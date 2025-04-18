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
	use App\Http\Resources\Base\FragmentResource;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ItemResource extends BaseModelResource {


	public function getProps(Request $request): array {

		return [

			// item properties
			'title' =>		$this->title,
			'richtext' =>	$this->richtext,
			'file' =>		$this->file,

			// relations
			'fragments' =>	$this->getChildRelation(FragmentResource::class,'fragments'),

			// backend properties
			//$this->addBackendProperties(['approved' => $this->approved]),
		];
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
