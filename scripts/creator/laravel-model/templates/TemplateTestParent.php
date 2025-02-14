	public function test_has_public_parent_relation() {

		// arrange
		$item = Item::factory()->public()->create();
		${{slug}} = {{ModelClass}}::factory()->create([
			'public' => true,
			'parent_id' => $item->id,
			'parent_type' => Item::class
		]);

		// act
		${{slug}}->load('parent');

		// assert
		$this->assertInstanceOf(Item::class, ${{slug}}->parent);
	}


	public function test_has_no_public_parent_relation() {

		// arrange
 		$item = Item::factory()->create(['public'=>false]);
		${{slug}} = {{ModelClass}}::factory()->create([
			'public' => true,
			'parent_id' => $item->id,
			'parent_type' => Item::class
		]);

		// act
		${{slug}}->load('parent');

		// assert
		$this->assertNull(${{slug}}->parent);
	}


	public function test_has_parent_relation_in_backend() {

		// arrange
		$this->loginAsAdmin();
 		$item = Item::factory()->create(['public'=>false]);
		${{slug}} = {{ModelClass}}::factory()->create([
			'public' => true,
			'parent_id' => $item->id,
			'parent_type' => Item::class
		]);

		// act
		request()->headers->set('x-context', 'backend');
		${{slug}}->load('parent');

		// assert
		$this->assertInstanceOf(Item::class, ${{slug}}->parent);
	}


