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
	use Illuminate\Support\Facades\DB;
	use Stripe\Checkout\Session;
	use Mockery;

	// App
	use App\Http\Controllers\App\Shop\StripeCustomerController;
	use App\Models\Auth\User;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class StripeCustomerControllerTest extends TestCase {

	use RefreshDatabase;


	protected function setUp(): void {

		parent::setUp();
		$this->skipIfNoShop();

		// skip if no cashier key
		if (!config('cashier.key')) { $this->markTestSkipped('No Stripe key set.'); }

		User::unsetEventDispatcher();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    WEBHOOK UPDATED
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_webhook_onCustomerUpdated() {

		// arrange
		$user = $this->createUser(['stripe_id' => 'cus_123']);
		$webhook = $this->mockStripeWebhook($user);

		// act
		$controller = new StripeCustomerController();
		$response = $controller->onCustomerUpdated($webhook);

		// assert: response
		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertEquals($response->getData()->message, 'User updated');
	}


	public function test_webhook_onCustomerUpdated_no_customer() {

		// arrange
		$webhook = $this->mockStripeWebhook(new User());

		// act
		$controller = new StripeCustomerController();
		$response = $controller->onCustomerUpdated($webhook);

		// assert: response
		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertEquals($response->getData()->message, 'User not found');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    WEBHOOK DELETED
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_webhook_onCustomerDeleted() {

		// arrange
		$user = $this->createUser(['stripe_id' => 'cus_123']);
		$webhook = $this->mockStripeWebhook($user);

		// act
		$controller = new StripeCustomerController();
		$response = $controller->onCustomerDeleted($webhook);

		// assert: response
		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertEquals($response->getData()->message, 'Customer deleted');

		// assert: user
		$user = User::find($user->id);
		$this->assertNull($user->stripe_id);
	}


	public function test_webhook_onCustomerDeleted_no_customer() {

		// arrange
		$webhook = $this->mockStripeWebhook(new User());

		// act
		$controller = new StripeCustomerController();
		$response = $controller->onCustomerDeleted($webhook);

		// assert: response
		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertEquals($response->getData()->message, 'User not found');
	}




/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MOCKS STRIPE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */



	protected function mockStripeWebhook(User $user) {

		$webhook = Mockery::mock('overload:\Laravel\Cashier\Events\WebhookReceived');
		$webhook->payload = [
			'data' => [
				'object' => [
					'id' => $user->stripe_id
				]
			]
		];

		/** @var \Laravel\Cashier\Events\WebhookReceived $webhook **/
		return $webhook;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

