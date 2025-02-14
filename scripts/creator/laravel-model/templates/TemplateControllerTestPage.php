/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MODEL PREVIEW
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_get_preview() {

		$user = $this->loginAsEditor();

		// arrange
		${{slug}} = {{ModelClass}}::factory()->create(['public' => false,'published_start' => '2099-01-01 00:00:00']);
		Fragment::factory()->create(['public' => true, 'parent_id' => ${{slug}}->id,'parent_type' => {{ModelClass}}::class]);
		Fragment::factory()->create(['public' => false, 'parent_id' => ${{slug}}->id,'parent_type' => {{ModelClass}}::class]);

		// act
		$data = $this->getData('/api/{{slug}}/'.${{slug}}->slug, [], [
			'X-Preview' => $user->id,
		]);

		// assert: response is {{slug}} with both fragments
		$this->assertEquals('{{slug}}',$data['type']);
		$this->assertEquals(${{slug}}->id,$data['id']);
		$this->assertCount(2,$data['fragments']);
	}


	public function test_user_not_allowed_to_get_preview() {

		$user = $this->loginAsUser();

		// arrange
		${{slug}} = {{ModelClass}}::factory()->create([ 'public' => false, ]);

		// act
		$response = $this->get('/api/{{slug}}/'.${{slug}}->slug, [
			'X-Preview' => $user->id,
		]);

		// assert
		$response->assertJson(['status'=>'error']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	META TAGS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_meta_tags_in_html() {

		// arrange
		${{slug}} = {{ModelClass}}::factory()->public()->create();

		// act
		$response = $this->get(${{slug}}->slug);

		// assert: correct meta tags
		$response->assertSee('<meta name="description" content="'.${{slug}}['meta_description'],false);
		$response->assertSee('<meta property="og:description" content="'.${{slug}}['social_description'],false);
	}


