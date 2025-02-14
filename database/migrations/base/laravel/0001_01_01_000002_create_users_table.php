<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// Laravel
	use Illuminate\Support\Facades\Schema;
	use Illuminate\Database\Schema\Blueprint;

	// App
	use Database\Migrations\Base\BaseMigration;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


return new class extends BaseMigration
{

	protected string $tableName = 'users';

	protected array $roles = [
		'guest' => 		[],
		'user' => 		['edit', 'delete'],
		'member' => 	['edit', 'delete'],
		'editor' => 	['edit', 'delete'],
		'admin' => 		['create', 'edit', 'delete'],
	];



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    UP
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function create(Blueprint &$table): void {

		$table->uuid('id')->primary();

		// personal
		$table->string('name',50);
		$table->string('surname',50)->index();
		$table->string('fullname',101)->index();
		$table->string('lang',3)->default(config('app.fallback_locale'));

		$table->string('username',30)->unique();
		$table->string('email',100)->unique();

		$table->string('street',50)->nullable();
		$table->string('street_number',10)->nullable();
		$table->string('zipcode',10)->nullable();
		$table->string('city',50)->nullable();
		$table->string('country',3)->nullable();
		$table->date('birthday')->nullable();

		$table->string('gender',1)->nullable();
		$table->string('image',256)->nullable();
		$table->string('password');
		$table->boolean('newsletter')->default(0);

		// single sign on
		$table->string('sso_token')->nullable();
		$table->string('sso_driver',16)->nullable();

		// crm
		$table->boolean('approved')->default(0);
		$table->boolean('blocked')->default(0);

		// laravel
		$table->timestamp('email_verified_at')->nullable();
		$table->rememberToken();
	}


	public function up(): void {

		parent::up();

		Schema::create('password_reset_tokens', function (Blueprint $table) {

			$table->string('email')->index();
			$table->string('token');
			$table->timestamp('created_at')->nullable();
		});


		Schema::create('password_insecure', function (Blueprint $table) {

			$table->uuid('id')->primary();
			$table->string('password', 20);
		});


		Schema::create('personal_access_tokens', function (Blueprint $table) {

			$table->uuid('id')->primary();
			$table->uuid('tokenable_id');
			$table->string('tokenable_type');
			$table->string('name',50);
			$table->string('token', 64)->unique();
			$table->text('abilities')->nullable();
			$table->timestamp('last_used_at')->nullable();
			$table->timestamp('expires_at')->nullable();
			$table->timestamps();

			$table->index(['tokenable_id', 'tokenable_type'], 'tokenable_index');
		});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    DOWN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function down(): void {

		parent::down();
		Schema::dropIfExists('password_reset_tokens');
		Schema::dropIfExists('personal_access_tokens');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}; // end class
