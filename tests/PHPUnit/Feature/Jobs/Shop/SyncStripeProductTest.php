<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Jobs\Shop;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Mockery;

	// App
	use App\Jobs\Shop\SyncStripeProduct;
	use App\Models\Shop\Product;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class SyncStripeProductTest extends TestCase {

	use RefreshDatabase;


	protected function setUp(): void {

		parent::setUp();
		$this->skipIfNoShop();

		// skip if no cashier key
		if(!config('cashier.key')) { $this->markTestSkipped('No Stripe key set.'); }
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	STRIPE API
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_get_product_from_stripe() {

		// arrange
		$this->mockStripeProducts();
		$this->mockStripePrice();
		$product = Product::factory()->createOne([
			'stripe_id' => 'prod_123',
			'stripe_synced' => false,
		]);

		// act
		$job = new SyncStripeProduct($product);
		$job->handle();

		// assert: props
		$product = Product::find($product->id);
		$this->assertEquals($product->stripe_name, 'stripe name');
		$this->assertEquals($product->stripe_description, 'stripe description');
		$this->assertEquals($product->stripe_price_id, 'price_123');
		$this->assertEquals($product->stripe_price_amount, 123);
		$this->assertEquals($product->stripe_price_value, '1,23 €');
		$this->assertTrue($product->stripe_synced);
	}


	public function test_get_product_from_stripe_wrong_product() {

		// arrange: no stripe product
		$this->mockStripeProducts();
		$this->mockStripePrice(false);
		Log::shouldReceive('critical')->once();

		// arrange: model
		$product = Product::factory()->createOne([
			'stripe_id' => 'prod_123',
			'stripe_synced' => false,
		]);

		// act
		$job = new SyncStripeProduct($product);
		$result = $job->handle();

		// assert
		$product = Product::find($product->id);
		$this->assertFalse($product->stripe_synced);
	}


	public function test_get_product_from_stripe_wrong_price() {

		// arrange: no stripe price
		$this->mockStripeProducts();
		$this->mockStripePrice(false);
		Log::shouldReceive('critical')->once();

		// arrange: model
		$product = Product::factory()->createOne([
			'stripe_id' => 'prod_123',
			'stripe_synced' => false,
		]);

		// act
		$job = new SyncStripeProduct($product);
		$job->handle();

		// assert
		$product = Product::find($product->id);
		$this->assertFalse($product->stripe_synced);
	}


	public function test_no_stripe_product() {

		// arrange: no stripe product
		$stripeProducts = Mockery::mock('overload:\Stripe\Service\ProductService');
		$stripeProducts->shouldReceive('retrieve')->andReturn(null);
		Log::shouldReceive('critical')->once();

		// arrange: model
		$product = Product::factory()->createOne([
			'stripe_id' => 'prod_123',
			'stripe_synced' => false,
		]);

		// act
		$job = new SyncStripeProduct($product);
		$job->handle();

		// assert
		$product = Product::find($product->id);
		$this->assertFalse($product->stripe_synced);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FAILED
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_job_failed() {

		// arrange/assert
		Log::shouldReceive('critical')->with('Job failed: SyncStripeProduct. Model name: testname')->once();
		$product = Product::factory()->createOne(['name' => 'testname']);

		// act
		$job = new SyncStripeProduct($product);
		$job->failed(new \Exception('test exception'));
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MOCKS STRIPE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function mockStripeProducts() {

		$stripeProducts = Mockery::mock('overload:\Stripe\Service\ProductService');
		$stripeProducts->shouldReceive('retrieve')->andReturn((object)[
			'id' => 'prod_123',
			'name' => 'stripe name',
			'description' => 'stripe description',
			'default_price' => 'price_123',
		]);
	}


	protected function mockStripePrice(bool $isSuccess = true) {

		$stripePrices = Mockery::mock('overload:\Stripe\Service\PriceService');
		$stripePrices->shouldReceive('retrieve')->andReturn($isSuccess ? (object)[
			'id' => 'price_123',
			'unit_amount' => 123,
		] : null);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

