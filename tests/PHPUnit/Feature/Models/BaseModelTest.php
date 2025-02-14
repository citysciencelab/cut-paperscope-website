<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Models;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Support\Carbon;
	use Illuminate\Support\Facades\Storage;
	use Intervention\Image\Laravel\Facades\Image;

	// Models
	use App\Models\BaseModel;
	use App\Models\App\Base\Item;
	use App\Models\App\Base\Page;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class BaseModelTest extends TestCase {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    PROPERTIES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_properties_with_slug_enabled() {

		// arrange
		$model = new BaseModel();
		$model::$useSlug = true;

		// act
		$model->mergeFillable([]);
		$fillable = $model->getFillable();

		// assert
		$this->assertContains('slug', $fillable);
	}


	public function test_properties_with_slug_disabled() {

		$model = new BaseModel();
		$model::$useSlug = false;

		$model->mergeFillable([]);
		$fillable = $model->getFillable();

		$this->assertNotContains('slug', $fillable);
	}


	public function test_properties_with_published_enabled() {

		// arrange
		$model = new BaseModel();
		$model::$usePublished = true;

		// act
		$model->mergeFillable([]);
		$fillable = $model->getFillable();
		$casts = $model->getCasts();

		// assert
		$this->assertContains('published_start', $fillable);
		$this->assertContains('published_end', $fillable);
		$this->assertArrayHasKey('published_start', $casts);
		$this->assertArrayHasKey('published_end', $casts);
	}


	public function test_properties_with_published_disabled() {

		// arrange
		$model = new BaseModel();
		$model::$usePublished = false;

		// act
		$model->mergeFillable([]);
		$fillable = $model->getFillable();
		$casts = $model->getCasts();

		// assert
		$this->assertNotContains('published_start', $fillable);
		$this->assertNotContains('published_end', $fillable);
		$this->assertArrayNotHasKey('published_start', $casts);
		$this->assertArrayNotHasKey('published_end', $casts);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    TRANSLATION PROPERTIES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_properties_with_translations_enabled() {

		config(['app.features.multi_lang' => true]);

		// arrange
		$model = new BaseModel();
		$model->mergeFillable([]);
		$fillable = $model->getFillable();

		// assert: default properties
		$this->assertNotContains('meta_title', $fillable);
		$this->assertNotContains('meta_description', $fillable);
		$this->assertNotContains('social_description', $fillable);
		$this->assertNotContains('social_image', $fillable);

		// assert: translated meta properties
		$this->assertContains('meta_title_de', $fillable);
		$this->assertContains('meta_title_en', $fillable);
		$this->assertContains('meta_description_de', $fillable);
		$this->assertContains('meta_description_en', $fillable);

		// assert: translated social properties
		$this->assertContains('social_description_de', $fillable);
		$this->assertContains('social_description_en', $fillable);
		$this->assertContains('social_image_de', $fillable);
		$this->assertContains('social_image_en', $fillable);
	}


	public function test_properties_with_translations_disabled() {

		config(['app.features.multi_lang' => false]);

		// arrange
		$model = new BaseModel();
		$model->mergeFillable([]);
		$fillable = $model->getFillable();

		// assert: default properties
		$this->assertContains('meta_title', $fillable);
		$this->assertContains('meta_description', $fillable);
		$this->assertContains('social_description', $fillable);
		$this->assertContains('social_image', $fillable);

		// assert: translated meta properties
		$this->assertNotContains('meta_title_de', $fillable);
		$this->assertNotContains('meta_title_en', $fillable);
		$this->assertNotContains('meta_description_de', $fillable);
		$this->assertNotContains('meta_description_en', $fillable);

		// assert: translated social properties
		$this->assertNotContains('social_description_de', $fillable);
		$this->assertNotContains('social_description_en', $fillable);
		$this->assertNotContains('social_image_de', $fillable);
		$this->assertNotContains('social_image_en', $fillable);
	}


	public function test_get_translation_properties() {

		// arrange
		$model = new Page();
		$properties = $model->getTranslationProps();

		// assert
		$this->assertContains('title', $properties);
		$this->assertContains('navi_label', $properties);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    STORAGE PROPERTIES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_property_storage_disk() {

		// arrange
		$model = new BaseModel();
		$storage = config('filesystems.default');

		// assert
		$this->assertEquals($storage, $model->storageDisk);
	}


	public function test_property_storage_folder() {

		// arrange
		$model = new BaseModel();
		$model->created_at = Carbon::now();

		// assert
		$this->assertNotEmpty($model->storageFolder);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_get_single_relation() {

		// arrange
		$item = Item::factory()->create();
		$relation = $item->getSingleRelation(Page::class);

		// assert
		$this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    DELETE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_delete() {

		// arrange
		$storage = Storage::disk(config('filesystems.default'));

		// arrange: prepare file for item
		$item = Item::factory()->create();
		$image = Image::create(100, 100)->fill('#ff0000');
		$file = $item->storageFolder.'test.jpg';
		$storage->put($file, $image->toJpeg());
		$this->assertTrue($storage->exists($file));

		// act
		$item->delete();

		// assert
		$this->assertFalse($storage->exists($file));
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

} // end class
