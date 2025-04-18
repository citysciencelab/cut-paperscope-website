<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// 	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Requests\Backend\Base;

	// App
	use App\Http\Requests\Model\BaseModelSaveRequest;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// 	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class PageSaveRequest extends BaseModelSaveRequest {


	protected $target = "pages";
	protected $targetClass = \App\Models\App\Base\Page::class;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	VALIDATE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function rules(): array {

		return $this->translate([

			...$this->getBaseRules(['page']),

			// page properties
			'title' => 			'bail|nullable|string|max:150|translate',

			// relations
			'items' => 			$this->rule('input-relation'),
			'items.*.id' => 	$this->rule('input-relation-item',true,'items'),
		]);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	ERROR MESSAGES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// rename variables for form error messages
	public function attributes(): array {

		return [
			...parent::attributes(),
			// 'start' => 	trans('Startdatum'),
		];
	}


	public function messages(): array {

		return [
			...parent::messages(),
		];
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
