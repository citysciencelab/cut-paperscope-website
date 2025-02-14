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
	use App\Models\App\Base\Item;
	use App\Models\App\Base\Page;
	use App\Models\App\Base\Fragment;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ItemTest extends TestCase {

	use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    SCHEMA
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_database_has_expected_columns() {

		$this->assertTrue(
		  Schema::hasColumns('items', [
			...$this->getBaseProps(),
			...$this->getPublishedProps(),
			'title',
			'richtext',
			'file',
		]), 1);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    PERMISSIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_existing_permissions_for_item() {

		// arrange
		$this->seed();

		// assert
		$this->assertDatabaseHas('permissions', ['name' => 'create items']);
		$this->assertDatabaseHas('permissions', ['name' => 'edit items']);
		$this->assertDatabaseHas('permissions', ['name' => 'delete items']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    PAGE RELATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_has_public_page_relation() {

		// arrange
 		$item = Item::factory()->public()->hasPages(['public'=>true])->create();

		// assert: valid relation
		$this->assertCount(1, $item->pages);
		$firstPage = $item->pages->first();
		$this->assertEquals($firstPage->pivot->order, 0);

		// act: delete model
		$deleted = $item->delete();

		// assert: relation deleted
		$this->assertTrue($deleted);
		$this->assertDatabaseMissing('item_page', ['page_id' => $firstPage->id]);
	}


	public function test_has_no_public_page_relation() {

		// arrange
 		$item = Item::factory()->public()->create();
		$page1 = Page::factory()->public()->create();
		$page2 = Page::factory()->create(['public'=>false]);

		// act: attach pages
		$item->pages()->attach([$page1->id, $page2->id]);
		$item->load('pages');

		// assert
		$this->assertCount(1, $item->pages);
	}


	public function test_has_page_relation_in_backend() {

		// arrange
		$this->loginAsAdmin();
 		$item = Item::factory()->public()->create();
		$page1 = Page::factory()->public()->create();
		$page2 = Page::factory()->create(['public'=>false]);

		// act: attach pages
		$item->pages()->attach([$page1->id, $page2->id]);
		request()->headers->set('x-context', 'backend');
		$item->load('pages');

		// assert
		$this->assertCount(2, $item->pages);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    FRAGMENT RELATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_has_public_fragment_relation() {

		// arrange
		$fragment = Fragment::factory()->public();
 		$item = Item::factory()->public()->has($fragment)->create();

		// act: add relation to model
		$item->load('fragments');
		$fragment = $item->fragments->first();
		$this->assertCount(1, $item->fragments);

		// assert: deleted model
		$deleted = $item->delete();
		$this->assertTrue($deleted);
		$this->assertDatabaseMissing('fragments', ['id' => $fragment->id]);
	}


	public function test_has_no_public_fragment_relation() {

		// arrange
 		$item = Item::factory()->public()->create();
		Fragment::factory()->public()->create(['parent_id'=>$item->id, 'parent_type'=>Item::class]);
		Fragment::factory()->create(['public'=>false, 'parent_id'=>$item->id, 'parent_type'=>Item::class]);

		// act/assert
		$item->load('fragments');
		$this->assertCount(1, $item->fragments);
	}


	public function test_has_fragment_relation_in_backend() {

		// arrange
		$this->loginAsAdmin();
 		$item = Item::factory()->public()->create();
		Fragment::factory()->public()->create(['parent_id'=>$item->id, 'parent_type'=>Item::class]);
		Fragment::factory()->create(['public'=>false, 'parent_id'=>$item->id, 'parent_type'=>Item::class]);

		// act
		request()->headers->set('x-context', 'backend');
		$item->load('fragments');

		// assert
		$this->assertCount(2, $item->fragments);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

} // end class
