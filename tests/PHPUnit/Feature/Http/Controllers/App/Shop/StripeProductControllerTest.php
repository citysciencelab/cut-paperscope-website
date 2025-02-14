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
	use Illuminate\Support\Facades\DB;
	use Mockery;

	// App
	use App\Http\Controllers\App\Shop\StripeProductController;
	use App\Models\Shop\Product;
	use App\Models\Auth\User;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class StripeProductSessionStub {

	const OBJECT_NAME = 'checkout.session';
	const MODE_PAYMENT = 'payment';
}


class StripeProductControllerTest extends TestCase {

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

	 public function test_create_session_for_product() {

		// arrange
		$this->seed();
		$user = $this->loginAsUser();
		$this->mockStripeCheckout($user);
		$product = Product::first();

		// act
		$response = $this->get('/stripe/checkout/product/'.$product->id);

		// assert: redirect to stripe
		$response->assertRedirect();
		$this->assertStringContainsString('stripe.com', $response->getTargetUrl());
	}


	public function test_create_session_not_logged_in() {

		// arrange
		$this->seed();
		$product = Product::first();

		// act
		$response = $this->get('/stripe/checkout/product/'.$product->id);

		// assert: redirect to login
		$response->assertRedirect();
		$this->assertStringContainsString('login', $response->getTargetUrl());
	}


	public function test_create_session_invalid_product() {

		// arrange
		$this->seed();
		$this->loginAsUser();

		// act
		$response = $this->get('/stripe/checkout/product/73a46846-bcb8-493c-9639-5684571460b9');

		// assert: redirect to error page
		$response->assertRedirect('/404');
	}


