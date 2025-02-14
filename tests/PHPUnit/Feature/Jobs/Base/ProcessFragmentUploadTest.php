<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Jobs\Base;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;

	// App
	use App\Jobs\Base\ProcessFragmentUpload;
	use App\Models\App\Base\Fragment;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ProcessFragmentUploadTest extends TestCase {

	use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    TEXT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_empty_text_processing() {

		// arrange
		$prop = $this->translateProp('content');
		$data = [
			'template' => 'text',
			($prop) => null
		];
		$fragment = Fragment::factory()->createOne($data);

		// act
		$job = new ProcessFragmentUpload($fragment);
		$job->handle();

		// assert
		$fragment = Fragment::find($fragment->id);
		$this->assertEquals("{}", $fragment[$prop]);
	}


	public function test_text_processing_with_inline_images() {

		// arrange: data
		$image = $this->createImageFile();
		$propContent = $this->translateProp('content');
		$propCopy = $this->translateProp('copy');
		$data = [
			'template' => 'text',
			($propContent) => json_encode([$propCopy => '<p><img src="'.$image.'"/></p>'])
		];

		// arrange: model
		$fragment = Fragment::factory()->createOne($data);

		// act
		$job = new ProcessFragmentUpload($fragment);
		$job->handle();

		// assert
		$fragment = Fragment::find($fragment->id);
		$content = $fragment[$propContent];
		$this->assertStringContainsString('test-desktop-hr.jpg',$content);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    IMAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_text_image_processing() {

		// arrange: data
		$image = $this->createImageFile();
		$propContent = $this->translateProp('content');
		$propCopy = $this->translateProp('copy');
		$propImage = $this->translateProp('image');
		$data = [
			'template' => 'text-image',
			($propContent) => json_encode([$propCopy => '<p>test-text</p>', $propImage => $image])
		];

		// arrange: model
		$fragment = Fragment::factory()->createOne($data);

		// act
		$job = new ProcessFragmentUpload($fragment);
		$job->handle();

		// assert
		$fragment = Fragment::find($fragment->id);
		$content = $fragment[$propContent];
		$this->assertStringContainsString("<p>test-text<\/p>", $content);
		$this->assertStringContainsString(str_replace('_','-',$propImage).'-desktop-hr.jpg', $content);
	}


	public function test_no_gif_processing() {

		// arrange: data
		$image = $this->createImageFile("test.gif");
		$propContent = $this->translateProp('content');
		$propCopy = $this->translateProp('copy');
		$propImage = $this->translateProp('image');
		$data = [
			'template' => 'text-image',
			($propContent) => json_encode([$propCopy => '<p>test-text</p>', $propImage => $image])
		];

		// arrange: model
		$fragment = Fragment::factory()->createOne($data);

		// act
		$job = new ProcessFragmentUpload($fragment);
		$job->handle();

		// assert
		$fragment = Fragment::find($fragment->id);
		$content = $fragment[$propContent];
		$this->assertStringContainsString('image.gif', $content);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    IMAGE SLIDER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_slider_image_processing() {

		// arrange: data
		$image = $this->createImageFile();
		$propContent = $this->translateProp('content');
		$propImage = $this->translateProp('image');
		$data = [
			'template' => 'slider-image',
			($propContent) => json_encode([
				'items' => [
					['id'=>'123', $propImage=>$image],
					['id'=>'456', $propImage=>$image],
				]
			])
		];

		// arrange: model
		$fragment = Fragment::factory()->createOne($data);

		// act
		$job = new ProcessFragmentUpload($fragment);
		$job->handle();

		// assert
		$fragment = Fragment::find($fragment->id);
		$content = $fragment[$propContent];
		$content = json_decode($content);
		$this->assertObjectHasProperty('items',$content);

		// assert: items
		$items = $content->items;
		$this->assertIsArray($items);
		$this->assertEquals(2,count($items));

		// assert: slider item
		$item = $items[1];
		$this->assertEquals('456', $item->id);
		$this->assertStringContainsString('image-456-de-desktop-hr.jpg', $item->{$propImage});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    VIDEO
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_video_processing() {

		// arrange: data
		$propContent = $this->translateProp('content');
		$data = ['template' => 'video'];
		$data[$propContent] = json_encode([
			($this->translateProp('poster_desktop')) => $this->createImageFile('poster-desktop.jpg'),
			($this->translateProp('poster_mobile')) => $this->createImageFile('poster-mobile.jpg'),
			($this->translateProp('video_desktop')) => $this->createImageFile('video-desktop.jpg'),
			($this->translateProp('video_mobile')) => $this->createImageFile('video-mobile.jpg'),
		]);

		// arrange: model
		$fragment = Fragment::factory()->createOne($data);

		// act
		$job = new ProcessFragmentUpload($fragment);
		$job->handle();

		// assert
		$fragment = Fragment::find($fragment->id);
		$content = $fragment[$propContent];

		// assert: images
		$this->assertStringContainsString('poster-desktop', $content);
		$this->assertStringContainsString('poster-mobile', $content);
		$this->assertStringContainsString('video-desktop', $content);
		$this->assertStringContainsString('video-mobile', $content);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

