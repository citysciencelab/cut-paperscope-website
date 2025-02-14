<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Http\Requests\Backend\Base;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Support\Facades\Bus;

	// App
	use App\Models\App\Base\Page;
	use App\Models\App\Base\Item;
	use App\Jobs\Base\ProcessSharingUpload;
	use App\Jobs\Base\ProcessPageUpload;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class PageSaveRequestTest extends TestCase {

	use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    SAVE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_save_page() {

		// arrange
		Bus::fake();
		$this->loginAsEditor();

		// arrange: form data
		$formData = $this->getBaseFormData();
		$formData['navi_visible'] = true;
		$formData[$this->translateProp('title')] = 'test title';
		$formData[$this->translateProp('navi_label')] = 'test navi label';
		$formData[$this->translateProp('meta_title')] = 'test meta title';
		$formData[$this->translateProp('meta_description')] = 'test meta description';
		$formData[$this->translateProp('social_description')] = 'test social description';

		// arrange: relations
		$items = Item::factory()->count(2)->create()->toArray();
		$formData['items'] = $items;

		// act
		$page = $this->postData('/api/backend/page/save', $formData, $this->getBackendHeaders());

		// assert
		$this->assertEquals('page',$page['type']);
		$this->assertArraySubset($formData, $page);

		// assert: relations
		$page = Page::with('items')->find($page['id']);
		$this->assertEquals(count($items), $page['items']->count());
		$this->assertEquals($items[0]['id'], $page['items'][0]['id']);

		// assert: jobs
		Bus::assertDispatched(ProcessSharingUpload::class);
		Bus::assertDispatched(ProcessPageUpload::class);

		// assert: page in api content
		$content = $this->getData('/api/content/');
		$pages = $content['pages'];
		$this->assertEquals(1, count($pages));
		$this->assertEquals($page['id'], $pages[0]['id']);
	}


	private function getBaseFormData() {

		return [
			'name' => 'test name',
			'slug' => 'test-slug',
			'public' => true,
			'order' => 0,
			'published_start' => '1.2.2023 12:34',
		];
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

} // end class

