<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Resources\Base;

	// Laravel
	use Illuminate\Http\Request;

	// App
	use App\Http\Resources\Base\BaseModelResource;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class FragmentListResource extends BaseModelResource {


	public function getProps(Request $request): array {

		return [

			// fragment properties
			'template' => $this->template,

			// backend properties
			$this->addBackendProperties([
				'parent_id' => $this->parent_id,
				'parent_type' => $this->parent_type,
			]),
		];
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
