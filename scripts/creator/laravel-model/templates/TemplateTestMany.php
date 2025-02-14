	public function test_has_public_{{r_slug}}_relation() {

		// arrange
 		${{slug}} = {{ModelClass}}::factory()->public()->{{r_hasPlural}}(['public'=>true])->create();

		// assert: valid relation
		$this->assertCount(1, ${{slug}}->{{r_plural}});
		$first{{r_target}} = ${{slug}}->{{r_plural}}->first();
		$this->assertEquals($first{{r_target}}->pivot->order, 0);

		// act: delete model
		$deleted = ${{slug}}->delete();

		// assert: relation deleted
		$this->assertTrue($deleted);
		$this->assertDatabaseMissing('{{r_table}}', ['{{r_slug}}_id' => $first{{r_target}}->id]);
	}


	public function test_has_no_public_{{r_slug}}_relation() {

		// arrange
 		${{slug}} = {{ModelClass}}::factory()->public()->create();
		${{r_slug}}1 = {{r_target}}::factory()->public()->create();
		${{r_slug}}2 = {{r_target}}::factory()->create(['public'=>false]);

		// act: attach {{r_plural}}
		${{slug}}->{{r_plural}}()->attach([${{r_slug}}1->id, ${{r_slug}}2->id]);
		${{slug}}->load('{{r_plural}}');

		// assert
		$this->assertCount(1, ${{slug}}->{{r_plural}});
	}


	public function test_has_{{r_slug}}_relation_in_backend() {

		// arrange
		$this->loginAsAdmin();
 		${{slug}} = {{ModelClass}}::factory()->public()->create();
		${{r_slug}}1 = {{r_target}}::factory()->public()->create();
		${{r_slug}}2 = {{r_target}}::factory()->create(['public'=>false]);

		// act: attach {{r_plural}}
		${{slug}}->{{r_plural}}()->attach([${{r_slug}}1->id, ${{r_slug}}2->id]);
		request()->headers->set('x-context', 'backend');
		${{slug}}->load('{{r_plural}}');

		// assert
		$this->assertCount(2, ${{slug}}->{{r_plural}});
	}


