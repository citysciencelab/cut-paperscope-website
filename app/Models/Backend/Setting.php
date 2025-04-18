<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Models\Backend;

	// Laravel
	use Illuminate\Support\Facades\Auth;

	// App
	use App\Models\BaseModel;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class Setting extends BaseModel {

	// The attributes that are mass assignable.
	protected $childFillable = [
		'data_type', 'category', 'identifier', 'reference',
	];

	protected $translateFillable = [
		'content',
	];

	// properties for model features
	public static $useSlug = false;
	public static $usePublished = false;
	public static $useSearch = false;

	// cast properties to correct type
	protected $casts = [];


	public function __construct(array $attributes = []) {

		parent::__construct($attributes);

		$this->mergeFillable($this->childFillable, $this->translateFillable);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
