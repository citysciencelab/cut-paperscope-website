<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Http\Controllers\App\Shop;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Support\Facades\Queue;
	use Illuminate\Support\Facades\Notification;
	use Illuminate\Support\Facades\Config;
	use Illuminate\Support\Collection;
	use Illuminate\Support\Facades\DB;
	use Stripe\Collection as StripeCollection;
	use Illuminate\Support\Carbon;
	use Mockery;

	// App
	use App\Http\Controllers\App\Shop\StripeSubscriptionController;
	use App\Notifications\SubscriptionCancelledNotification;
	use App\Notifications\SubscriptionResumedNotification;
	use App\Models\Auth\User;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class StripeSessionStub {

	const OBJECT_NAME = 'checkout.session';
}


class StripeSubscriptionControllerTest extends TestCase {

	use RefreshDatabase;


	protected function setUp(): void {

		parent::setUp();
		$this->skipIfNoShop();

		// skip if no cashier key
		if (!config('cashier.key')) { $this->markTestSkipped('No Stripe key set.'); }
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CREATE CHECKOUT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_create_session_for_default_subscription() {

		// arrange
		$this->seed();
		$this->loginAsUser();
		$this->mockStripeCheckout();

		// act
		$response = $this->get('/stripe/checkout/subscription/default');

		// assert: redirect to stripe url
		$response->assertRedirect();
		$this->assertStringContainsString('stripe.com', $response->getTargetUrl());
	}


	public function test_create_session_not_logged_in() {

		// arrange
		$this->seed();

		// act
		$response = $this->get('/stripe/checkout/subscription/default');

		// assert: redirect to login
		$response->assertRedirect();
		$this->assertStringContainsString('login', $response->getTargetUrl());
	}


	public function test_create_session_missing_price() {

		// arrange
		$this->seed();
		$this->loginAsUser();

		// act
		Config::set('cashier.subscription.default', null);

		// assert: redirect to error page
		$response = $this->get('/stripe/checkout/subscription/default');
		$response->assertRedirect('/404');
	}


	public function test_create_session_for_wrong_type() {

		// arrange
		$this->seed();
		$this->loginAsUser();

		// act
		$response = $this->get('/stripe/checkout/subscription/wrongtype');

		// assert: response to default page
		$response->assertStatus(200);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CHECKOUT SUCCESS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_checkout_success_new_subscription() {

		// arrange
		Queue::fake();
		$this->seed();
		$user = $this->loginAsUser(['stripe_id' => 'cus_123']);

		// arrange: mock stripe
		$this->mockStripeClient($user);
		$this->mockStripeSubscription();

		// act
		$response = $this->get('/stripe/checkout/success/subscription/default');

		// assert: redirect to success page
		$response->assertRedirect();

		// assert: user is member
		$user = User::find($user->id);
		$this->assertTrue($user->isMember());
	}


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_checkout_success_existing_subscription() {

		// arrange
		Queue::fake();
		$this->seed();
		$user = $this->loginAsUser(['stripe_id' => 'cus_123']);
		$this->createSubscription($user);

		// arrange: mock stripe
		$this->mockStripeClient($user);
		$this->mockStripeSubscription();

		// act
		$response = $this->get('/stripe/checkout/success/subscription/default');

		// assert: redirect to success page
		$response->assertRedirect();
	}


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_checkout_success_new_subscription_with_multiple_items() {

		// arrange
		Queue::fake();
		$this->seed();
		$user = $this->loginAsUser(['stripe_id' => 'cus_123']);

		// arrange: mock stripe
		$this->mockStripeClient($user);
		$this->mockStripeSubscription([
			(object) [
				"id" => 'prod_123',
				"quantity" => 1,
				"price" => (object)[
					"id" => "price_123",
					"product" => 'test'
				]
			],
			(object) [
				"id" => 'prod_456',
				"quantity" => 1,
				"price" => (object)[
					"id" => "price_456",
					"product" => 'test2'
				]
			],
		]);

		// act
		$response = $this->get('/stripe/checkout/success/subscription/default');

		// assert: redirect to success page
		$response->assertRedirect();

		// assert: subscription items
		$this->assertDatabaseHas('subscription_items',['subscription_id' => 2, 'stripe_id' => 'prod_123']);
		$this->assertDatabaseHas('subscription_items',['subscription_id' => 2, 'stripe_id' => 'prod_456']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CANCEL SUBSCRIPTION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_cancel_subscription() {

		// arrange
		Queue::fake();
		Notification::fake();
		$this->seed();
		$user = $this->loginAsUser(['stripe_id' => 'cus_123']);

		// arrange: mock stripe
		$this->mockStripeClient($user);
		$this->mockStripeSubscription();
		$this->mockStripeSubscriptionUpdate($user);
		$this->createSubscription($user);

		// act
		$response = $this->post('/api/stripe/subscription/cancel', [
			'id' => $user->id,
			'type' => 'default',
		]);

		// assert: response
		$response->assertStatus(200);

		// assert: notification
		Notification::assertSentTo($user,SubscriptionCancelledNotification::class);
	}


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_cancel_subscription_for_user_as_admin() {

		// arrange
		Queue::fake();
		Notification::fake();
		$this->loginAsAdmin();
		$user = $this->createUser(['stripe_id' => 'cus_123']);

		// arrange: mock stripe
		$this->mockStripeClient($user);
		$this->mockStripeSubscription();
		$this->mockStripeSubscriptionUpdate($user);
		$this->createSubscription($user);

		// act
		$response = $this->post('/api/stripe/subscription/cancel', [
			'id' => $user->id,
			'type' => 'default',
		]);

		// assert: response
		$response->assertStatus(200);

		// assert: notification
		Notification::assertSentTo($user,SubscriptionCancelledNotification::class);
	}


	 public function test_cancel_subscription_for_user_without_permission() {

		// arrange
		Queue::fake();
		Notification::fake();
		$this->seed();
		$this->loginAsUser();
		$user = $this->createUser(['stripe_id' => 'cus_123']);

		// act
		$response = $this->post('/api/stripe/subscription/cancel', [
			'id' => $user->id,
			'type' => 'default',
		]);

		// assert
		$response->assertStatus(403);
	}


	 public function test_cancel_non_existing_subscription() {

		// arrange
		Queue::fake();
		Notification::fake();
		$this->seed();
		$user = $this->loginAsUser();

		// act
		$response = $this->post('/api/stripe/subscription/cancel', [
			'id' => $user->id,
			'type' => 'default',
		]);

		// assert
		$response->assertStatus(404);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    RESUME SUBSCRIPTION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_resume_subscription() {

		// arrange
		Queue::fake();
		Notification::fake();
		$this->seed();
		$user = $this->loginAsUser(['stripe_id' => 'cus_123']);

		$cancelData = [
			'stripe_status' => 'canceled',
			'ends_at' => Carbon::now()->addDays(1)->timestamp
		];

		// arrange: mock stripe
		$this->mockStripeClient($user);
		$this->mockStripeSubscription();
		$this->mockStripeSubscriptionUpdate($user);
		$this->createSubscription($user, $cancelData);

		// act
		$response = $this->post('/api/stripe/subscription/resume', [
			'id' => $user->id,
		]);

		// assert: response
		$response->assertStatus(200);

		// assert: notification
		Notification::assertSentTo($user,SubscriptionResumedNotification::class);
	}


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_resume_subscription_for_user_as_admin() {

		// arrange
		Queue::fake();
		Notification::fake();
		$this->loginAsAdmin();
		$user = $this->createUser(['stripe_id' => 'cus_123']);

		$cancelData = [
			'stripe_status' => 'canceled',
			'ends_at' => Carbon::now()->addDays(1)->timestamp
		];

		// arrange: mock stripe
		$this->mockStripeClient($user);
		$this->mockStripeSubscription();
		$this->mockStripeSubscriptionUpdate($user);
		$this->createSubscription($user, $cancelData);

		// act
		$response = $this->post('/api/stripe/subscription/resume', [
			'id' => $user->id,
			'type' => 'default',
		]);

		// assert: response
		$response->assertStatus(200);

		// assert: notification
		Notification::assertSentTo($user,SubscriptionResumedNotification::class);
	}


	public function test_resume_subscription_for_user_without_permission() {

		// arrange
		Queue::fake();
		Notification::fake();
		$this->seed();
		$this->loginAsUser();
		$user = $this->createUser(['stripe_id' => 'cus_123']);

		// act
		$response = $this->post('/api/stripe/subscription/resume', [
			'id' => $user->id,
			'type' => 'default',
		]);

		// assert
		$response->assertStatus(403);
	}


	public function test_resume_non_existing_subscription() {

		// arrange
		Queue::fake();
		Notification::fake();
		$this->seed();
		$user = $this->loginAsUser();

		// act
		$response = $this->post('/api/stripe/subscription/resume', [
			'id' => $user->id,
			'type' => 'default',
		]);

		// assert
		$response->assertStatus(404);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    GET SUBSCRIPTION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_get_subscription() {

		// arrange: user
		$user = $this->loginAsUser();

		// arrange: mock stripe subscription
		$this->mockStripeClient($user);
		$this->createSubscription($user);
		$this->mockStripeSubscription();

		// act
		$data = $this->postData('/api/stripe/subscription', [
			'type' => 'default',
		]);

		// assert
		$this->assertIsArray($data);
		$this->assertEquals('stripe_subscription',$data['type']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    FREE SUBSCRIPTION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_add_free_subscription_on_user_save() {

		// arrange
		$this->loginAsAdmin();

		// arrange: new user
		$newUser = [
			'email' => 'asdj123@asdeafj.de',
			'name' => 'Max',
			'surname' => 'Mustermann',
			'username' => 'max_mustermann',
			'gender' => 'm',
			'password' => 'muster_passwort_123',
			'password_confirmation' => 'muster_passwort_123',
			'role' => 'guest',
			'free_subscription' => true,
		];

		// act: save user
		$user = $this->postData('/api/user/save', $newUser, $this->getBackendHeaders());
		$user = User::find($user['id']);

		// assert: for existing subscription
		$this->assertTrue($user->subscribed('free'));

		// assert: guest must be a user now
		$this->assertFalse($user->isGuest());
		$this->assertTrue($user->isUser());

		// assert: reset for trial
		$this->assertTrue($user->trial_used);
		$this->assertNull($user->trial_ends_at);
	}


	public function test_remove_free_subscription_on_user_save() {

		// arrange
		$this->loginAsAdmin();
		$user = $this->createUser();

		// arrange: add free subscription first
		$StripesubscriptionController = new StripeSubscriptionController;
		$StripesubscriptionController->applyFreeSubscription($user);

		// assert: user with active subscription
		$user = User::find($user->id);
		$this->assertTrue($user->subscribed('free'));

		// act: delete subscription
		$userData = $user->toArray();
		$userData['free_subscription'] = false;
		$user = $this->postData('/api/user/save', $userData, $this->getBackendHeaders());

		// assert: user without subscription
		$user = User::find($user['id']);
		$this->assertFalse($user->subscribed('free'));
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    SUBSCRIPTION PAYMENTS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	 public function test_get_payment_secret() {

		// arrange
		Queue::fake();
		$user = $this->loginAsUser(['stripe_id' => 'cus_123']);

		// arrange: mock stripe
		$this->mockStripeClient($user);
		$this->mockStripeSubscription();
		$this->createSubscription($user);

		// act
		$response = $this->post('/api/stripe/payment/secret', [
			'type' => 'default',
			'id' => $user->id,
			'offset' => 'e57f0dd9190f512344bd2e8575d814ba',
		]);

		// assert: response
		$response->assertStatus(200);
		$response->assertJsonPath('data.client_secret', 'seti_123');
	}



	 public function test_get_payment_secret_not_allowed_for_other_user() {

		// arrange
		Queue::fake();
		$this->loginAsUser(['stripe_id' => 'cus_123']);
		$user = $this->createUser(['stripe_id' => 'cus_456']);

		// act
		$response = $this->post('/api/stripe/payment/secret', [
			'type' => 'default',
			'id' => $user->id,
			'offset' => 'e57f0dd9190f512344bd2e8575d814ba',
		]);

		// assert
		$response->assertStatus(403);
	}


	public function test_get_payment_secret_no_subscription() {

		// arrange
		Queue::fake();
		$user = $this->loginAsUser(['stripe_id' => 'cus_123']);

		// act
		$response = $this->post('/api/stripe/payment/secret', [
			'type' => 'default',
			'id' => $user->id,
			'offset' => 'e57f0dd9190f512344bd2e8575d814ba',
		]);

		// assert
		$response->assertStatus(404);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    WEBHOOK PAYMENTS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_webhook_on_onSetupIntentSucceeded() {

		// arrange
		Queue::fake();
		$this->seed();
		$user = $this->createUser(['stripe_id' => 'cus_123']);

		// arrange: mock stripe
		$this->mockStripeClient($user);
		$this->mockStripeSubscription();
		$this->createSubscription($user);

		// arrange: mock webhook
		$webhook = $this->mockStripeWebhook([
			'metadata' => [
				'customer_id' => 'cus_123',
				'subscription_id' => 'sub_123',
			],
			'payment_method' => 'pm_123'
		]);

		// act
		$controller = new StripeSubscriptionController();
		$response = $controller->onSetupIntentSucceeded($webhook);

		// assert
		$this->assertEquals($response->getStatusCode(), 200);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    WEBHOOK SUBSCRIPTION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_webhook_onSubscriptionUpdated() {

		// arrange
		Queue::fake();
		$this->seed();
		$user = $this->createUser(['stripe_id' => 'cus_123']);

		// arrange: mock stripe
		$this->createSubscription($user);

		// arrange: mock webhook
		$webhook = $this->mockStripeWebhook([
			'id' => 'sub_123',
			'status' => 'active',
			'customer' => 'cus_123',
			'quantity' => 1,
			'metadata' => [
				'user_id' => $user->id,
			],
			'items' => [
				'data' => [
					[
						"id" => "si_123",
						"quantity" => 1,
						"price" => ["id" => "price_123","product"=>""]
					],
					[
						"id" => "si_456",
						"quantity" => 1,
						"price" => ["id" => "price_456","product"=>""]
					]
				]
			]
		]);

		// act
		$controller = new StripeSubscriptionController();
		$response = $controller->onSubscriptionUpdated($webhook);

		// assert
		$this->assertEquals($response->getStatusCode(), 200);
	}


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_webhook_remove_incomplete_subscription() {

		// arrange
		Queue::fake();
		$this->seed();
		$user = $this->createUser(['stripe_id' => 'cus_123']);

		// arrange: mock stripe
		$this->createSubscription($user);

		// arrange: mock webhook
		$webhook = $this->mockStripeWebhook([
			'id' => 'sub_123',
			'status' => 'incomplete_expired',
			'customer' => 'cus_123',
			'quantity' => 1,
			'metadata' => [
				'user_id' => $user->id,
			],
			'items' => [
				'data' => [
					[
						"id" => "si_123",
						"quantity" => 1,
						"price" => ["id" => "price_123","product"=>""]
					]
				]
			]
		]);

		// act
		$controller = new StripeSubscriptionController();
		$response = $controller->onSubscriptionUpdated($webhook);

		// assert
		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertDatabaseMissing('subscriptions', ['stripe_id' => 'sub_123']);
	}


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	 public function test_webhook_ending_trial() {

		// arrange
		Queue::fake();
		$this->seed();

		// arrange: user with trial
		$user = $this->createUser(['stripe_id' => 'cus_123']);
		$trialEndsAt = Carbon::now()->addDays(30);
		$trialEndsAtUtc = $trialEndsAt->copy()->setTimezone('UTC');

		// arrange: mock stripe
		$this->createSubscription($user, [
			'stripe_status' => 'trialing',
			'trial_ends_at' => $trialEndsAtUtc,
		]);

		// arrange: mock webhook
		$webhook = $this->mockStripeWebhook([
			'id' => 'sub_123',
			'status' => 'active',
			'customer' => 'cus_123',
			'trial_end' => $trialEndsAt->timestamp,
			'quantity' => 1,
			'metadata' => [
				'user_id' => $user->id,
			],
			'items' => [
				'data' => [
					[
						"id" => "si_123",
						"quantity" => 1,
						"price" => [ "id" => "price_123"]
					]
				]
			]
		]);

		// act
		$controller = new StripeSubscriptionController();
		$response = $controller->onSubscriptionUpdated($webhook);

		// assert
		$this->assertEquals($response->getStatusCode(), 200);

		// assert: subscription
		$this->assertDatabaseHas('subscriptions', [
			'stripe_id' => 'sub_123',
			'stripe_status' => 'active',
			'trial_ends_at' => $trialEndsAtUtc->format('Y-m-d H:i:s'),
		]);
	}


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_webhook_cancel_subscription_period() {

		// arrange
		Queue::fake();
		$this->seed();

		// arrange: user with trial
		$user = $this->createUser(['stripe_id' => 'cus_123']);
		$cancelAt = Carbon::now()->addDays(30);
		$cancelAtUtc = $cancelAt->copy()->setTimezone('UTC');

		// arrange: mock stripe
		$this->createSubscription($user, [
			'stripe_status' => 'active',
		]);

		// arrange: mock webhook
		$webhook = $this->mockStripeWebhook([
			'id' => 'sub_123',
			'status' => 'active',
			'customer' => 'cus_123',
			'cancel_at_period_end' => true,
			'current_period_end' => $cancelAt->timestamp,
			'quantity' => 1,
			'metadata' => [
				'user_id' => $user->id,
			],
			'items' => [
				'data' => [
					[
						"id" => "si_123",
						"quantity" => 1,
						"price" => ["id" => "price_123","product"=>""]
					],
				]
			]
		]);

		// act
		$controller = new StripeSubscriptionController();
		$response = $controller->onSubscriptionUpdated($webhook);

		// assert
		$this->assertEquals($response->getStatusCode(), 200);

		// assert: subscription
		$this->assertDatabaseHas('subscriptions', [
			'stripe_id' => 'sub_123',
			'stripe_status' => 'active',
			'ends_at' => $cancelAtUtc->format('Y-m-d H:i:s'),
		]);
	}


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_webhook_cancel_subscription_timestamp() {

		// arrange
		Queue::fake();
		$this->seed();

		// arrange: user with trial
		$user = $this->createUser(['stripe_id' => 'cus_123']);
		$cancelAt = Carbon::now()->addDays(3);
		$cancelAtUtc = $cancelAt->copy()->setTimezone('UTC');

		// arrange: mock stripe
		$this->createSubscription($user, [
			'stripe_status' => 'active',
		]);

		// arrange: mock webhook
		$webhook = $this->mockStripeWebhook([
			'id' => 'sub_123',
			'status' => 'active',
			'customer' => 'cus_123',
			'cancel_at_period_end' => false,
			'cancel_at' => $cancelAt->timestamp,
			'quantity' => 1,
			'metadata' => [
				'user_id' => $user->id,
			],
			'items' => [
				'data' => [
					[
						"id" => "si_123",
						"quantity" => 1,
						"price" => ["id" => "price_123","product"=>""]
					],
				]
			]
		]);

		// act
		$controller = new StripeSubscriptionController();
		$response = $controller->onSubscriptionUpdated($webhook);

		// assert
		$this->assertEquals($response->getStatusCode(), 200);

		// assert: subscription
		$this->assertDatabaseHas('subscriptions', [
			'stripe_id' => 'sub_123',
			'stripe_status' => 'active',
			'ends_at' => $cancelAtUtc->format('Y-m-d H:i:s'),
		]);
	}


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	 public function test_webhook_reactivate_subscription() {

		// arrange
		Queue::fake();
		$this->seed();
		$user = $this->createUser(['stripe_id' => 'cus_123']);

		// arrange: mock stripe
		$this->createSubscription($user, [
			'stripe_status' => 'active',
			'ends_at' => Carbon::now()->addDays(3),
		]);

		// arrange: mock webhook
		$webhook = $this->mockStripeWebhook([
			'id' => 'sub_123',
			'status' => 'active',
			'customer' => 'cus_123',
			'cancel_at_period_end' => false,
			'quantity' => 1,
			'metadata' => [
				'user_id' => $user->id,
			],
			'items' => [
				'data' => [
					[
						"id" => "si_123",
						"quantity" => 1,
						"price" => ["id" => "price_123","product"=>""]
					],
				]
			]
		]);

		// act
		$controller = new StripeSubscriptionController();
		$response = $controller->onSubscriptionUpdated($webhook);

		// assert
		$this->assertEquals($response->getStatusCode(), 200);

		// assert: subscription
		$this->assertDatabaseHas('subscriptions', [
			'stripe_id' => 'sub_123',
			'stripe_status' => 'active',
			'ends_at' => null,
		]);
	}


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_webhook_onSubscriptionDeleted() {

		// arrange
		Queue::fake();
		$this->seed();
		$user = $this->createUser(['stripe_id' => 'cus_123']);
		$user->changeRole('user', 'member');

		// arrange: mock stripe
		$this->createSubscription($user, [
			'stripe_status' => 'active',
			'ends_at' => Carbon::now()->addDays(3),
		]);

		// arrange: mock webhook
		$webhook = $this->mockStripeWebhook([
			'id' => 'sub_123',
			'metadata' => [
				'user_id' => $user->id,
			],
		]);

		// act
		$controller = new StripeSubscriptionController();
		$response = $controller->onSubscriptionDeleted($webhook);

		// assert
		$this->assertEquals($response->getStatusCode(), 200);

		// assert: subscription
		$this->assertDatabaseMissing('subscriptions', [
			'stripe_id' => 'sub_123',
		]);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MOCKS STRIPE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function mockStripeCheckout() {

		$session = Mockery::mock('overload:\Stripe\Checkout\Session', StripeSessionStub::class);
		$session->ui_mode = 'hosted';
		$session->url = 'https://stripe.com/checkout';

		$sessions = Mockery::mock('overload:\Stripe\Service\Checkout\SessionService');
		$sessions->shouldReceive('create')->andReturn((array)$session);
	}


	protected function mockStripeClient($user) {

		$sessions = Mockery::mock('overload:\Stripe\Service\Checkout\SessionService');
		$sessions->shouldReceive('retrieve')->andReturn([
			'metadata' => [
				'user_id' => $user->id,
			],
			'customer' => 'cus_123',
			'subscription' => 'sub_123',
			'customer_details' => [
				'address' => [
					'line1' => 'Musterstraße 123a',
					'postal_code' => '12345',
					'city' => 'Hamburg',
					'country' => 'de'
				]
			]
		]);

		$setupIntents = Mockery::mock('overload:\Stripe\Service\SetupIntentService');
		$setupIntents->shouldReceive('create')->andReturn((object)[
			"client_secret" => "seti_123"
		]);

		// mock invoices
		$invoiceCollection = new StripeCollection();
		$invoice = (object)[
			"customer" => "cus_123",
			"customer_name" => "Adam Yauch",
			"customer_email" => "tester@hello-nasty.com",
			"subscription" => "sub_123",
			"total" => 1000,
			"currency" => "eur",
			"created" => Carbon::now()->timestamp,
			"paid" => Carbon::now()->timestamp,
			"invoice_pdf" => "https://stripe.com/invoice.pdf",
		];
		$invoiceCollection->data = new Collection([$invoice]);

		$invoices = Mockery::mock('overload:\Stripe\Service\InvoiceService');
		$invoices->shouldReceive('all')->andReturn($invoiceCollection);
	}


	protected function mockStripeSubscription(array $itemsData = null, array $data = []) {

		// create subscription
		$subscription = new \Stripe\Subscription("sub_123");
		$subscription->items = new StripeCollection();
		$subscription->status = 'active';
		$subscription->trial_end = null;
		$subscription->current_period_end = Carbon::now()->addDays(30)->timestamp;
		$subscription->default_payment_method = null;
		$subscription->collection_method = 'charge_automatically';
		$subscription->cancel_at = null;
		$subscription->ended_at = null;
		$subscription->trial_start = null;

		// subscription items
		$subscription->items->data = $itemsData ?? [
			(object) ["price" => (object)["id" => "price_123"]]
		];

		// subscription plan
		$subscription->plan = (object)[
			"amount" => 1000,
			"currency" => "eur",
		];

		// subscription data
		foreach ($data as $key => $value) {
			$subscription->{$key} = $value;
		}

		$subscriptions = Mockery::mock('overload:\Stripe\Service\SubscriptionService');
		$subscriptions->shouldReceive('retrieve')->andReturn($subscription);
		$subscriptions->shouldReceive('update')->andReturn($subscription);
		$subscriptions->shouldReceive('cancel')->andReturn($subscription);

		$paymentMethods = Mockery::mock('overload:\Stripe\Service\PaymentMethodService');
		$paymentMethods->shouldReceive('retrieve')->andReturn((object)[
			"id" => "pm_123"
		]);

		$customers = Mockery::mock('overload:\Stripe\Service\CustomerService');
		$customers->shouldReceive('retrieve')->andReturn((object)[
			"invoice_settings" => (object)['default_payment_method' => 'pm_123']
		]);

		/** @var \Stripe\Subscription $subscription **/
		return $subscription;
	}


	public function mockStripeSubscriptionUpdate($user, array $data = []) {

		// mock stripe cancellation
		$subscription = new \Stripe\Subscription("sub_123");
		$subscription->status = 'active';
		$subscription->current_period_end = Carbon::now()->addDays(30)->timestamp;

		foreach ($data as $key => $value) {
			$subscription->{$key} = $value;
		}

		$subscriptions = Mockery::mock('overload:\Stripe\Service\SubscriptionService');
		$subscriptions->shouldReceive('update')->andReturn($subscription);

		return $subscription;
	}


	public function createSubscription($user, array $data = []) {

		return DB::table('subscriptions')->insert([
			'user_id' => $user->id,
			'stripe_id' => 'sub_123',
			'stripe_status' => 'active',
			'type' => 'default',
			...$data
		]);
	}


	protected function mockStripeWebhook(array $object) {

		$webhook = Mockery::mock('overload:\Laravel\Cashier\Events\WebhookReceived');
		$webhook->payload = [
			'data' => ['object' => $object]
		];

		/** @var \Laravel\Cashier\Events\WebhookReceived $webhook **/
		return $webhook;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

