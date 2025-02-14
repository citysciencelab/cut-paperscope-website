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

	// App
	use App\Models\Shop\Product;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ProductControllerTest extends TestCase {

	use RefreshDatabase;


	protected function setUp(): void {

		parent::setUp();
		$this->skipIfNoShop();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MODEL LIST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_get_public_list() {

		// arrange
		$this->seed();

		// act
		$data = $this->getData('/api/product');

		// assert
		$this->assertIsArray($data);
		$this->assertEquals('product',$data[0]['type']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MODEL GET
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_get_public() {

		// arrange
		$product = Product::factory()->public()->create([
			'stripe_synced' => 			true,
			'stripe_id' => 				'prod_123',
			'stripe_price_id' => 		'price_123',
			'stripe_price_value' => 	'1,00 €',
			'stripe_price_amount' => 	100,
		]);

		// act
		$data = $this->getData('/api/product/'.$product->slug);

		// assert: properties
		$this->assertEquals('product',$data['type']);
		$this->assertEquals($product->id,$data['id']);
		$this->assertEquals($product->stripe_price_value,$data['stripe_price_value']);
		$this->assertEquals($product->stripe_price_amount,$data['stripe_price_amount']);

		// assert: paid properties not present in public response.
		$this->assertArrayNotHasKey('content',$data);
		$this->assertArrayNotHasKey('receipt',$data);
		$this->assertArrayNotHasKey('fragments',$data);
	}


	public function test_get_public_not_found() {

		// arrange
		$this->seed();

		// act and assert
		$this->getError('/api/product/3ec9fc3b-a102-40b6-bd61-743f8bd2403b');
	}


	public function test_get_public_without_stripe_sync() {

		// arrange
		$product = Product::factory()->public()->create(['stripe_synced' => false]);

		// act and assert
		$this->getError('/api/product/'.$product->slug);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	PAID PRODUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_paid_product_without_purchase() {

		// arrange
		$this->seed();
		$this->loginAsUser();
		$product = $this->createProduct();

		// act
		$response = $this->get('/api/product/'.$product->id.'/paid');

		// assert: no product before purchase
		$response->assertJson(['status'=>'error']);
	}


	public function test_paid_product_pending() {

		// arrange
		$this->seed();
		$user = $this->loginAsUser();
		$product = $this->createProduct();

		// act: purchase product with pending state
		/** @var \App\Models\Auth\User $user **/
		$user->products()->attach($product->id, ['status' => 'pending', 'receipt'=>'https://example.com/receipt']);

		// act: get product after purchase
		$response = $this->get('/api/product/'.$product->slug.'/paid');

		// assert: response
		$response->assertJson(['status'=>'success']);
		$response->assertStatus(202);

		// assert: no data in response if pending
		$data = $response->json('data');
		$this->assertEmpty($data);
	}


	public function test_paid_product_succeded() {

		// arrange
		$this->seed();
		$user = $this->loginAsUser();
		$product = $this->createProduct();

		// act: purchase product
		/** @var \App\Models\Auth\User $user **/
		$user->products()->attach($product->id, ['status' => 'succeeded', 'receipt'=>'https://example.com/receipt']);

		// act: get product after purchase
		$response = $this->get('/api/product/'.$product->slug.'/paid');

		// assert: response
		$response->assertJson(['status'=>'success']);
		$response->assertStatus(200);

		// assert: data in response
		$data = $response->getData('data');
		$this->assertEquals($product->id,$data['data']['id']);
	}


	public function test_paid_product_preview() {

		// arrange
		$this->seed();
		$user = $this->loginAsEditor();
		$product = $this->createProduct();

		// act
		$response = $this->get('/api/product/'.$product->slug.'/paid', [
			'X-Preview' => $user->id,
		]);

		// assert
		$response->assertJson(['status'=>'success']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createProduct() {

		return Product::factory()->public()->create([
			'stripe_synced' => 			true,
			'stripe_id' => 				'prod_123',
			'stripe_price_id' => 		'price_123',
			'stripe_price_value' => 	'1,00 €',
			'stripe_price_amount' => 	100,
		]);
	}


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

