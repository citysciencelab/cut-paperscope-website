<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Http\Controllers\Auth;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Support\Facades\Hash;
	use Illuminate\Support\Facades\DB;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class TokenControllerTest extends TestCase {

	use RefreshDatabase;


	protected function setUp(): void {

		parent::setUp();
		$this->skipIfNoAppAccounts();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CREATE TOKEN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_create_new_token() {

		// arrange
		$user = $this->createUser(['password' => Hash::make('password123')]);
		$data = [
			'email' => $user->email,
			'password' => 'password123',
			'device_name' => 'Test Device',
		];

		// act: create token
		$response = $this->postData('/auth/token',$data);
		$token = $response['token'];

		// assert
		$this->assertIsString($token);
		$this->assertEquals($data['email'],$response['user']['email']);

		// act: create new token
		$response = $this->postData('/auth/token',$data);

		// assert: different tokens
		$this->assertNotEquals($token, $response['token']);
		$this->assertEquals($data['email'],$response['user']['email']);
	}


	public function test_create_new_token_with_username() {

		// arrange
		$user = $this->createUser(['password' => Hash::make('password123')]);
		$data = [
			'email' => $user->username,
			'password' => 'password123',
			'device_name' => 'Test Device',
		];

		// act: create token
		$response = $this->postData('/auth/token',$data);
		$token = $response['token'];

		// assert
		$this->assertIsString($token);
		$this->assertEquals($data['email'],$response['user']['username']);
	}


	public function test_user_not_found() {

		// arrange
		$data = [
			'email' => 'invalid@example.com',
			'password' => 'password123',
			'device_name' => 'Test Device',
		];

		// act
		$response = $this->post('/auth/token',$data,['Accept' => 'application/json']);

		// assert
		$response->assertStatus(403);
	}


	public function test_invalid_password() {

		// arrange
		$user = $this->createUser(['password' => Hash::make('password123')]);
		$data = [
			'email' => $user->email,
			'password' => 'password456',
			'device_name' => 'Test Device',
		];

		// act and assert
		$this->postError('/auth/token',$data,[],'auth.failed');
	}


	public function test_delete_old_token() {

		// arrange
		$user = $this->createUser(['password' => Hash::make('password123')]);
		$data = [
			'email' => $user->email,
			'password' => 'password123',
			'device_name' => 'Test Device',
		];

		// act: create token
		$response = $this->postData('/auth/token',$data);
		$token = $response['token'];

		// assert
		$this->assertIsString($token);

		// act: create new token
		$response = $this->postData('/auth/token',$data);
		$token2 = $response['token'];

		// assert: different tokens
		$this->assertNotEquals($token, $token2);

		// assert: only one token in database
		$dbTokens = DB::table('personal_access_tokens')->where('tokenable_id',$user->id)->get();
		$this->assertEquals(1, $dbTokens->count());
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	AUTH
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_protected_route_with_valid_token() {

		// arrange
		$user = $this->createUser(['password' => Hash::make('password123')]);
		$data = [
			'email' => $user->email,
			'password' => 'password123',
			'device_name' => 'Test Device',
		];

		// act
		$response = $this->postData('/auth/token',$data);
		$response = $this->get('/api/user', ['Authorization' => 'Bearer '.$response['token']]);

		// assert
		$response->assertStatus(200);
		$response->assertJson([
			'data' => [
				'id' => $user->id,
				'name' => $user->name,
				'email' => $user->email,
			],
		]);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	DELETE TOKEN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_delete_token() {

		// arrange: create token
		$user = $this->createUser(['password' => Hash::make('password123')]);
		$data = [
			'email' => $user->email,
			'password' => 'password123',
			'device_name' => 'Test Device',
		];
		$data = $this->postData('/auth/token',$data);

		// act
		$response = $this->post('/auth/token/delete',[
			'id' => $user->id,
			'token' => $data['token'],
		]);

		// assert: response
		$response->assertStatus(200);
		$response->assertJson(['status'=>'success']);

		// asser
		$dbToken = DB::table('personal_access_tokens')->where('tokenable_id',$user->id)->first();
		$this->assertNull($dbToken);
	}


	public function test_no_token_for_deleted_user() {

		// arrange
		$user = $this->createUser(['password' => Hash::make('password123')]);
		$data = [
			'email' => $user->email,
			'password' => 'password123',
			'device_name' => 'Test Device',
		];

		// act: create token
		$response = $this->postData('/auth/token',$data);

		// act: delete user
		$userId = $user->id;
		$user->delete();

		// assert: token is deleted
		$dbToken = DB::table('personal_access_tokens')->where('tokenable_id',$userId)->first();
		$this->assertNull($dbToken);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
