<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\App\Shop;

	// Laravel
	use App\Http\Controllers\Controller;
	use Illuminate\Http\Request;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Support\Carbon;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Log;
	use Laravel\Cashier\Subscription;
	use Laravel\Cashier\Events\WebhookReceived;
	use Stripe\Subscription as StripeSubscription;
	use Ramsey\Uuid\Uuid;

	// App
	use App\Http\Resources\Auth\UserResource;
	use App\Http\Requests\App\Shop\SubscriptionRequest;
	use App\Http\Requests\App\Shop\CancelSubscriptionRequest;
	use App\Http\Requests\App\Shop\ResumeSubscriptionRequest;
	use App\Http\Requests\App\Shop\PaymentSecretRequest;
	use App\Http\Resources\Shop\StripeSubscriptionResource;
	use App\Notifications\SubscriptionCancelledNotification;
	use App\Notifications\SubscriptionResumedNotification;

	// App Models
	use App\Models\Auth\User;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * use following webhook events in stripe:
	 *
	 * price.updated, price.deleted,
	 * product.updated, product.deleted
	 * charge.refunded
	 * checkout.session.async_payment_succeeded
	 * customer.subscription.created, customer.subscription.updated, customer.subscription.deleted,
	 * customer.updated, customer.deleted
	 * invoice.payment_action_required
	 * setup_intent.succeeded
	 */

class StripeSubscriptionController extends Controller {

	// model classes
	protected $modelClass = User::class;
	protected $modelResourceClass = UserResource::class;
	protected $modelListResourceClass = UserResource::class;

