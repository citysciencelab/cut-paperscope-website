<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Actions\Fortify;

	// Laravel
	use Illuminate\Support\Facades\Hash;
	use Illuminate\Support\Facades\Validator;
	use Illuminate\Validation\Rule;
	use Laravel\Fortify\Contracts\CreatesNewUsers;

	// App
	use App\Models\Auth\User;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class RegisterNewUser implements CreatesNewUsers
{
	// Traits
	use PasswordValidationRules;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CREATE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function create(array $input): User {

		// force lower case username
		$input['email'] = isset($input['email']) ? strtolower($input['email']) : null;
		$input['username'] = isset($input['username']) ? strtolower($input['username']) : null;

		// Validate input
		$validated = Validator::make($input, [
			'name' => 			['bail', 'required', 'string', 'max:50'],
			'surname' => 		['bail', 'required', 'string', 'max:50'],
			'lang' => 			['bail', 'required', 'string', 'in:'.implode(',', config('app.available_locales'))],
			'email' => 			['bail', 'required', 'string', 'email:strict', 'not_regex:/[äüö]/i', 'max:100', Rule::unique(User::class)],
			'username' => 		['bail', 'required', 'string', 'regex:/^[a-z0-9]([._-](?![._-])|[a-z0-9]){1,28}[a-z0-9]$/', 'min:3', 'max:30','unique:users'],
			'gender' => 		['bail', 'required', 'string', 'in:m,f,d,u'],
			'terms' => 			['bail', 'required', 'accepted',],
			'newsletter' => 	['sometimes', 'boolean'],
			'password' => 		$this->passwordRules(),
		],
		// Messages
		[
			'terms.required' => __('auth.terms-required'),
			'password.unique' => __('auth.password-insecure'),
		],
		// Attributes
		[
			'terms' => __('Datenschutzbestimmungen'),
		])
		->validate();

		// create new user
		$user = User::create([
			'name' => 			$validated['name'],
			'surname' => 		$validated['surname'],
			'email' => 			$validated['email'],
			'lang' => 			$validated['lang'],
			'username' => 		$validated['username'],
			'fullname' => 		$validated['name'] . ' ' . $validated['surname'],
			'gender' => 		$validated['gender'],
			'newsletter' => 	$validated['newsletter'] ?? false,
			'password' => 		Hash::make($validated['password']),
		]);


		$user->assignRole('guest');

		return $user;
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
