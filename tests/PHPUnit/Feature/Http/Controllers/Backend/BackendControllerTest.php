<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Http\Controllers\Backend;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Spatie\Permission\Models\Role;

	// App
	use App\Models\App\Base\Page;
	use App\Models\App\Base\Item;
	use App\Http\Controllers\Backend\BackendController;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class BackendControllerTest extends TestCase {

	use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    VIEWS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_show_index_view() {

		// act
		$response = $this->get('/backend');

		// assert: config variable in view
		$response->assertViewHas('config', function(array $config) {
			return $config['app_name'] === config('app.name');
		});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MODEL LIST FILTERS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_model_list_with_filter_order() {

		// arrange
		$this->seed();
		$this->loginAsAdmin();

		// act: get all items in different order
		$itemsNew = $this->postData('/api/backend/item');
		$itemsOld = $this->postData('/api/backend/item', ['order'=>'oldest']);

		// assert: all items in reverse order
		$len = count($itemsNew);
		$this->assertEquals($itemsNew[0]['id'],$itemsOld[$len-1]['id']);
		$this->assertEquals($itemsNew[$len-1]['id'],$itemsOld[0]['id']);
	}


	public function test_model_list_with_filter_direction() {

		// arrange
		$this->loginAsAdmin();

		// arrange: items
		Item::factory()->create(['title'=>'a']);
		Item::factory()->create(['title'=>'b']);
		Item::factory()->create(['title'=>'c']);

		// act: order by title
		$items = $this->postData('/api/backend/item', [
			'direction_property'=>'title',
			'direction'=>'desc',
		]);

		// assert
		$this->assertEquals('c',$items[0]['title']);
		$this->assertEquals('b',$items[1]['title']);
		$this->assertEquals('a',$items[2]['title']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MODEL GET
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_model_get_by_id() {

		// arrange
		$this->loginAsAdmin();
		$item = Item::factory()->public()->create();

		// act
		$controller = new BackendController();
		$response = $controller->getBackend($item->id);
		$data = $response->getData('data');

		// assert: correct id
		$this->assertEquals($item->id,$data['data']['id']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MODEL DELETE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_delete_model() {

		// arrange
		$this->loginAsAdmin();
		$item = Item::factory()->create(['public'=>false]);

		// act
		$response = $this->post('/api/backend/item/delete/', ['id'=>$item->id],  $this->getBackendHeaders());

		// assert: response
		$response->assertStatus(200);
		$response->assertJson(['status'=>'success']);

		// assert: model deleted
		$this->assertNull(Item::find($item->id));
	}


	public function test_no_permission_to_delete_model() {

		// arrange
		$this->seed();
		$this->loginAsEditor();

		// arrange: remove delete permission from editor role
		$role = Role::findByName('editor');
		$role->revokePermissionTo('delete items');

		// act
		$item = Item::first();
		$response = $this->post('/api/backend/item/delete/', ['id'=>$item->id], $this->getBackendHeaders());

		// assert
		$response->assertStatus(403);
	}


	public function test_delete_missing_model() {

		// arrange
		$this->loginAsAdmin();

		// act
		$response = $this->post('/api/backend/item/delete/', ['id'=>'8c21d36a-8d16-434c-b377-1d46c9c6255a'], $this->getBackendHeaders());

		// assert
		$response->assertStatus(404);
		$response->assertJson(['status'=>'error']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MODEL SEARCH
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_model_search() {

		// arrange
		$this->seed();
		$this->loginAsAdmin();
		$page = Page::first();

		// act
		$data = $this->postData('/api/backend/page/search', ['value'=>$page->name], $this->getBackendHeaders());

		// assert
		$this->assertIsArray($data);
	}


	public function test_model_search_with_pagination() {

		// arrange
		$this->seed();
		$this->loginAsAdmin();

		// act
		$response = $this->post('/api/backend/item/search', ['value'=>'Intergalactic'], $this->getBackendHeaders());
		$response->assertJson(['status'=>'success']);
		$data = $response->json('data');

		// assert: pagination
		$this->assertIsArray($data);
		$response->assertJson(['currentPage'=>1]);
	}


	public function test_model_search_with_filter_directions() {

		// arrange
		$this->seed();
		$this->loginAsAdmin();

		// act
		$response = $this->post('/api/backend/item/search', [
			'value'=>'Intergalactic',
			'direction_property'=>'title',
			'direction'=>'desc'
		], $this->getBackendHeaders());

		$response->assertJson(['status'=>'success']);
		$data = $response->json('data');

		// assert
		$this->assertIsArray($data);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MODEL SORT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_sort_model() {

		// arrange
		$this->seed();
		$this->loginAsAdmin();

		// arrange: clear items
		$items = Item::all();
		foreach($items as $item) { $item->delete(); }

		// arrange: create 3 new items
		$data = [];
		for($i=0; $i<3; $i++) {
			$data[] = Item::factory()->createOne(['public'=>false]);
		}

		// act: sort items
		$response = $this->post('/api/backend/item/sort', ['items'=>[
			$data[2]['id'] => 0,
			$data[1]['id'] => 1,
			$data[0]['id'] => 2,
		]], $this->getBackendHeaders());

		// assert: response
		$response->assertStatus(200);
		$response->assertJson(['status'=>'success']);

		// assert: order
		$newOrder = $this->postData('/api/backend/item');
		$this->assertEquals($data[2]['id'], $newOrder[0]['id']);
		$this->assertEquals($data[1]['id'], $newOrder[1]['id']);
		$this->assertEquals($data[0]['id'], $newOrder[2]['id']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    SAVE HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_method_getInputJson() {

		// act
		$controller = new BackendController();
		$json = $controller->getInputJson(['test' => 'test']);

		// assert
		$this->assertEquals("{\"test\":\"test\"}", $json);
	}

	public function test_method_getInputJson_invalid_data() {

		// act
		$controller = new BackendController();
		$json = $controller->getInputJson('');

		// assert
		$this->assertEquals("{}", $json);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

