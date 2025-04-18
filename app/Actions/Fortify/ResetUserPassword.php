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
	use Laravel\Fortify\Contracts\ResetsUserPasswords;

	// App
	use App\Models\Auth\User;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ResetUserPassword implements ResetsUserPasswords
{

	// Traits
	use PasswordValidationRules;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	RESET
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function reset(User $user, array $input): void {

		// Validate input
		Validator::make($input, [
			'password' => $this->passwordRules(),
		],
		// Messages
		[
			'password.unique' => __('auth.password-insecure'),
		])
		->validate();

		// Reset password
		$user->forceFill(['password' => Hash::make($input['password'])])->save();
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
