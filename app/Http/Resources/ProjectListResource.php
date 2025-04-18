<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Resources;

	// Laravel
	use Illuminate\Http\Request;

	// App
	use App\Http\Resources\Base\BaseModelResource;
	use App\Http\Resources\Auth\UserListResource;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ProjectListResource extends BaseModelResource {


	public function getProps(Request $request): array {

		return [

			// project properties
			'title' =>			$this->title,
			'description' =>	$this->translate('description'),

			// timestamps
			'created_at' =>		$this->created_at,
			'updated_at' =>		$this->updated_at,

			// relations
			'user' =>			$this->getSingleRelation(UserListResource::class,'user'),
			'user_id' =>		$this->user_id,

			// backend properties
			//$this->addBackendProperties(['approved' => $this->approved]),
		];
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
