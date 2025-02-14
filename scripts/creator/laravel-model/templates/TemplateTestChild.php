	public function test_has_public_fragment_relation() {

		// arrange
		$fragment = Fragment::factory()->public();
 		${{slug}} = {{ModelClass}}::factory()->public()->has($fragment)->create();

		// act: add relation to model
		${{slug}}->load('fragments');
		$fragment = ${{slug}}->fragments->first();
		$this->assertCount(1, ${{slug}}->fragments);

		// assert: deleted model
		$deleted = ${{slug}}->delete();
		$this->assertTrue($deleted);
		$this->assertDatabaseMissing('fragments', ['id' => $fragment->id]);
	}


	public function test_has_no_public_fragment_relation() {

		// arrange
 		${{slug}} = {{ModelClass}}::factory()->public()->create();
		Fragment::factory()->public()->create(['parent_id'=>${{slug}}->id, 'parent_type'=>{{ModelClass}}::class]);
		Fragment::factory()->create(['public'=>false, 'parent_id'=>${{slug}}->id, 'parent_type'=>{{ModelClass}}::class]);

		// act/assert
		${{slug}}->load('fragments');
		$this->assertCount(1, ${{slug}}->fragments);
	}


	public function test_has_fragment_relation_in_backend() {

		// arrange
		$this->loginAsAdmin();
 		${{slug}} = {{ModelClass}}::factory()->public()->create();
		Fragment::factory()->public()->create(['parent_id'=>${{slug}}->id, 'parent_type'=>{{ModelClass}}::class]);
		Fragment::factory()->create(['public'=>false, 'parent_id'=>${{slug}}->id, 'parent_type'=>{{ModelClass}}::class]);

		// act
		request()->headers->set('x-context', 'backend');
		${{slug}}->load('fragments');

		// assert
		$this->assertCount(2, ${{slug}}->fragments);
	}


