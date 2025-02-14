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
	use Illuminate\Support\Facades\Cache;
	use Laravel\Cashier\Events\WebhookReceived;

	// App
	use App\Http\Resources\Auth\UserResource;
	use App\Jobs\Shop\SyncStripeProduct;

	// App Models
	use App\Models\Auth\User;
	use App\Models\Shop\Product;



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

class StripeProductController extends Controller {

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


	public function checkoutProduct(Request $request, string $type, string $id): mixed {

		// redirect if user not logged in
		$me = Auth::user();
		if(!$me) { return redirect('/login?redirect='.$request->path()); }

		// get product model
		$product = $this->findProductByType($type, $id);
		if(!$product) { return redirect('/404'); }

		// set checkout items via price
		$items = [];
		$items[$product->stripe_price_id] = 1; // quantity of 1

		// set stripe urls
		$urlSuccess = route('stripe.checkout.success',['type' => $type]) . '?session_id={CHECKOUT_SESSION_ID}';
		$urlCancel 	= $request->headers->get('referer', config('app.url'));

		// stripe checkout features
		$me->allowPromotionCodes();

		// Redirect to stripe checkout page
		return $me->checkout($items, [

			// default properties
			'success_url' => $urlSuccess,
			'cancel_url' => $urlCancel,
			'locale' => $me->lang,

			// invoice/receipt settings
			'billing_address_collection' => 'required', 			// collect address data from user for tax
			'invoice_creation' => [ 'enabled' => true ],

			// stripe features
			'customer_update' => ['address' => 'auto', 'name' => 'auto'],
			'automatic_tax' => [ 'enabled' => true ], 			// enable stripe tax

			// one time payments
			'payment_method_types' => [
				'card',
				'klarna',
				'sofort',
				'giropay',
				'paypal',
			],

			'payment_intent_data' => [

				'description' => 'Payment ' . $product->title,
				'receipt_email' => $me->email,

				'metadata' => [
					'user_id' => 		$me->id,
					'product_id' => 	$product->id,
					'product_type' => 	$type,
				],
			]
		]);
	}


