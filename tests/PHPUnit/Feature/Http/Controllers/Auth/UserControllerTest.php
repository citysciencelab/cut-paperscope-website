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
	use Illuminate\Support\Facades\Notification;
	use Illuminate\Support\Facades\Bus;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Hash;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Http\UploadedFile;
	use Spatie\Permission\Models\Role;
	use Laravel\Fortify\Fortify;
	use Mockery;

	// App
	use App\Http\Controllers\Auth\UserController;
	use App\Notifications\UserDeletedNotification;
	use App\Notifications\PasswordResetNotification;
	use App\Jobs\Newsletter\RemoveUserFromNewsletter;
	use App\Models\Auth\User;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class UserControllerTest extends TestCase {

	use RefreshDatabase;


	protected function setUp(): void {

		parent::setUp();
		$this->skipIfNoBackend();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    AUTH LOGIN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_app_login() {

		$this->skipIfNoAppAccounts();
		$this->seed();

		// act
		$response = $this->post('/auth/login', [
			'email' => 'tester@hello-nasty.com',
			'password' => 'B7yHQDZPEcDvX3yR',
		]);

		// assert: response
		$response->assertStatus(200);

		// assert: user data
		$data = $response->json('data');
		$this->assertEquals('user',$data['type']);
		$this->assertEquals('tester@hello-nasty.com',$data['email']);
	}


	public function test_app_login_with_username() {

		$this->skipIfNoAppAccounts();
		$this->seed();

		// act
		$response = $this->post('/auth/login', [
			'email' => 'tester',
			'password' => 'B7yHQDZPEcDvX3yR',
		]);

		// assert: response
		$response->assertStatus(200);

		// assert: user data
		$data = $response->json('data');
		$this->assertEquals('user',$data['type']);
		$this->assertEquals('tester@hello-nasty.com',$data['email']);
	}


	public function test_app_login_with_wrong_password() {

		$this->skipIfNoAppAccounts();
		$this->seed();

		// act
		$response = $this->post('/auth/login', [
			'email' => 'missinguser',
			'password' => 'wrongPassword',
		]);

		// assert: response
		$response->assertStatus(302);

		// assert: form error
		$response->assertSessionHasErrors(['email']);
	}


	public function test_redirect_if_already_logged_in() {

		$this->skipIfNoAppAccounts();
		$this->loginAsUser();

		// act
		$response = $this->post('/auth/login', [
			'email' => 'tester@hello-nasty.com',
			'password' => 'B7yHQDZPEcDvX3yR',
		]);

		// assert
		$response->assertStatus(302);
	}


	public function test_app_login_as_blocked_user() {

		$this->skipIfNoAppAccounts();
		$this->seed();

		// arrange
		$user = User::where('email','tester@hello-nasty.com')->first();
		$user->update(['blocked'=>true]);

		// act
		$response = $this->post('/auth/login', [
			'email' => 'tester@hello-nasty.com',
			'password' => 'B7yHQDZPEcDvX3yR',
		]);

		// assert
		$response->assertStatus(403);
		$response->assertJson(['status'=>'error']);
	}


	public function test_backend_login() {

		$this->seed();

		// act
		$response = $this->post('/auth/login', [
			'email' => 'admin@paperscope.de',
			'password' => env('ROOT_PASSWORD'),
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(200);
	}


	public function test_backend_login_without_permisison() {

		$this->seed();

		// act
		$response = $this->post('/auth/login', [
			'email' => 'tester@hello-nasty.com',
			'password' => 'B7yHQDZPEcDvX3yR',
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(403);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    AUTH REGISTER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_app_register() {

		$this->skipIfNoAppAccounts();

		// arrange
		Notification::fake();

		// act
		$user = $this->postData('/auth/register', [
			'name' => 'Max',
			'surname' => 'Mustermann',
			'lang' => 'de',
			'email' => 'tester@hello-nasty.com',
			'username' => 'testuser',
			'gender' => 'u',
			'terms' => true,
			'password' => 'B7yHQDZPEcDvX3yR',
			'password_confirmation' => 'B7yHQDZPEcDvX3yR',
		]);

		// assert: user in database
		$this->assertEquals('tester@hello-nasty.com',$user['email']);
		$user = User::find($user['id']);

		// act: get verification url
		$actionUrl = '';
		Notification::assertSentTo($user, \App\Notifications\VerifyRegisterNotification::class, function ($notification) use ($user,&$actionUrl) {
    		$mailData = $notification->toMail($user);
			$actionUrl = $mailData->actionUrl;
			return true;
		});

		// assert: action url exists
		$this->assertNotNull($actionUrl);

		// act: verify email
		Auth::logout();
		$response = $this->get($actionUrl);

		// assert: redirect to verified
		$response->assertStatus(302);
		$response->assertRedirect(Fortify::redirects('email-verification').'?verified=1');
	}


	public function test_register_existing_user_not_allowed() {

		// arrange
		$this->skipIfNoAppAccounts();
		$this->seed();

		// act
		$response = $this->post('/auth/register', [
			'name' => 'Max',
			'surname' => 'Mustermann',
			'lang' => 'de',
			'email' => 'tester@hello-nasty.com',
			'username' => 'testuser',
			'gender' => 'u',
			'terms' => true,
			'password' => 'B7yHQDZPEcDvX3yR',
			'password_confirmation' => 'B7yHQDZPEcDvX3yR',
		]);

		// assert
		$response->assertStatus(302);
		$response->assertSessionHasErrors(['email']);
	}


	public function test_register_existing_username_not_allowed() {

		// arrange
		$this->skipIfNoAppAccounts();
		$this->seed();

		// act
		$response = $this->post('/auth/register', [
			'name' => 'Max',
			'surname' => 'Mustermann',
			'lang' => 'de',
			'email' => 'tester2@hello-nasty.com',
			'username' => 'tester',
			'gender' => 'u',
			'terms' => true,
			'password' => 'B7yHQDZPEcDvX3yR',
			'password_confirmation' => 'B7yHQDZPEcDvX3yR',
		]);

		// assert
		$response->assertStatus(302);
		$response->assertSessionHasErrors(['username']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    APP GET
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_get_user() {

		$this->skipIfNoAppAccounts();

		// arrange
		$user = $this->loginAsUser();

		// act
		$data = $this->getData('/api/user');

		// assert
		$this->assertEquals('user',$data['type']);
		$this->assertEquals($user->id,$data['id']);
	}


	public function test_get_no_user_without_login() {

		$this->skipIfNoAppAccounts();
		$this->seed();

		// act
		$response = $this->get('/api/user');

		// assert
		$response->assertStatus(302);
	}


	public function test_get_user_without_permission() {

		$this->skipIfNoAppAccounts();

		// arrange
		$this->loginAsUser();

		// act
		$response = $this->get('/api/user', $this->getBackendHeaders());

		// arrange
		$response->assertStatus(404);
	}


	public function test_method_getMe() {

		$this->skipIfNoAppAccounts();

		// arrange
		$user = $this->loginAsUser();

		// act
		$me = (new UserController())->getMe();

		// assert
		$this->assertEquals($user->id, $me->id);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    BACKEND GET
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_get_user_in_backend() {

		// arrange
		$admin = $this->loginAsAdmin();

		// act
		$data = $this->postData('/api/backend/user', [], $this->getBackendHeaders());

		// assert
		$this->assertEquals('user',$data['type']);
		$this->assertEquals($admin->id,$data['id']);
	}


	public function test_get_other_user_as_admin() {

		// arrange
		$admin = $this->loginAsAdmin();
		$user = User::factory()->create();

		// act
		$data = $this->postData('/api/backend/user',
			['id' => $user->id],
			$this->getBackendHeaders()
		);

		// assert
		$this->assertEquals('user',$data['type']);
		$this->assertEquals($user->id,$data['id']);
	}


	public function test_no_access_get_user_in_backend() {

		// force user in db
		$this->seed();

		// check response
		$response = $this->post('/api/backend/user');
		$response->assertStatus(302);
	}


	public function test_get_user_in_backend_without_permission() {

		$user = $this->loginAsUser();

		// check response
		$response = $this->post('/api/backend/user',[], $this->getBackendHeaders());
		$response->assertStatus(403);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    BACKEND LIST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_get_list_in_backend() {

		// arrange
		$admin = $this->loginAsAdmin();

		// act
		$data = $this->postData('/api/backend/user/list');

		// assert
		$this->assertIsArray($data);
		$this->assertEquals('user',$data[0]['type']);
	}


	public function test_get_list_ordered_by_email_in_backend() {

		// arrange
		$admin = $this->loginAsAdmin();
		User::factory()->create(['email'=>'a@hello-nasty.com']);
		User::factory()->create(['email'=>'zzz@hello-nasty.com']);

		// act: get reversed order
		$data = $this->postData('/api/backend/user/list', [
			'direction_property'=>'email',
			'direction'=>'desc'
		]);

		// assert
		$this->assertIsArray($data);
		$this->assertEquals('zzz@hello-nasty.com',$data[0]['email']);
	}


	public function test_get_list_ordered_by_role_in_backend() {

		// arrange
		$admin = $this->loginAsAdmin();
		$user1 = User::factory()->create(['email'=>'a@hello-nasty.com']);
		$user2 = User::factory()->create(['email'=>'zzz@hello-nasty.com']);
		$user1->changeRole('guest','user');
		$user2->changeRole('guest','admin');

		// act: get reversed order
		$data = $this->postData('/api/backend/user/list', [
			'direction_property'=>'role',
			'direction'=>'desc'
		]);

		// assert
		$this->assertIsArray($data);
		$this->assertEquals('a@hello-nasty.com',$data[0]['email']);
	}


	public function test_no_admin_get_list_in_backend() {

		// arrange
		$this->loginAsEditor();

		// act
		$response = $this->post('/api/backend/user/list');

		// assert
		$response->assertStatus(403);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    BACKEND ROLES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_get_roles_in_backend() {

		// enable shop feature
		config(['app.features.shop'=>true]);

		// arrange
		$this->loginAsAdmin();

		// act
		$data = $this->getData('/api/backend/user/roles');
		$roles = collect($data)->pluck('name')->toArray();

		// assert
		$this->assertIsArray($data);
		$this->assertEquals('role',$data[0]['type']);
		$this->assertContains('guest', $roles);
		$this->assertContains('member', $roles);
		$this->assertContains('admin', $roles);
	}


	public function test_get_roles_in_backend_with_disabled_feature_shop() {

		// disable shop feature
		config(['app.features.shop'=>false]);

		// arrange
		$this->loginAsAdmin();

		// act
		$data = $this->getData('/api/backend/user/roles');
		$roles = collect($data)->pluck('name')->toArray();

		// check data
		$this->assertNotContains('member', $roles);
	}


	public function test_no_admin_get_roles_in_backend() {

		// arrange
		$this->loginAsEditor();

		// act
		$response = $this->get('/api/backend/user/roles');

		// assert
		$response->assertStatus(403);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    APP SAVE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_save_user() {

		$this->skipIfNoAppAccounts();

		// arrange
		$user = $this->loginAsUser();
		$formData = [
			'id' => $user->id,
			'email' => $user->email,
			'username' => 'testuser',
			'name' => 'John',
			'surname' => 'Doe',
			'gender' => 'f',
		];

		// act
		$data = $this->postData('/api/user/save', $formData);

		// assert
		$this->assertEquals('user',$data['type']);
		$this->assertEquals($formData['name'],$data['name']);
		$this->assertEquals($formData['surname'],$data['surname']);
		$this->assertEquals($formData['gender'],$data['gender']);
	}


	public function test_not_allowed_to_save_other_user() {

		$this->skipIfNoAppAccounts();

		// arrange
		$user = $this->loginAsUser();
		$otherUser = $this->createUser();
		$formData = [
			'id' => $otherUser->id,
			'email' => $otherUser->email,
			'username' => 'testuser',
			'name' => 'John',
			'surname' => 'Doe',
			'gender' => 'f',
		];

		// act
		$response = $this->post('/api/user/save', $formData);

		// assert
		$response->assertStatus(403);
		$response->assertJson(['status'=>'error']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    BACKEND SAVE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_save_new_user_in_backend() {

		// arrange
		$admin = $this->loginAsAdmin();
		$newUser = [
			'email' => 'asdj123@asdeafj.de',
			'name' => 'Max',
			'surname' => 'Mustermann',
			'username' => 'testuser',
			'gender' => 'm',
			'password' => 'muster_passwort_123',
			'password_confirmation' => 'muster_passwort_123',
			'role' => 'user',
		];

		// act
		$data = $this->postData('/api/user/save', $newUser, $this->getBackendHeaders());

		// assert: new user
		$this->assertEquals('user',$data['type']);
		$this->assertEquals($newUser['email'],$data['email']);
		$this->assertEquals('user',$data['role']);
	}


	public function test_save_user_with_new_name_for_new_image() {

		$this->seed();

		// arrange
		/** @var \App\Models\Auth\User $admin **/
		$admin = $this->loginAsAdmin();
		$admin->name = "New";
		$admin->surname = "Name";
		$newData = $admin->toArray();
		unset($newData['roles']);

		// act
		$data = $this->postData('/api/user/save', $newData, $this->getBackendHeaders());

		// assert: image property
		$this->assertNotNull($data['image']);
		$this->assertStringContainsString('default-hr.jpg', $data['image']);

		// assert: file exists in storage
		$data['image'] = str_replace($this->storage->url(''), '', $data['image']);
		$data['image'] = explode('?', $data['image'])[0];
		$this->storage->assertExists($data['image']);
	}


	public function test_missing_password_when_saving_new_user_in_backend() {

		// arrange
		$admin = $this->loginAsAdmin();
		$newUser = [
			'email' => 'asdj123@asdeafj.de',
			'name' => 'Max',
			'surname' => 'Mustermann',
			'username' => 'testuser',
			'gender' => 'm',
		];

		// act
		$response = $this->post('/api/user/save', $newUser, $this->getBackendHeaders());

		// assert: form error
		$response->assertStatus(422);
		$response->assertJsonStructure(['errors'=>["password"]]);
	}


	public function test_save_new_backend_user_without_write_permission() {

		// arrange
		$editorRole = Role::findByName('editor');
		$editorRole->revokePermissionTo('create users');
		$this->loginAsEditor();

		// arrange: new user
		$newUser = [
			'email' => 'asdj123@asdeafj.de',
			'name' => 'Max',
			'surname' => 'Mustermann',
			'username' => 'testuser',
			'gender' => 'm',
			'password' => 'muster_passwort_123',
			'password_confirmation' => 'muster_passwort_123',
			'role' => 'user',
		];

		// act
		$response = $this->post('/api/user/save', $newUser, $this->getBackendHeaders());

		// assert
		$response->assertStatus(403);
	}


	public function test_save_other_backend_user_without_permission() {

		$this->seed();

		// arrange
		$editor = $this->loginAsEditor();
		$user = User::whereEmail('tester@hello-nasty.com')->first();

		// arrange: form data
		$formData = [
			'id' => $user->id,
			'email' => 'asdj123@asdeafj.de',
			'name' => 'Max',
			'surname' => 'Mustermann',
			'username' => 'testuser',
			'gender' => 'm',
		];

		// act
		$response = $this->post('/api/user/save', $formData, $this->getBackendHeaders());

		// assert
		$response->assertStatus(403);
	}


	public function test_save_blocked_backend_user() {

		$user = $this->createUser();
		$this->loginAsAdmin();

		// arrange: set remember token for assertion
		DB::table('users')->where('id', $user->id)->update(['remember_token'=>'testing_token']);
		$this->assertNotNull(User::find($user->id)->remember_token);

		// arrange
		$formData = [
			'id' => $user->id,
			'email' => $user->email,
			'name' => $user->name,
			'surname' => $user->surname,
			'username' => $user->username,
			'gender' => $user->gender,
			'role' => 'user',
			'blocked' => true,
		];

		// act
		$data = $this->postData('/api/user/save', $formData, $this->getBackendHeaders());

		// assert: user is blocked
		$this->assertTrue($data['blocked']);

		// assert: remember token was removed
		$this->assertNull(User::find($user->id)->remember_token);
	}


	public function test_save_user_and_change_to_sso() {

		$user = $this->createGuest();
		$this->loginAsAdmin();

		// arrange
		$formData = [
			'id' => $user->id,
			'email' => $user->email,
			'name' => $user->name,
			'surname' => $user->surname,
			'username' => $user->username,
			'gender' => $user->gender,
			'sso_driver' => 'google',
		];

		// act
		$data = $this->postData('/api/user/save', $formData, $this->getBackendHeaders());
		$data = User::find($user->id);


		// assert
		$this->assertEquals('google', $data->sso_driver);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	APP DELETE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_delete_user() {

		$this->skipIfNoAppAccounts();
		$this->seed();

		// arrange
		Notification::fake();
		$user = User::first();

		// assert: user image exists
		$this->storage->assertExists($user->storageFolder.'default-hr.jpg');

		// act
		/** @var \Illuminate\Contracts\Auth\Authenticatable $user **/
		$response = $this->actingAs($user)->post('/api/user/delete', ['id'=>$user->id]);

		// assert: response
		$response->assertStatus(200);
		$response->assertJson(['status'=>'success']);

		// assert: user is not in database anymore
		$deletedUser = User::find($user->id);
		$this->assertNull($deletedUser);

		// assert: no folder in storage
		$this->storage->assertMissing($user->storageFolder.'default-hr.jpg');

		// assert: delete notification was sent
		Notification::assertSentOnDemand(
            UserDeletedNotification::class,
			function ($notification, $channels, $notifiable) use ($user) {
        		return $notifiable->routes['mail'] === $user->email;
    		}
        );
	}


	public function test_not_allowed_to_delete_other_user() {

		$this->skipIfNoAppAccounts();

		// arrange
		$user = $this->loginAsUser();
		$otherUser = $this->createUser();

		// act
		$response = $this->post('/api/user/delete', ['id'=>$otherUser->id]);

		// arrange
		$response->assertStatus(403);
		$response->assertJson(['status'=>'error']);
	}


	public function test_delete_user_with_newsletter() {

		$this->skipIfNoAppAccounts();
		$this->seed();

		// arrange
		Bus::fake();
		$user = User::first();
		$user->update(['newsletter'=>true]);

		// act
		/** @var \Illuminate\Contracts\Auth\Authenticatable $user **/
		$response = $this->actingAs($user)->post('/api/user/delete', ['id'=>$user->id]);

		// assert
		$response->assertStatus(200);

		// assert:newsletter job dispatched
		Bus::assertDispatched(RemoveUserFromNewsletter::class);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	BACKEND DELETE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_delete_user_in_backend() {

		$this->seed();

		// arrange
		Notification::fake();
		$admin = $this->loginAsAdmin();
		$user = User::where('email','tester@hello-nasty.com')->first();

		// assert: user image exists
		$this->storage->assertExists($user->storageFolder.'default-hr.jpg');

		// act
		/** @var \Illuminate\Contracts\Auth\Authenticatable $admin **/
		$response = $this->actingAs($admin)->post('/api/user/delete', ['id'=>$user->id], $this->getBackendHeaders());

		// assert: response
		$response->assertStatus(200);
		$response->assertJson(['status'=>'success']);

		// assert: user is not in database anymore
		$deletedUser = User::find($user->id);
		$this->assertNull($deletedUser);

		// assert: no folder in storage
		$this->storage->assertMissing($user->storageFolder.'default-hr.jpg');

		// assert: delete notification was sent
		Notification::assertSentOnDemand(
			UserDeletedNotification::class,
			function ($notification, $channels, $notifiable) use ($user) {
				return $notifiable->routes['mail'] === $user->email;
			}
		);
	}


	public function test_not_allowed_to_delete_other_user_in_backend() {

		// arrange
		$this->loginAsEditor();
		$otherUser = $this->createUser();

		// act
		$response = $this->post('/api/user/delete', ['id'=>$otherUser->id], $this->getBackendHeaders());

		// assert
		$response->assertStatus(403);
		$response->assertJson(['status'=>'error']);
	}


	public function test_not_allowed_to_delete_admin_in_backend() {

		$this->seed();

		// arrange
		$this->loginAsAdmin();
		$user = User::where('email','admin@paperscope.de')->first();

		// act
		$response = $this->post('/api/user/delete', ['id'=>$user->id], $this->getBackendHeaders());

		// assert
		$response->assertStatus(403);
		$response->assertJson(['status'=>'error']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	PASSWORD RESET
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_forgot_password() {

		// arrange
		Notification::fake();
		$user = $this->createUser([
			'email' => 'tester@hello-nasty.com',
			'password' => Hash::make('password123')
		]);

		// act: send reset password request
		$this->post('/auth/forgot-password', ['email'=>$user->email]);

		// assert: reset token saved
		$result = DB::table('password_reset_tokens')->whereEmail($user->email)->first();
		$this->assertNotNull($result);
		$this->assertEquals($user->email, $result->email);

		// assert: reset password notification was sent
		$token = null;
		Notification::assertSentTo([$user], PasswordResetNotification::class, function ($notification, $channels, $notifiable) use ($user, &$token) {
			$token = $notification->token;
			return true;
		});

		// act: reset password
		$response = $this->post('auth/reset-password', [
			'email' => $user->email,
			'token' => $token,
			'password' => 'newPassword',
			'password_confirmation' => 'newPassword',
		]);

		// assert: check redirect to home
		$response->assertRedirect(config('fortify.home'));
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	PASSWORD UPDATE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_update_password() {

		$this->skipIfNoAppAccounts();

		// arrange
		$user = $this->loginAsUser([
			'email' => 'tester@hello-nasty.com',
			'password' => Hash::make('password123')
		]);
		$formData = [
			'id' => $user->id,
			'old' => 'password123',
			'password' => 'newPassword',
			'password_confirmation' => 'newPassword',
		];

		// act
		$response = $this->post('/api/user/password', $formData);

		// assert: response
		$response->assertStatus(200);
		$response->assertJson(['status'=>'success']);

		// assert: password was updated
		$user = User::find($user->id);
		$this->assertTrue(Hash::check('newPassword', $user->password));
	}


	public function test_not_allowed_to_update_password_for_other_user() {

		$this->skipIfNoAppAccounts();

		// arrange
		$this->loginAsUser(['password' => Hash::make('password123')]);
		$otherUser = $this->createUser(['password' => Hash::make('456password')]);
		$formData = [
			'id' => $otherUser->id,
			'old' => 'password123',
			'password' => 'newPassword',
			'password_confirmation' => 'newPassword',
		];

		// act
		$response = $this->post('/api/user/password', $formData);

		// assert
		$response->assertStatus(403);
		$response->assertJson(['status'=>'error']);
	}


	public function test_update_password_not_allowed_for_sso_user() {

		$this->skipIfNoAppAccounts();

		// arrange
		$user = $this->loginAsUser([
			'password' => Hash::make('password123'),
			'sso_driver' => 'google',
		]);
		$formData = [
			'id' => $user->id,
			'old' => 'password123',
			'password' => 'newPassword',
			'password_confirmation' => 'newPassword',
		];

		// act
		$response = $this->post('/api/user/password', $formData);

		// assert
		$response->assertStatus(403);
		$response->assertJson(['status'=>'error']);
	}


	public function test_password_confirmation_incorrect() {

		$this->skipIfNoAppAccounts();

		// arrange
		$user = $this->loginAsUser([
			'password' => Hash::make('password123'),
		]);
		$formData = [
			'id' => $user->id,
			'old' => 'password123',
			'password' => 'newPassword',
			'password_confirmation' => 'newPassword2',
		];

		// act
		$response = $this->post('/api/user/password', $formData);

		// assert
		$response->assertStatus(302);
		$response->assertSessionHasErrors(['password']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	UPDATE IMAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_update_user_image() {

		$this->skipIfNoAppAccounts();
		$this->seed();

		// arrange
		$user = $this->loginAsUser();
		$file = UploadedFile::fake()->image('newImage.jpg');
		$formData = [
			'id' => $user->id,
			'file' => $file,
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716='
		];

		// act
		$response = $this->post('/api/user/image', $formData);

		// assert: response
		$response->assertStatus(200);
		$response->assertJson(['status'=>'success']);

		// assert: image property was updated
		$user = User::find($user->id);
		$this->assertNotNull($user->image);
		$this->assertStringContainsString('image.jpg?id=', $user->image);

		// assert: image was stored
		$image = $this->storageUrlToPath($user->image);
		$this->storage->assertExists($image);
	}


	public function test_update_image_for_missing_user() {

		$this->skipIfNoAppAccounts();

		// arrange
		$this->loginAsUser();
		$missingUser = User::factory()->make();
		$formData = [
			'id' => $missingUser->id,
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716='
		];

		// act
		$response = $this->post('/api/user/image', $formData);

		// assert: response
		$response->assertStatus(302);
		$response->assertSessionHasErrors(['id']);
	}


	public function test_not_allowed_to_update_image_for_other_user() {

		$this->skipIfNoAppAccounts();

		// arrange
		$this->loginAsUser();
		$otherUser = $this->createUser();
		$formData = [
			'id' => $otherUser->id,
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716='
		];

		// act
		$response = $this->post('/api/user/image', $formData);

		// assert
		$response->assertStatus(403);
		$response->assertJson(['status'=>'error']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	DELETE IMAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_delete_image() {

		$this->skipIfNoAppAccounts();
		$this->seed();

		// arrange
		$user = $this->loginAsUser();
		$file = UploadedFile::fake()->image('newImage.jpg');
		$formData = [
			'id' => $user->id,
			'file' => $file,
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716='
		];

		// act: create image
		$response = $this->post('/api/user/image', $formData);

		// assert: image created
		$updatedImage = $this->storageUrlToPath($response->json('data'));
		$this->storage->assertExists($updatedImage);

		// act: delete image
		$response = $this->post('/api/user/image/delete', ['id' => $user->id]);

		// assert: response
		$response->assertStatus(200);
		$response->assertJson(['status'=>'success']);

		// assert: image deleted
		$updatedImage = $this->storageUrlToPath($updatedImage);
		$this->storage->assertMissing($updatedImage);

		// assert: new default image
		$defaultImage = $response->json('data');
		$this->assertStringContainsString('default-hr.jpg', $defaultImage);
	}


	public function test_delete_other_image_without_permission() {

		$this->skipIfNoAppAccounts();
		$this->seed();

		// arrange
		$user = $this->loginAsUser();
		$otherUser = $this->createUser();
		$file = UploadedFile::fake()->image('newImage.jpg');
		$formData = [
			'id' => $otherUser->id,
			'file' => $file,
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716='
		];

		// act: create image
		$response = $this->post('/api/user/image', $formData);

		// assert: image created
		$updatedImage = $this->storageUrlToPath($response->json('image'));
		$this->storage->assertExists($updatedImage);

		// act: delete image
		$response = $this->actingAs($user)->post('/api/user/image/delete', ['id' => $otherUser->id]);

		// assert: response
		$response->assertStatus(403);
		$response->assertJson(['status'=>'error']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
