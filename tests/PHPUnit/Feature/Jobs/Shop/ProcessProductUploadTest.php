<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Jobs\Shop;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;

	// App
	use App\Jobs\Shop\ProcessProductUpload;
	use App\Models\Shop\Product;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ProcessProductUploadTest extends TestCase {

	use RefreshDatabase;


	protected function setUp(): void {

		parent::setUp();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    IMAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_image_processing() {

		// arrange
		$product = Product::factory()->createOne();
		$propImage = $this->translateProp('teaser_image');
		$product[$propImage] = $this->createImageFile();

		// act
		$job = new ProcessProductUpload($product);
		$job->handle();

		// assert
		$product = Product::find($product->id);
		$this->assertStringContainsString('image-desktop-', $product[$propImage]);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