	protected function findProductByType(string $productType, string $productId): mixed {

		switch($productType) {
			case 'product': 	return Product::find($productId); break;
			//case 'course': 	return Course::find($productId); break;
		}

		// @codeCoverageIgnoreStart
		return null;
		// @codeCoverageIgnoreEnd
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CHECKOUT SUCCESS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function checkoutProductSuccess(Request $request) {

		$stripe = new \Stripe\StripeClient(config('cashier.secret'));

		// get data from stripe checkout session
		$session = $stripe->checkout->sessions->retrieve($request->get('session_id'));
		$paymentIntent = $stripe->paymentIntents->retrieve($session->payment_intent);

		// update relation with succeeded payment intent
		$product =  $this->confirmPaymentIntent($paymentIntent);
		if(!$product) { return redirect('/404'); }

		// redirect to product page
		$url = app()->getLocale() == config('app.fallback_locale') ? '' : '/'.app()->getLocale();
		switch($paymentIntent['metadata']['product_type']) {
			case 'product': 	$url .= '/product/'.$product->slug; break;
			//case 'course': 		$url .= '/course/'.$product->slug; break;
		}

		return redirect($url);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	STRIPE PAYMENT INTENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	private function confirmPaymentIntent(&$paymentIntent) {

		if(empty($paymentIntent['metadata'])) { return false; }

		// get product and user models
		$productId = $paymentIntent['metadata']['product_id'];
		$productType = $paymentIntent['metadata']['product_type'];

		$product = $this->findProductByType($productType, $productId);
		$user = User::find($paymentIntent['metadata']['user_id']);

		if(!$product || !$user) { return false; }

		$stripe = new \Stripe\StripeClient(config('cashier.secret'));

		// get receipt
		$charge = $stripe->charges->retrieve($paymentIntent['latest_charge']);
		$receipt = $charge['receipt_url'] ?? null;

		// get address
		$address = $charge['billing_details']['address'] ?? null;
		if($address && $address['line1'] && !$user->street) {

			// get street number from line1
			preg_match_all('/\s+([\d].*)/m', $address['line1'], $matches);
			$street_number = isset($matches[1]) && isset($matches[1][0]) ? trim($matches[1][0]) : '';

			// update user address
			$user->street 	= trim( str_replace($street_number,'',$address['line1']) );
			$user->street_number = $street_number;
			$user->zipcode  = $address['postal_code'];
			$user->city 	= $address['city'];
			$user->country 	= strtolower($address['country']);
			$user->save();
		}

		// set product_user relation
		$tableName = $productType.'_user';
		$productName = $productType.'_id';
		$tableProps = [];
		$tableProps[$productName] = $product->id;
		$tableProps['user_id'] = $user->id;

		// update existing relation
		$hasRecord = DB::table($tableName)->where($tableProps)->exists();
		if($hasRecord) {
			DB::table($tableName)->where($tableProps)->update([
				'receipt' => $receipt,
				'status' => $paymentIntent['status'],
				'updated_at' => Carbon::now(),
			]);
		}
		// set new relation
		else {
			$tableProps['receipt'] = $receipt;
			$tableProps['status'] = $paymentIntent['status'];
			$tableProps['created_at'] = Carbon::now();
			$tableProps['updated_at'] = Carbon::now();
			DB::table($tableName)->insert($tableProps);
		}

		return $product;
	}


	private function abortPaymentIntent(&$paymentIntent): bool {

		if(empty($paymentIntent['metadata'])) { return false; }

		// get product and user models
		$productId = $paymentIntent['metadata']['product_id'] ?? null;
		$productType = $paymentIntent['metadata']['product_type'] ?? null;

		$product = $this->findProductByType($productType, $productId);
		$user = User::find($paymentIntent['metadata']['user_id'] ?? null);

		if(!$product || !$user) { return false; }

		// remove product_user relation
		$hasRecord = DB::table('product_user')->where(['product_id'=>$product->id,'user_id'=>$user->id])->exists();
		if($hasRecord) {
			DB::table('product_user')->where(['product_id'=>$product->id,'user_id'=>$user->id])->delete();
		}

		return true;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	WEBHOOKS PAYMENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function onPaymentSuccess(WebhookReceived $event): JsonResponse {

		// get payment intent from event
		$paymentIntent = $event->payload['data']['object'];

		// update database with succeeded payment intent
		$product =  $this->confirmPaymentIntent($paymentIntent);
		if(!$product) { return $this->resonseError(403); }

		return $this->responseSuccess();
	}


	public function onAsyncPaymentSuccess(WebhookReceived $event): JsonResponse {

		$stripe = new \Stripe\StripeClient(config('cashier.secret'));

		// get payment intent from single product
		if($event->payload['data']['object']['mode']=='payment') {

			$paymentIntent = $stripe->paymentIntents->retrieve($event->payload['data']['object']['payment_intent']);

			// update database with succeeded payment intent
			$product =  $this->confirmPaymentIntent($paymentIntent);
			if(!$product) { return $this->resonseError(403); }
		}

		return $this->responseSuccess();
	}


	public function onAsyncPaymentFailed(WebhookReceived $event): JsonResponse {

		$stripe = new \Stripe\StripeClient(config('cashier.secret'));

		// get payment intent from strip api
		$paymentIntent = $stripe->paymentIntents->retrieve($event->payload['data']['object']['payment_intent']);

		// update database with failed payment intent
		$this->abortPaymentIntent($paymentIntent);

		return $this->responseSuccess();
	}


	public function onChargeRefunded(WebhookReceived $event): JsonResponse {

		$stripe = new \Stripe\StripeClient(config('cashier.secret'));
		$data = $event->payload['data']['object'];

		// find client
		$user = User::where('stripe_id',$data['customer'])->first();
		if(!$user) {return $this->responseSuccess(); } // success to delete webhook in stripe

		// iterate all refunds in data
		foreach($data['refunds']['data'] as $refund) {

			// get according charge object
			$charge = $stripe->charges->retrieve($refund['charge']);

			// total refund amount must match charge amount
			if(!$charge || $refund['amount'] != $charge['amount']) { continue; }

			// get product from metadata or skip
			if(empty($charge['metadata'])) { continue; }
			$productType	= $charge['metadata']['product_type'];
			$productId		= $charge['metadata']['product_id'];

			// remove product on complete refund
			switch($productType) {
				case "product":
					DB::table('product_user')->where(['product_id'=>$productId,'user_id'=>$user->id])->delete();
					break;
				/* case 'course':
					DB::table('course_user')->where(['course_id'=>$productId,'user_id'=>$user->id])->delete();
					break; */
			}
		}

		return $this->responseSuccess();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	WEBHOOKS PRODUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function onProductUpdated(WebhookReceived $event): JsonResponse {

		// find product
		$stripe_product_id = $event->payload['data']['object']['id'];
		$product = Product::where('stripe_id', $stripe_product_id)->first();
		if(!$product) { return $this->responseSuccess(); }

		// force update on product
		$product->stripe_synced = false;
		$product->save();

		// if stripe product is active an not archived
		if($event->payload['data']['object']['active']==true) {
			SyncStripeProduct::dispatch($product);
		}

		Cache::flush();

		return $this->responseSuccess();
	}


	public function onProductDeleted(WebhookReceived $event): JsonResponse {

		// find product
		$stripe_product_id = $event->payload['data']['object']['id'];
		$product = Product::where('stripe_id', $stripe_product_id)->first();
		if(!$product) { return $this->responseSuccess(); }

		// hide product
		$product->public = false;
		$product->stripe_synced = false;
		$product->save();

		Cache::flush();

		return $this->responseSuccess();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