	public function test_create_session_for_wrong_type() {

		// arrange
		$this->seed();
		$this->loginAsUser();
		$product = Product::first();

		// act
		$response = $this->get('/stripe/checkout/wrongtype/'.$product->id);

		// assert: response to default page
		$response->assertStatus(200);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CHECKOUT SUCCESS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	 public function test_checkout_success_new_product() {

		// arrange
		$this->seed();
		$user = $this->loginAsUser();
		$product = Product::first();
		$this->mockStripeClient($user,$product);

		// act
		$response = $this->get('/stripe/checkout/success/product');

		// assert: redirect to success page
		$response->assertRedirect('/product/'.$product->slug);

		// assert: data in product_user table
		$entry = DB::table('product_user')->where('product_id', $product->id)->first();
		$this->assertEquals($entry->status, 'succeeded');
		$this->assertEquals($entry->receipt, 'https://stripe.com/receipt/123');
		$this->assertEquals($entry->user_id, $user->id);

		// assert: new customer address
		$user = User::find($user->id);
		$address = $user->stripeAddress();
		$this->assertEquals($address['line1'], 'Musterstraße 123a');
		$this->assertEquals($address['postal_code'], '12345');
		$this->assertEquals($address['city'], 'Hamburg');
		$this->assertEquals($address['country'], 'de');
	}


	 public function test_checkout_success_existing_product() {

		// arrange
		$this->seed();
		$user = $this->loginAsUser();
		$product = Product::first();
		$this->mockStripeClient($user,$product);

		// act: add product to database first
		DB::table('product_user')->insert([
			'product_id' => $product->id,
			'user_id' => $user->id,
			'status' => 'failed',
			'receipt' => null
		]);

		// act
		$response = $this->get('/stripe/checkout/success/product');

		// assert: redirect to success page
		$response->assertRedirect('/product/'.$product->slug);

		// assert: data in product_user table
		$entry = DB::table('product_user')->where('product_id', $product->id)->first();
		$this->assertEquals($entry->status, 'succeeded');
		$this->assertEquals($entry->receipt, 'https://stripe.com/receipt/123');
		$this->assertEquals($entry->user_id, $user->id);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    WEBHOOKS PAYMENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	 public function test_webhook_onPaymentSuccess() {

		// arrange
		$this->seed();
		$product = Product::first();
		$user = $this->loginAsUser();

		// arrange: payment intent
		$paymentIntent = $this->getStripePaymentIntent($user,$product);
		$paymentIntent['mode'] = 'payment';
		$paymentIntent['payment_intent'] = 'pi_test_1234567890';

		// arrange: mock stripe
		$this->mockStripeClient($user,$product,$paymentIntent);
		$webhook = $this->mockStripeWebhook($paymentIntent);

		// act
		$controller = new StripeProductController();
		$response = $controller->onPaymentSuccess($webhook);

		// assert: response
		$this->assertEquals($response->getStatusCode(), 200);

		// assert: data in product_user table
		$entry = DB::table('product_user')->where('product_id', $product->id)->first();
		$this->assertEquals($entry->status, 'succeeded');
		$this->assertEquals($entry->receipt, 'https://stripe.com/receipt/123');
	}


	public function test_webhook_onAsyncPaymentSuccess() {

		// arrange
		$this->seed();
		$product = Product::first();
		$user = $this->loginAsUser();

		// arrange: payment intent
		$paymentIntent = $this->getStripePaymentIntent($user,$product);
		$paymentIntent['mode'] = 'payment';
		$paymentIntent['payment_intent'] = 'pi_test_1234567890';

		// arrange: mock stripe
		$this->mockStripeClient($user,$product,$paymentIntent);
		$webhook = $this->mockStripeWebhook($paymentIntent);

		// act
		$controller = new StripeProductController();
		$response = $controller->onAsyncPaymentSuccess($webhook);

		// assert: response
		$this->assertEquals($response->getStatusCode(), 200);

		// assert: data in product_user table
		$entry = DB::table('product_user')->where('product_id', $product->id)->first();
		$this->assertEquals($entry->status, 'succeeded');
		$this->assertEquals($entry->receipt, 'https://stripe.com/receipt/123');
	}


	public function test_webhook_onAsyncPaymentFailed() {

		// arrange
		$this->seed();
		$product = Product::first();
		$user = $this->loginAsUser();

		// arrange: add product to database first
		DB::table('product_user')->insert([
			'product_id' => $product->id,
			'user_id' => $user->id,
			'status' => 'succeeded',
			'receipt' => null
		]);

		// arrange: payment intent
		$paymentIntent = $this->getStripePaymentIntent($user,$product);
		$paymentIntent['mode'] = 'payment';
		$paymentIntent['payment_intent'] = 'pi_test_1234567890';

		// arrange: mock stripe
		$this->mockStripeClient($user,$product,$paymentIntent);
		$webhook = $this->mockStripeWebhook($paymentIntent);

		// act
		$controller = new StripeProductController();
		$response = $controller->onAsyncPaymentFailed($webhook);

		// assert: response
		$this->assertEquals($response->getStatusCode(), 200);

		// assert: data in product_user table
		$entry = DB::table('product_user')->where('product_id', $product->id)->first();
		$this->assertEmpty($entry);
	}


	public function test_webhook_onChargeRefunded() {

		// arrange
		$this->seed();
		User::flushEventListeners();
		$product = Product::first();
		$user = $this->loginAsUser(['stripe_id' => 'cus_test_1234567890']);

		// arrange: add product to database first
		DB::table('product_user')->insert([
			'product_id' => $product->id,
			'user_id' => $user->id,
			'status' => 'succeeded',
			'receipt' => null
		]);

		// arrange: webhook data
		$webhookData = [
			'customer' => 'cus_test_1234567890',
			'refunds' => [
				'data' => [
					[
						'charge' => 'ch_test_1234567890',
						'amount' => 1000,
					]
				]
			]
		];

		// arrange: mock stripe
		$this->mockStripeClient($user,$product);
		$webhook = $this->mockStripeWebhook($webhookData);
		// $charges = Mockery::mock('overload:\Stripe\Service\ChargeService');
		// $charges->shouldReceive('retrieve')->andReturn([
		// 	'amount' => 1000,
		// 	'metadata' => [
		// 		'user_id' => $user->id,
		// 		'product_id' => $product->id,
		// 		'product_type' => 'product',
		// 	]
		// ]);

		// act
		$controller = new StripeProductController();
		$response = $controller->onChargeRefunded($webhook);

		// assert: response
		$this->assertEquals($response->getStatusCode(), 200);

		// assert: data in product_user table
		$entry = DB::table('product_user')->where('product_id', $product->id)->first();
		$this->assertEmpty($entry);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    WEBHOOKS PRODUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_webhook_onProductUpdated() {

		// arrange
		Queue::fake();
		$this->seed();
		$product = Product::first();

		// arrange: webhook data
		$webhook = $this->mockStripeWebhook([
			'id' => $product->stripe_id,
			'active' => true,
		]);

		// act
		$controller = new StripeProductController();
		$response = $controller->onProductUpdated($webhook);

		// assert response
		$this->assertEquals($response->getStatusCode(), 200);

		// assert: job in queue
		Queue::assertPushed(\App\Jobs\Shop\SyncStripeProduct::class, function ($job) use ($product) {
			return $job->product->id === $product->id;
		});
	}


	public function test_webhook_onProductDeleted() {

		// arrange
		$this->seed();
		$product = Product::factory()->public()->create();
		$webhook = $this->mockStripeWebhook(['id' => $product->stripe_id]);

		// act
		$controller = new StripeProductController();
		$response = $controller->onProductDeleted($webhook);

		// arrange: response
		$this->assertEquals($response->getStatusCode(), 200);

		// arrange: product in database
		$product = Product::find($product->id);
		$this->assertFalse($product->public);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MOCKS STRIPE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function mockStripeCheckout($user) {

		$session = Mockery::mock('overload:\Stripe\Checkout\Session', StripeProductSessionStub::class);
		$session->ui_mode = 'hosted';
		$session->url = 'https://stripe.com/checkout';

		$customers = Mockery::mock('overload:\Stripe\Service\CustomerService');
		$customers->shouldReceive('create')->andReturn($user);
		$customers->shouldReceive('update')->andReturn($user);

		$sessions = Mockery::mock('overload:\Stripe\Service\Checkout\SessionService');
		$sessions->shouldReceive('create')->andReturn($session);
	}


	protected function mockStripeClient($user, $product, $paymentIntent = null) {

		$sessions = Mockery::mock('overload:\Stripe\Service\Checkout\SessionService');
		$sessions->shouldReceive('retrieve')->andReturn((object)[
			'payment_intent' => 'pi_test_1234567890',
		]);

		$paymentIntents = Mockery::mock('overload:\Stripe\Service\PaymentIntentService');
		$paymentIntents->shouldReceive('retrieve')->andReturn($paymentIntent ?? $this->getStripePaymentIntent($user,$product));

		$charges = Mockery::mock('overload:\Stripe\Service\ChargeService');
		$charges->shouldReceive('retrieve')->andReturn($this->getStripeCharge($user,$product));
	}


	protected function mockStripeWebhook(array $object) {

		$webhook = Mockery::mock('overload:\Laravel\Cashier\Events\WebhookReceived');
		$webhook->payload = ['data' => ['object' => $object]];

		/** @var \Laravel\Cashier\Events\WebhookReceived $webhook **/
		return $webhook;
	}


	protected function getStripePaymentIntent($user, $product) {

		return [
			'status' => 'succeeded',
			'metadata' => [
				'user_id' => $user->id,
				'product_id' => $product->id,
				'product_type' => 'product',
			],
			'latest_charge' => 'ch_test_1234567890',
		];
	}


	protected function getStripeCharge($user, $product) {

		return [
			'receipt_url' => 'https://stripe.com/receipt/123',
			'amount' => 1000,
			'billing_details' => [
				'address' => [
					'line1' => 'Musterstraße 123a',
					'postal_code' => '12345',
					'city' => 'Hamburg',
					'country' => 'de'
				]
			],
			'metadata' => [
				'user_id' => $user->id,
				'product_id' => $product->id,
				'product_type' => 'product',
			],
		];
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

