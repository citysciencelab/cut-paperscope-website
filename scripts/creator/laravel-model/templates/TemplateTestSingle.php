	public function test_has_public_{{r_slug}}_relation() {

		// arrange
		${{r_slug}} = {{r_target}}::factory()->public()->create();
 		${{slug}} = {{ModelClass}}::factory()->public()->create([
			'{{r_slug}}_id' => ${{r_slug}}->id,
		]);

		// act
		${{slug}}->load('{{r_slug}}');

		// assert: valid relation
		$this->assertEquals(${{r_slug}}->id, ${{slug}}->{{r_slug}}->id);
	}


