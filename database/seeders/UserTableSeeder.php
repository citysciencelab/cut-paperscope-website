<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	NCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Database\Seeders;

	// Laravel
	use Illuminate\Database\Seeder;
	use Ramsey\Uuid\Uuid;
	use Illuminate\Support\Carbon;
	use Illuminate\Support\Facades\Hash;
	use Illuminate\Support\Facades\DB;
	use Laravel\Cashier\Subscription;

	// Models
	use App\Models\Auth\User;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class UserTableSeeder extends Seeder
{



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	RUN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function run(): void {

		// Reset cached roles and permissions
		app()['cache']->forget('spatie.permission.cache');

		$this->addRootUser('Hello', 'Nasty', config('auth.root.email'), 'm', 'password');
		$this->addTestingUser();
		$this->addManagerUser();
	}


	private function addRootUser($name, $surname, $mail, $gender, $passKey=false): User {

		// find password
		$pass = env('ROOT_'.strtoupper($passKey?$passKey:$name));

		// create root user
		$root = User::create([

			'id' => 		Uuid::uuid4(),
			'name' =>		$name,
			'surname' =>	$surname,
			'fullname' => 	$name.' '.$surname,

			'email' =>		$mail,
			'username' => 	'admin',

			'gender' =>		$gender,
			'password' =>	Hash::make($pass),
		]);

		// non fillable properties
		$root->created_at = Carbon::now();
		$root->updated_at = Carbon::now();
		$root->email_verified_at = Carbon::now();
		$root->syncRoles(['admin']);

		// free subscription
		Subscription::create([
			'user_id' => 		$root->id,
			'type' => 			'free',
			'stripe_id' => 		'sub_' . (string) Uuid::uuid4(),
			'stripe_status' => 	'active',
			'stripe_price' => 	'price_xxx',
			'quantity' => 		1,
		]);

		$root->save();

		return $root;
	}


	private function addTestingUser() {

		$tester = User::create([
			'id' => 		Uuid::uuid4(),
			'name' =>		'Adam',
			'surname' => 	'Yauch',
			'fullname' => 	'Adam Yauch',

			'email' =>		'tester@hello-nasty.com',
			'username' => 	'tester',

			'gender' =>		'm',
			'password' =>	Hash::make("B7yHQDZPEcDvX3yR"),
		]);

		// non fillable properties
		$tester->created_at = Carbon::now();
		$tester->updated_at = Carbon::now();
		$tester->email_verified_at = Carbon::now();
		$tester->syncRoles(['user']);
		$tester->save();
	}


	private function addManagerUser() {

		$manager = User::create([
			'id' => 		Uuid::uuid4(),
			'name' =>		'PaperScope',
			'surname' => 	'Manager',
			'fullname' => 	'PaperScope Manager',

			'email' =>		'manager@paperscope.de',
			'username' => 	'manager',

			'gender' =>		'u',
			'password' =>	Hash::make("BcavReLGxW38b4uU"),
		]);

		// non fillable properties
		$manager->created_at = Carbon::now();
		$manager->updated_at = Carbon::now();
		$manager->email_verified_at = Carbon::now();
		$manager->syncRoles(['user']);
		$manager->save();

		// create personal access token
		DB::table('personal_access_tokens')->insert([
			'id' => Uuid::uuid4(),
			'tokenable_type' => User::class,
			'tokenable_id' => $manager->id,
			'name' => 'Manager Token',
			'token' => "4f8347105cdcf8cce7342819a6f7cca89ebaa6b0fa63c95281575e87f6e2a94a",
			'abilities' => json_encode(['*']),
			'expires_at' => null,
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
