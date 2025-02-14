<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Models\App;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Support\Facades\Schema;

	// App
	use App\Models\App\Base\Page;
	use App\Models\App\Base\Item;
	use App\Models\App\Base\Fragment;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class PageTest extends TestCase {

	use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    SCHEMA
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_database_has_expected_columns() {

		$this->assertTrue(
		  Schema::hasColumns('pages', [
			...$this->getBaseProps(),
			...$this->getPublishedProps(),
			...$this->getPageProps(),
			$this->translateProp('title'),
		]), 1);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    PERMISSIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_existing_permissions_for_page() {

		// arrange
		$this->seed();

		// assert
		$this->assertDatabaseHas('permissions', ['name' => 'create pages']);
		$this->assertDatabaseHas('permissions', ['name' => 'edit pages']);
		$this->assertDatabaseHas('permissions', ['name' => 'delete pages']);
	}


	public function test_permissions_for_user() {

		$this->assertRoleNoPermission('user', 'create pages');
		$this->assertRoleNoPermission('user', 'edit pages');
		$this->assertRoleNoPermission('user', 'delete pages');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    FRAGMENT RELATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_has_public_fragment_relation() {

		// arrange
		$fragment = Fragment::factory()->public();
 		$page = Page::factory()->public()->has($fragment)->create();

		// act: add relation to model
		$page->load('fragments');
		$fragment = $page->fragments->first();
		$this->assertCount(1, $page->fragments);

		// assert: deleted model
		$deleted = $page->delete();
		$this->assertTrue($deleted);
		$this->assertDatabaseMissing('fragments', ['id' => $fragment->id]);
	}


	public function test_has_no_public_fragment_relation() {

		// arrange
 		$page = Page::factory()->public()->create();
		Fragment::factory()->public()->create(['parent_id'=>$page->id, 'parent_type'=>Page::class]);
		Fragment::factory()->create(['public'=>false, 'parent_id'=>$page->id, 'parent_type'=>Page::class]);

		// act/assert
		$page->load('fragments');
		$this->assertCount(1, $page->fragments);
	}


	public function test_has_fragment_relation_in_backend() {

		// arrange
		$this->loginAsAdmin();
 		$page = Page::factory()->public()->create();
		Fragment::factory()->public()->create(['parent_id'=>$page->id, 'parent_type'=>Page::class]);
		Fragment::factory()->create(['public'=>false, 'parent_id'=>$page->id, 'parent_type'=>Page::class]);

		// act
		request()->headers->set('x-context', 'backend');
		$page->load('fragments');

		// assert
		$this->assertCount(2, $page->fragments);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    ITEM RELATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_has_public_item_relation() {

		// arrange
		$page = Page::factory()->public()->hasItems(['public'=>true])->create();

		// assert: valid relation
		$this->assertCount(1, $page->items);
		$firstItem = $page->items->first();
		$this->assertEquals($firstItem->pivot->order, 0);

		// act: delete model
		$deleted = $page->delete();

		// assert: relation deleted
		$this->assertTrue($deleted);
		$this->assertDatabaseMissing('item_page', ['item_id' => $firstItem->id]);
	}


	public function test_has_no_public_item_relation() {

 		$page = Page::factory()->public()->create();
		$item1 = Item::factory()->public()->create();
		$item2 = Item::factory()->create(['public'=>false]);

		// add relation to model
		$page->items()->attach([$item1->id, $item2->id]);
		$this->assertCount(1, $page->items);
	}


	public function test_has_item_relation_in_backend() {

		// assert
		$this->loginAsAdmin();
 		$page = Page::factory()->public()->create();
		$item1 = Item::factory()->public()->create();
		$item2 = Item::factory()->create(['public'=>false]);

		// act: attach items
		$page->items()->attach([$item1->id, $item2->id]);
		request()->headers->set('x-context', 'backend');
		$page->load('items');

		// assert
		$this->assertCount(2, $page->items);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

} // end class
