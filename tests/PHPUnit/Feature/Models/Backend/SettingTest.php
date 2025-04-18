<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Models\Backend;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Support\Facades\Schema;

	// Models
	use App\Models\App\Setting;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class SettingTest extends TestCase {

	use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    SCHEMA
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_database_has_expected_columns() {

		$this->assertTrue(
		  Schema::hasColumns('settings', [
			...$this->getBasePropsNoSlug(),
			'data_type',
			'category',
			$this->translateProp('content'),
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
		$this->assertDatabaseHas('permissions', ['name' => 'create settings']);
		$this->assertDatabaseHas('permissions', ['name' => 'edit settings']);
		$this->assertDatabaseHas('permissions', ['name' => 'delete settings']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

} // end class