	// model relations
	protected $modelRelations = [];
	protected $modelListRelations = [];



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CHECKOUT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function checkoutSubscription(Request $request, string $type) {

		// redirect if user not logged in
		$me = Auth::user();
		if(!$me) { return redirect('/login?redirect='.$request->path()); }

		// redirect if user already has subscription
		if($me->subscribed($type)) { return redirect('/404'); }

		// get price for subscription
		$price = null;
		switch($type) {
			case 'free': 	return redirect('/404');
			default: 		$price = config('cashier.subscription.default'); break;
		}
		if(!$price) {
			Log::critical('StripeSubscriptionController::checkoutSubscription() - no price found for subscription: '.$type);
			return redirect('/404');
		}

		// set stripe urls
		$urlSuccess = route('stripe.checkout.success.subscription',['type' => $type]) . '?session_id={CHECKOUT_SESSION_ID}';
		$urlCancel 	= $request->headers->get('referer', config('app.url'));

		// stripe checkout features
		$me->allowPromotionCodes();

		// create stripe data
		$stripe = new \Stripe\StripeClient(config('cashier.secret'));
		$stripeConfig = [
			'line_items' => [
				['price' => $price, 'quantity' => 1],
			],

			// default properties
			'success_url' => $urlSuccess,
			'cancel_url' => $urlCancel,
			'locale' => $me->lang,
			'mode' => 'subscription',

			'metadata' => [
				'user_id' => $me->id,
				'subscription' => $price,
			],

			// invoice/receipt settings
			'billing_address_collection' => 'required', 			// collect address from user

			// stripe features
			'automatic_tax' => [ 'enabled' => true ], 			// enable stripe tax

			// recurring payments
			'payment_method_types' => [
				'card', 'sepa_debit', 'paypal'
			],

			// subscription properties
			'subscription_data' => [
				'metadata' => [
					'user_id' => $me->id,
				],
			],
		];

		// add trial if first subscription
		if(!$me->trial_used) { $stripeConfig['subscription_data']['trial_period_days'] = 30; }

		// add stripe id if already customer
		if($me->stripe_id) { $stripeConfig['customer'] = $me->stripe_id; }

		// or create new user via email
		else { $stripeConfig['customer_email'] = $me->email; }

		// redirect to stripe checkout page
		$session = $stripe->checkout->sessions->create($stripeConfig);
		return redirect($session['url']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CHECKOUT SUCCESS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function checkoutSubscriptionSuccess(Request $request, string $type) {

		$stripe = new \Stripe\StripeClient(config('cashier.secret'));

		// get stripe data
		$session = $stripe->checkout->sessions->retrieve($request->get('session_id'));
		if(!$session) { return redirect('/404'); }

		// get user
		$user = User::find($session['metadata']['user_id']);
		if(!$user) { return redirect('/404'); }

		// update user as stripe customer
		if(!$user->stripe_id) { $user->stripe_id = $session['customer']; }

		// update role if already verified
		if($user->hasRole('user')) { $user->syncRoles(['member']); }

		// save stripe subscription for user
		$subscription = $stripe->subscriptions->retrieve($session['subscription']);
		if(!$subscription) { return redirect('/404'); }
		$this->createSubscription($user, $subscription, $type);

		// update default payment data for user
		$paymentMethod = $stripe->paymentMethods->retrieve($subscription['default_payment_method']);
		$user->updateDefaultPaymentMethod($paymentMethod);

		// update user
		$user->trial_used = true;
		$user->save();

		// Redirect to verify
		return redirect((app()->getLocale() == config('app.fallback_locale') ? '' : '/'.app()->getLocale()) . '/verify?checkout=1');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CREATE SUBSCRIPTION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createSubscription(User $user, StripeSubscription $stripeSubscription, string $type) {

		$user->deleteStripeSubscriptions();

		// get product for subscription
		$firstItem		= $stripeSubscription->items->first();
		$isSinglePrice	= $stripeSubscription->items->count() === 1;

		// create subscription model
		$subscription = $user->subscriptions()->create([
			'type' => 			$type,
			'stripe_id' => 		$stripeSubscription->id,
			'stripe_status' => 	$stripeSubscription->status,
			'stripe_price' => 	$isSinglePrice ? $firstItem->price->id : null,
            'quantity' => 		$isSinglePrice ? ($firstItem->quantity ?? null) : null,
			'trial_ends_at' => 	$stripeSubscription->trial_end ? new Carbon($stripeSubscription->trial_end) : null,
			'ends_at' => 		$stripeSubscription->ended_at ?? null,
		]);

		// create subscription items for more than one product
		if($stripeSubscription->items->count() > 1) {

			foreach ($stripeSubscription->items as $item) {
				$subscription->items()->create([
					'stripe_id' => 			$item->id,
					'stripe_product' => 	$item->price->product,
					'stripe_price' => 		$item->price->id,
					'quantity' => 			$item->quantity ?? null,
				]);
			}
		}

		return $subscription;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CANCEL SUBSCRIPTION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function cancelSubscription(CancelSubscriptionRequest $request): JsonResponse {

		$me = Auth::user();
		$id = $request->validated('id');
		$user = User::find($id);

		// only my subscription if not admin
		if($me->id != $id && !$me->isAdmin()) { return $this->responseError(403); }

		// find subscription by type
		$type = $request->validated('type','default');
		$subscription = $user->subscription($type);
		if(!$subscription) { return $this->responseError(404); }

		// cancel subscription
		$subscription->cancel();
		$user->notify(new SubscriptionCancelledNotification($subscription->ends_at,$me->lang));

		$stripeSubscription = $this->getSubscription($user, $type);
		return $this->responseGet($stripeSubscription, StripeSubscriptionResource::class);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	RESUME SUBSCRIPTION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function resumeSubscription(ResumeSubscriptionRequest $request): JsonResponse {

		$me = Auth::user();
		$id = $request->validated('id');
		$user = User::find($request->id);

		// only my subscription if not admin
		if($me->id != $id && !$me->isAdmin()) { return $this->responseError(403); }

		// find subscription by type
		$type = $request->validated('type','default');
		$subscription = $user->subscription($type);
		if(!$subscription) { return $this->responseError(); }

		// resume subscription
		$subscription->resume();
		$user->notify(new SubscriptionResumedNotification($me->lang));

		$stripeSubscription = $this->getSubscription($user, $type);
		return $this->responseGet($stripeSubscription, StripeSubscriptionResource::class);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	GET SUBSCRIPTION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function get(SubscriptionRequest $request): JsonResponse {

		$me = Auth::user();

		// laravel cashier subscription
		$type = $request->validated('type','default');
		$stripeSubscription = $me->hasFreeSubscription() ? $me->subscription('free') : $this->getSubscription($me, $type);

		return $this->responseGet($stripeSubscription, StripeSubscriptionResource::class);
	}


	protected function getSubscription(User $user, string $type = 'default'): ?StripeSubscription {

		$subscription = $user->subscription($type);
		if(!$subscription) { return null; }

		// stripe subscription
		$stripe = new \Stripe\StripeClient(config('cashier.secret'));
		$stripeSubscription = $stripe->subscriptions->retrieve($subscription->stripe_id);
		$stripeSubscription->type = $type;

		// get last 6 invoices
		$invoices = $stripe->invoices->all(['subscription' => $stripeSubscription->id, 'limit' => 6]);
		$stripeSubscription->invoices = $invoices->data;

		return $stripeSubscription;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FREE SUBSCRIPTION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function applyFreeSubscription(User &$user): bool {

		// only admins can apply free subscriptions
		$me = Auth::user();
		if(!$me || !$me->isAdmin()) { return false; }

		if($user->hasFreeSubscription()) { return true; }

		// remove all other subscriptions
		$user->deleteStripeSubscriptions();

		Subscription::create([
			'user_id' => 		$user->id,
			'type' => 			'free',
			'stripe_id' => 		'sub_' . (string) Uuid::uuid4(),
			'stripe_status' => 	'active',
			'stripe_price' => 	'price_xxx',
			'quantity' => 		1,
		]);

		$user->trial_used = true;
		$user->trial_ends_at = null;

		// update user role
		if($user->hasRole('guest')) {
			$user->changeRole('guest', 'user');
			$user->email_verified_at = Carbon::now();
		}

		return $user->save();
	}


	public function deleteFreeSubscription(User &$user): void {

		if(!$user->hasFreeSubscription()) { return; }

		// remove free subscription
		DB::table('subscriptions')->where(['user_id' => $user->id, 'type' => 'free'])->delete();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SUBSCRIPTION PAYMENTS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getPaymentSecret(PaymentSecretRequest $request): JsonResponse {

		$me = Auth::user();
		$id = $request->validated('id');
		$type = $request->validated('type','default');

		// only for myself
		if(!$me || $me->id != $id || !$me->hasStripeId()) { return $this->responseError(403); }

		// find subscription by type
		$subscription = $me->subscription($type);
		if(!$subscription) { return $this->responseError();	}

		// create payment secret
		$secret = $me->createSetupIntent([
			'customer' => $me->stripe_id,
			'payment_method_types' => [
				'card',
				'sepa_debit',
			],
			'metadata' => [
				'customer_id' => $me->stripe_id,
				'subscription_id' => $subscription->stripe_id,
			],
		]);

		return $this->responseData($secret);
	}


	protected function updateSubscriptionPaymentMethod($setupIntent): bool {

		// get customer
		$customer = User::where('stripe_id', $setupIntent['metadata']['customer_id'])->first();
		if(!$customer) { return false; }

		// get subscription
		$subscription = $customer->subscriptions()->where('stripe_id', $setupIntent['metadata']['subscription_id'])->first();
		if(!$subscription) { return false; }

		// update payment method
		$customer->updateDefaultPaymentMethod($setupIntent['payment_method']);

		return true;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	WEBHOOKS PAYMENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function onSetupIntentSucceeded(WebhookReceived $event): JsonResponse {

		// get setup intent from event
		$setupIntent = $event->payload['data']['object'];

		$success = true;
		if(isset($setupIntent['metadata']) && isset($setupIntent['metadata']['subscription_id'])) {
			$success = $this->updateSubscriptionPaymentMethod($setupIntent);
		}

		return $success ? $this->responseSuccess() : $this->responseError(403);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	WEBHHOOKS SUBCRIPTION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function onSubscriptionUpdated(WebhookReceived $event): JsonResponse {

		// find user
		$user = User::where('stripe_id', $event->payload['data']['object']['customer'] ?? 'none')->first();
		if(!$user) { $user = User::find($event->payload['data']['object']['metadata']['user_id']); }

		// find subscription
		$subscription = $user ? $user->subscriptions()->where('stripe_id',$event->payload['data']['object']['id'])->first() : null;

		if(!$user || !$subscription) { return $this->responseSuccess(); }

		// get subscription data
		$data = $event->payload['data']['object'];
		$firstItem = $data['items']['data'][0];
        $isSinglePrice = count($data['items']['data']) === 1;

		// remove incomplete subscription
		if($data['status'] === StripeSubscription::STATUS_INCOMPLETE_EXPIRED) {
			$subscription->items()->delete();
			$subscription->delete();
			return $this->responseSuccess();
		}

		// is trial ending?
		if(isset($data['trial_end'])) {
			$trialEnd = Carbon::createFromTimestamp($data['trial_end']);
			if (!$subscription->trial_ends_at || $subscription->trial_ends_at->ne($trialEnd)) {
				$subscription->trial_ends_at = $trialEnd;
			}
		}

		// is cancelled?
		if(isset($data['cancel_at_period_end'])) {
			if($data['cancel_at_period_end']) {
				$subscription->ends_at = $subscription->onTrial() ? $subscription->trial_ends_at : Carbon::createFromTimestamp($data['current_period_end']);
			}
			else if(isset($data['cancel_at'])) {
				$subscription->ends_at = Carbon::createFromTimestamp($data['cancel_at']);
			}
			else {
				$subscription->ends_at = null;
			}
		}

		// update subscription
		$subscription->stripe_price		= $isSinglePrice ? $firstItem['price']['id'] : null;
		$subscription->quantity 		= $isSinglePrice && isset($firstItem['quantity']) ? $firstItem['quantity'] : null;
		$subscription->stripe_status 	= $data['status'];
		$subscription->save();

		// update subscription items if more than one product
		if (count($data['items']['data'])>1) {

			$prices = [];

			foreach($data['items']['data'] as $item) {
				$prices[] = $item['price']['id'];
				$subscription->items()->updateOrCreate([
					'stripe_id' => $item['id'],
				],
				[
					'stripe_product' => $item['price']['product'],
					'stripe_price' => $item['price']['id'],
					'quantity' => $item['quantity'] ?? null,
				]);
			}

			// Delete items that aren't attached to the subscription anymore...
			$subscription->items()->whereNotIn('stripe_price', $prices)->delete();
		}

		return $this->responseSuccess();
	}


	public function onSubscriptionDeleted(WebhookReceived $event): JsonResponse {

		// find user
		$user = User::find($event->payload['data']['object']['metadata']['user_id']);
		if(!$user) { return $this->responseSuccess(); }

		// remove subscriptions
		$subscription = $user->subscriptions()->where('stripe_id',$event->payload['data']['object']['id'])->first();
		if($subscription) {
			$subscription->markAsCanceled();
			$subscription->items()->delete();
			$subscription->delete();
		}

		// update user status from member to user
		if($user->hasRole('member')) {
			$user->changeRole('member', 'user');
		}

		// logout user from all devices
		$this->logoutFromAllDevices($user);

		return $this->responseSuccess();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
