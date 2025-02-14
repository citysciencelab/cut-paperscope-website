<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Listeners;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Laravel\Cashier\Events\WebhookReceived;
	use Illuminate\Support\Facades\Log;
	use Mockery;

	// App
	use App\Listeners\StripeEventListener;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class StripeEventListenerTest extends TestCase {

	use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    WEBHOOKS CHECKOUT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	*/

	public function test_payment_intent_succeeded() {

		// arrange
		$webhook = $this->createWebhook('payment_intent.succeeded');
		$listener = new StripeEventListener();

		// act/arrange
		$controller = Mockery::mock('overload:App\Http\Controllers\App\Shop\StripeProductController');
		$controller->shouldReceive('onPaymentSuccess')->once();
		$listener->handle($webhook);
	}


	/**
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	*/

	public function test_checkout_session_async_payment_succeded() {

		// arrange
		$webhook = $this->createWebhook('checkout.session.async_payment_succeeded');
		$listener = new StripeEventListener();

		// act/arrange
		$controller = Mockery::mock('overload:App\Http\Controllers\App\Shop\StripeProductController');
		$controller->shouldReceive('onAsyncPaymentSuccess')->once();
		$listener->handle($webhook);
	}

	/**
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	*/


	public function test_checkout_session_async_payment_failed() {

		// arrange
		$webhook = $this->createWebhook('checkout.session.async_payment_failed');
		$listener = new StripeEventListener();

		// act/arrange
		$controller = Mockery::mock('overload:App\Http\Controllers\App\Shop\StripeProductController');
		$controller->shouldReceive('onAsyncPaymentFailed')->once();
		$listener->handle($webhook);
	}


	/**
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	*/

	public function test_checkout_setup_intent_succeded() {

		// arrange
		$webhook = $this->createWebhook('setup_intent.succeeded');
		$listener = new StripeEventListener();

		// act/arrange
		$controller = Mockery::mock('overload:App\Http\Controllers\App\Shop\StripeProductController');
		$controller->shouldReceive('onSetupIntentSucceeded')->once();
		$listener->handle($webhook);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    WEBHOOKS CHARGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	*/

	public function test_charge_refunded() {

		// arrange
		$webhook = $this->createWebhook('charge.refunded');
		$listener = new StripeEventListener();

		// act/arrange
		$controller = Mockery::mock('overload:App\Http\Controllers\App\Shop\StripeProductController');
		$controller->shouldReceive('onChargeRefunded')->once();
		$listener->handle($webhook);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    WEBHOOKS PRODUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	*/

	public function test_product_updated() {

		// arrange
		$webhook = $this->createWebhook('product.updated');
		$listener = new StripeEventListener();

		// act/arrange
		$controller = Mockery::mock('overload:App\Http\Controllers\App\Shop\StripeProductController');
		$controller->shouldReceive('onProductUpdated')->once();
		$listener->handle($webhook);
	}


	/**
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	*/

	public function test_product_deleted() {

		// arrange
		$webhook = $this->createWebhook('product.deleted');
		$listener = new StripeEventListener();

		// act/arrange
		$controller = Mockery::mock('overload:App\Http\Controllers\App\Shop\StripeProductController');
		$controller->shouldReceive('onProductDeleted')->once();
		$listener->handle($webhook);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    WEBHOOKS CUSTOMER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	*/

	public function test_customer_subscription_updated() {

		// arrange
		$webhook = $this->createWebhook('customer.subscription.updated');
		$listener = new StripeEventListener();

		// act/arrange
		$controller = Mockery::mock('overload:App\Http\Controllers\App\Shop\StripeSubscriptionController');
		$controller->shouldReceive('onSubscriptionUpdated')->once();
		$listener->handle($webhook);
	}


	/**
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	*/

	public function test_customer_subscription_deleted() {

		// arrange
		$webhook = $this->createWebhook('customer.subscription.deleted');
		$listener = new StripeEventListener();

		// act/arrange
		$controller = Mockery::mock('overload:App\Http\Controllers\App\Shop\StripeSubscriptionController');
		$controller->shouldReceive('onSubscriptionDeleted')->once();
		$listener->handle($webhook);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    WEBHOOKS DEFAULT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	*/

	public function test_webhook_default() {

		// arrange
		$webhook = $this->createWebhook('unknown.webhook');
		$listener = new StripeEventListener();

		// act/arrange
		Log::shouldReceive('info')->with('Unhandeld stripe webhook: unknown.webhook')->once();
		$listener->handle($webhook);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createWebhook($type) {

		return new WebhookReceived([
			'type' => $type,
		]);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

} // end class
