<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Http\Resources;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Support\Str;
	use Illuminate\Http\Resources\MissingValue;

	// App
	use App\Models\App\Base\Item;
	use App\Models\App\Base\Page;
	use App\Http\Resources\Base\BaseModelResource;
	use App\Http\Resources\Base\ItemListResource;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class BaseModelResourceTest extends TestCase {

	use RefreshDatabase;


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    BASE PROPERTIES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_base_property_type() {

		// arrange
		$item = Item::factory()->create();
		$request = request();

		// act
		$resource = new BaseModelResource($item);
		$properties = $resource->getBaseProperties($request);

		// assert
		$this->assertEquals('item',$properties['type']);
	}


	public function test_base_property_skipping_name() {

		// arrange
		$item = Item::factory()->create();
		$request = request();

		// act
		$resource = new BaseModelResource($item);
		$properties = $resource->getBaseProperties($request);

		// assert
		$this->assertInstanceOf(MissingValue::class, $properties['name']);
	}


	public function test_base_property_name_in_backend() {

		// arrange
		$item = Item::factory()->create();
		$request = request();
		$request->headers->set('X-Context', 'backend');
		$request->headers->set('referer', 'http://localhost/backend/items');

		// act
		$resource = new BaseModelResource($item);
		$properties = $resource->getBaseProperties($request);

		// assert
		$this->assertEquals($item->name,$properties['name']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    BASE PROPERTIES PAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_base_property_page_properties() {

		config(['app.features.multi_lang' => false]);

		// arrange
		$page = Page::factory()->make(['navi_label'=>'test']);
		$request = request();

		// act
		$resource = new BaseModelResource($page);
		$properties = $resource->getBaseProperties($request);

		// assert
		$this->assertIsString($page->navi_label);
		$this->assertEquals($page->navi_label,$properties['navi_label']);
	}


	public function test_base_property_page_properties_multilang() {

		config(['app.features.multi_lang' => true]);

		// arrange
		$page = Page::factory()->make(['navi_label_de'=>'test de', 'navi_label_en'=>'test en']);
		$request = request();
		app()->setLocale('en');

		// act
		$resource = new BaseModelResource($page);
		$properties = $resource->getBaseProperties($request);

		// assert: property is MergeValue instead of string
		$this->assertArrayNotHasKey('test en',$properties);
	}


	public function test_base_property_social_properties() {

		config(['app.features.multi_lang' => false]);

		// arrange
		$page = Page::factory()->make([
			'social_description'=>'test description',
			'social_image'=> $this->getStorageUrl().'test.jpg',
		]);
		$request = request();

		// act
		$resource = new BaseModelResource($page);
		$properties = $resource->getBaseProperties($request);

		// assert
		$this->assertIsString($page->social_description);
		$this->assertIsString($page->social_image);
		$this->assertEquals($page->social_description,$properties['social_description']);
		$this->assertEquals($this->getStorageUrl().'test.jpg',$properties['social_image']);
	}


	public function test_base_property_social_properties_multilang() {

		config(['app.features.multi_lang' => true]);

		// arrange
		$page = Page::factory()->make([
			'social_description_de'=>'test description de',
			'social_description_en'=>'test description en',
			'social_image_de'=>'test_de.jpg',
			'social_image_en'=>'test_en.jpg',
		]);
		$request = request();
		app()->setLocale('en');

		// act
		$resource = new BaseModelResource($page);
		$properties = $resource->getBaseProperties($request);

		// assert: property is MergeValue instead of string
		$this->assertEquals('test description en',$properties['social_description']);
		$this->assertEquals('test_en.jpg',$properties['social_image']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HELPER INPUT RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_resource_many_relation() {

		// arrange
		$page = Page::factory()->create();
		$item1 = Item::factory()->public()->create();
		$item2 = Item::factory()->public()->create();
		$item3 = Item::factory()->public()->create();
		$page->items()->attach($item1, ['order'=>1]);
		$page->items()->attach($item2, ['order'=>2]);
		$page->items()->attach($item3, ['order'=>3]);
		$page->load('items');

		// act
		$resource = new BaseModelResource($page);
		$result = $resource->getManyRelation(ItemListResource::class, 'items');

		// assert
		$this->assertEquals($item1->id,$result[0]->id);
		$this->assertEquals($item2->id,$result[1]->id);
		$this->assertEquals($item3->id,$result[2]->id);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    TRANSLATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_resource_translation() {

		config(['app.features.multi_lang' => true]);

		// arrange
		$item = Item::factory()->create();
		$item->test_de = 'test name de';
		$item->test_en = 'test name en';
		app()->setLocale('en');

		// act
		$resource = new BaseModelResource($item);
		$result = $resource->translate('test');

		// assert
		$this->assertEquals('test name en',$result);
	}


	public function test_resource_translation_missing() {

		config(['app.features.multi_lang' => true]);

		// arrange
		$item = Item::factory()->create();
		$item->test = 'test name';
		app()->setLocale('fr');

		// act
		$resource = new BaseModelResource($item);
		$result = $resource->translate('test');

		// assert
		$this->assertEquals('test name',$result);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getStorageUrl() {

		$storageUrl = config('filesystems.disks.'.config('filesystems.default').'.url');
		return Str::finish($storageUrl, '/');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

} // end class

