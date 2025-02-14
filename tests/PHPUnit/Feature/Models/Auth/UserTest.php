<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Models\Auth;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Support\Facades\Schema;
	use Illuminate\Support\Carbon;
	use Mockery;

	// Models
	use App\Models\Auth\User;
	use App\Models\Shop\Product;
	use App\Models\Shop\Subscription;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class UserTest extends TestCase {

	use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    SCHEMA
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_database_has_expected_columns() {

		$this->assertTrue(
		  Schema::hasColumns('users', [
			'name', 'surname', 'fullname', 'lang', 'username',
			'email',
			'street', 'street_number', 'zipcode', 'city', 'country', 'birthday',
			'gender', 'image', 'password', 'newsletter',
			'sso_token', 'sso_driver',
			'approved', 'blocked',
			'created_at', 'updated_at',
			'email_verified_at',
			'remember_token',
		]), 1);
	}


	public function test_users_database_has_stripe_columns() {

		// skip if no shop feature
		if (!config('app.features.shop')) {	return $this->assertTrue(true);	}

		$this->assertTrue(
		  Schema::hasColumns('users', [
			'stripe_id',
			'pm_type', 'pm_last_four',
			'trial_used', 'trial_ends_at',
		]), 1);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    PERMISSIONS / ROLES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_existing_permissions_for_user() {

		// arrange
		$this->seed();

		// assert
		$this->assertDatabaseHas('permissions', ['name' => 'create users']);
		$this->assertDatabaseHas('permissions', ['name' => 'edit users']);
		$this->assertDatabaseHas('permissions', ['name' => 'delete users']);
	}


	public function test_existing_user_roles() {

		// arrange
		$this->seed();

		// assert
		$this->assertDatabaseHas('roles', ['name' => 'guest']);
		$this->assertDatabaseHas('roles', ['name' => 'user']);
		$this->assertDatabaseHas('roles', ['name' => 'editor']);
		$this->assertDatabaseHas('roles', ['name' => 'admin']);
	}


	public function test_guest_can_not_edit_users() {

		// arrange
		$this->seed();
		$guest = User::factory()->create();
		$guest->assignRole('guest');

		// assert
		$this->assertFalse($guest->can('edit users'));
	}


	public function test_user_can_edit_users() {

		// arrange
		$this->seed();
		$user = User::factory()->create();
		$user->assignRole('user');

		// assert
		$this->assertTrue($user->can('edit users'));
	}


	public function test_only_admins_can_create_new_users() {

		// arrange
		$this->seed();

		// arrange: guest
		$guest = User::factory()->create();
		$guest->assignRole('guest');

		// arrange: user
		$user = User::factory()->create();
		$user->assignRole('user');

		// arrange: editor
		$editor = User::factory()->create();
		$editor->assignRole('editor');

		// arrange: admin
		$admin = User::factory()->create();
		$admin->assignRole('admin');

		// assert
		$this->assertFalse($guest->can('create users'));
		$this->assertFalse($user->can('create users'));
		$this->assertFalse($editor->can('create users'));
		$this->assertTrue($admin->can('create users'));
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    GETTER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_gender_properties() {

		// arrange
		$female = User::factory()->make(['gender' => 'f']);
		$male = User::factory()->make(['gender' => 'm']);

		// assert
		$this->assertTrue($female->isFemale());
		$this->assertTrue($male->isMale());
	}


	public function test_roles_properties() {

		// arrange
		$this->seed();

		// assert: default role is guest
		$user = User::factory()->create();
		$this->assertTrue($user->isGuest());
		$this->assertFalse($user->isUser());
		$this->assertFalse($user->isAdmin());

		// assert: user role
		$user = User::factory()->create();
		$user->assignRole('user');
		$this->assertTrue($user->isUser());
		$this->assertFalse($user->isAdmin());

		// assert: editor role
		$user = User::factory()->create();
		$user->assignRole('editor');
		$this->assertTrue($user->isEditor());
		$this->assertFalse($user->isAdmin());

		// assert: admin role
		$user = User::factory()->create();
		$user->assignRole('admin');
		$this->assertTrue($user->isAdmin());
	}


	public function test_backend_user_roles() {

		// arrange
		$this->seed();

		// assert: user without backend access
		$user = User::factory()->create();
		$user->assignRole('user');
		$this->assertFalse($user->isBackendUser());

		// assert: user with backend access
		$user = User::factory()->create();
		$user->assignRole('editor');
		$this->assertTrue($user->isBackendUser());

		// assert admin
		$user = User::factory()->create();
		$user->assignRole('admin');
		$this->assertTrue($user->isBackendUser());
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    STORAGE PROPERTIES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_property_storage_disk() {

		// arrange
		$this->seed();
		$user = User::factory()->create();
		$storage = config('filesystems.default');

		// assert
		$this->assertEquals($storage, $user->storageDisk);
	}


	public function test_property_storage_folder() {

		// arrange
		$this->seed();
		$user = User::factory()->create();
		$user->created_at = Carbon::now();

		// assert
		$this->assertNotEmpty($user->storageFolder);
		$this->assertStringContainsString($user->id, $user->storageFolder);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    USER IMAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_create_user_with_default_image() {

		// arrange
		$this->seed();
		$user = User::factory()->create(['name'=>'Max', 'surname'=>'Mustermann']);

		// assert: images exists
	   	$this->storage->assertExists($user->storageFolder.'default-hr.jpg');
	   	$this->storage->assertExists($user->storageFolder.'default-mr.jpg');
	   	$this->storage->assertExists($user->storageFolder.'default-lr.jpg');

		// assert: image property
		$this->assertStringContainsString($user->storageFolder.'default-hr.jpg', $user->image);

		// assert: image property saved in database
		$user = User::find($user->id);
		$this->assertNotNull($user->image);
		$this->assertStringContainsString($user->storageFolder.'default-hr.jpg', $user->image);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	ROLES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_change_user_role() {

		// arrange
		$this->seed();
		$user = User::factory()->create();
		$user->changeRole('guest','user');

		// assert
		$this->assertTrue($user->hasRole('user'));
		$this->assertFalse($user->hasRole('guest'));
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    SEARCH
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_searchable_data() {

		// arrange
		$this->seed();
		$user = User::factory()->create([
			'name'=>'Max',
			'surname'=>'Mustermann',
			'email'=>'max.mustermann@example.com',
			'username'=>'max'
		]);

		// assert: searchable data
		$this->assertEquals($user->toSearchableArray(), [
			'name' => 'Max',
			'surname' => 'Mustermann',
			'email' => 'max.mustermann@example.com',
			'username' => 'max',
		]);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    PRODUCT RELATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_has_product_relation() {

		// arrange
		$this->seed();
		$user = User::factory()->create(['name'=>'Max', 'surname'=>'Mustermann']);
		$product = Product::factory()->public()->create();

		// act: add relation to model
		$user->products()->attach($product->id, ['receipt' => 'https://stripe.com', 'status' => 'succeded']);
		$this->assertCount(1, $user->products);

		// assert: columns from intermediate table
		$firstProduct = $user->products->first();
		$this->assertEquals($firstProduct->pivot->receipt, 'https://stripe.com');
		$this->assertEquals($firstProduct->pivot->status, 'succeded');

		// act: delete model
		$deleted = $user->delete();

		// assert: relation deleted
		$this->assertTrue($deleted);
		$this->assertDatabaseMissing('product_user', ['product_id' => $product->id]);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    STRIPE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_has_stripe_subscription() {

		$this->skipIfNoShop();
		if(!config('cashier.key')) { $this->markTestSkipped('No Stripe key set.'); }
		$this->mockStripe();

		// arrange: member
		$user = User::factory()->create();
		$user->changeRole('guest','member');

		// act: add default subscription
		Subscription::factory()->create([
			'user_id' => $user->id,
			'type' => "default",
			'stripe_id' => 'sub_1234567890',
			'stripe_status' => 'active',
			'stripe_price' => 'price_1234567890',
			'quantity' => 1,
		]);
		$user->load('subscriptions');

		// assert: subscribed
		$this->assertTrue($user->hasSubcription());
		$this->assertFalse($user->hasFreeSubscription());
	}


	public function test_has_free_stripe_subscription() {

		$this->skipIfNoShop();
		if(!config('cashier.key')) { $this->markTestSkipped('No Stripe key set.'); }
		$this->mockStripe();

		// arrange: member
		$user = User::factory()->create();
		$user->changeRole('guest','member');

		// act: add free subscription
		Subscription::factory()->create([
			'user_id' => $user->id,
			'type' => "free",
			'stripe_id' => 'sub_xxx',
			'stripe_status' => 'active',
			'stripe_price' => 'price_xxx',
			'quantity' => 1,
		]);
		$user->load('subscriptions');

		// assert: subscribed
		$this->assertFalse($user->hasSubcription());
		$this->assertTrue($user->hasFreeSubscription());
	}


	public function test_delete_user_with_subscription() {

		$this->skipIfNoShop();
		if(!config('cashier.key')) { $this->markTestSkipped('No Stripe key set.'); }
		$this->mockStripe();

		// arrange: member
		$user = User::factory()->create();
		$user->changeRole('guest','member');

		// act: add default subscription
		Subscription::factory()->create([
			'user_id' => $user->id,
			'type' => "default",
			'stripe_id' => 'sub_1234567890',
			'stripe_status' => 'active',
			'stripe_price' => 'price_1234567890',
			'quantity' => 1,
		]);

		// act: add free subscription
		Subscription::factory()->create([
			'user_id' => $user->id,
			'type' => "free",
			'stripe_id' => 'sub_xxx',
			'stripe_status' => 'active',
			'stripe_price' => 'price_xxx',
			'quantity' => 1,
		]);
		$user->load('subscriptions');

		// act: delete model
		$deleted = $user->delete();

		// assert: relation deleted
		$this->assertTrue($deleted);
		$this->assertDatabaseMissing('subscriptions', ['user_id' => $user->id]);
	}


	protected function mockStripe() {

		$subscriptions = Mockery::mock('overload:\Stripe\Service\SubscriptionService');
		$subscriptions->shouldReceive('cancel')->andReturn(true);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

} // end class
