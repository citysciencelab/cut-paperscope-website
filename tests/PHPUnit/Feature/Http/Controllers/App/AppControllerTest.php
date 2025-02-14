<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Http\Controllers\App;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Support\Carbon;

	// App
	use App\Http\Controllers\App\AppController;
	use App\Models\App\Base\Item;
	use App\Models\App\Base\Page;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class AppControllerTest extends TestCase {

    use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    VIEWS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_show_index_view() {

		// act
		$response = $this->get('/');

		// assert: config variable exists in view
		$response->assertViewHas('config', function(array $config) {
			return $config['app_name'] === config('app.name');
		});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    USER LANGUAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_show_index_view_redirect_user_language() {

		// act
		$this->loginAsUser(['lang'=>'en']);

		// get index view
		$response = $this->get('/');

		// assert
		$response->assertRedirect('/en');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    META TAGS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_meta_data_for_index_route() {

		// act
		$response = $this->get('/');

		// assert: meta variable exists in view
		$response->assertViewHas('meta', function(array $meta) {
			return $meta['og:site_name'] === config('app.name');
		});
	}


	public function test_default_meta_tags_in_html() {

		// act
		$response = $this->get('/');

		// assert: default meta tags exists
		$response->assertSee('<title>'.config('app.name').' | '.__('Startseite'),false);
		$response->assertSee('<meta name="description" content="',false);
		$response->assertSee('<link rel="canonical" href="',false);

		// assert: noindex tag if not in production
		$response->assertSee('<meta name="robots" content="noindex,nofollow">',false);
	}


	public function test_meta_tags_in_html_for_dynamic_page() {

		// arrange
		$page = Page::create([
			'name' => 'Test Page',
			'slug' => 'test-page',
			'public' => true,
			'published_start' => Carbon::now()->subWeek(),
			($this->translateProp('meta_title')) => 'Meta Title DE',
		]);

		// act
		$response = $this->get('/test-page', ['Accept-Language' => 'de']);

		// assert: german meta tags exist
		$response->assertSee('<title>'.config('app.name').' | Meta Title DE',false);

		// check english meta tags
		if($this->hasFeatureMultiLang()) {
			$page->update([$this->translateProp('meta_title', 'en') => 'Meta Title EN']);
			$response = $this->get('/test-page', ['Accept-Language' => 'en']);
			$response->assertSee('<title>'.config('app.name').' | Meta Title EN',false);
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MODEL LIST FILTERS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_model_list_with_filter_order() {

		// arrange
		$this->seed();

		// act: get all items in different order
		$itemsNew = $this->getData('/api/item');
		$itemsOld = $this->getData('/api/item', ['order'=>'oldest']);

		// assert: all items in reverse order
		$len = count($itemsNew);
		$this->assertEquals($itemsNew[0]['id'],$itemsOld[$len-1]['id']);
		$this->assertEquals($itemsNew[$len-1]['id'],$itemsOld[0]['id']);
	}


	public function test_model_list_with_filter_types_unavailable() {

		// arrange
		$this->seed();

		// act
		$response = $this->getWithParams('/api/item', ['types'=>['video']]);

		// assert: data is empty
		$data = $response->json('data');
		$this->assertIsArray($data);
		$this->assertEmpty($data);
	}


	public function test_model_list_with_filter_tags_unavailable() {

		// arrange
		$this->seed();

		// act
		$response = $this->getWithParams('/api/item', ['tags'=>['tag1', 'tag2']]);

		// assert: data ist not filtered by tags
		$data = $response->json('data');
		$this->assertIsArray($data);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MODEL GET
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_get_model_by_id() {

		// arrange
		$item = Item::factory()->public()->create();

		// act
		$controller = new AppController();
		$response = $controller->getPublic($item->id);
		$data = $response->getData('data');

		// assert: correct id
		$this->assertEquals($item->id,$data['data']['id']);
	}


	public function test_model_get_by_slug() {

		// arrange
		$item = Item::factory()->public()->create();

		// act
		$controller = new AppController();
		$response = $controller->getPublicBySlug($item->slug);
		$data = $response->getData('data');

		// assert: correct slug
		$this->assertEquals($item->slug,$data['data']['slug']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

