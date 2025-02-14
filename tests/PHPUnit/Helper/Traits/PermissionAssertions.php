<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Helper\Traits;

	// Laravel
	use Illuminate\Support\Facades\DB;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    TRAIT DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


trait PermissionAssertions {


	/**
	 * Assert that the given user role has the given permission.
	 *
	 * @param string $userRole
	 * @param string $permission permission must include verb and noun, e.g. 'create users'
	 */

	public function assertRoleHasPermission(string $userRole, string $permission) : void {

		$roleId = DB::table('roles')->where('name', $userRole)->value('id');
		$permissionId = DB::table('permissions')->where('name', $permission)->value('id');
		$this->assertTrue(DB::table('role_has_permissions')->where('role_id', $roleId)->where('permission_id', $permissionId)->exists());
	}


	/**
	 * Assert that the given user role does not have the given permission.
	 *
	 * @param string $userRole
	 * @param string $permission permission must include verb and noun, e.g. 'create users'
	 */

	public function assertRoleNoPermission(string $userRole, string $permission) : void {

		$roleId = DB::table('roles')->where('name', $userRole)->value('id');
		$permissionId = DB::table('permissions')->where('name', $permission)->value('id');
		$this->assertFalse(DB::table('role_has_permissions')->where('role_id', $roleId)->where('permission_id', $permissionId)->exists());
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
