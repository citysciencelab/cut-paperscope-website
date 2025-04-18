<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// 	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Requests\Model;

	// Laravel
	use Illuminate\Support\Facades\Auth;

	// App
	use App\Http\Requests\BaseFormRequest;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// 	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class DeleteRequest extends BaseFormRequest {


	public function authorize(): bool {

		$user = Auth::user();
		$model = $this->getModelFromRoute();

		// allowed to edit
		return $user && $model && $user->hasPermissionTo('delete ' . $model);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	VALIDATE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function rules(): array {

		return [

			'id' =>	'bail|required|uuid',
		];
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
