<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Models\App\Base;

	// App
	use App\Models\BaseModel;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class Item extends BaseModel {

	// the attributes that are mass assignable.
	protected $childFillable = [
		'title', 'richtext', 'file'
	];

	protected $translateFillable = [

	];

	// properties for model features
	public static $useSlug = true;
	public static $usePublished = true;
	public static $useSearch = true;

	// cast properties to correct type
	protected $casts = [];


	public function __construct(array $attributes = []) {

		parent::__construct($attributes);

		$this->mergeFillable($this->childFillable, $this->translateFillable);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// default relation with intermediate table (InputRelation)
	public function pages() {

		return $this->getManyRelation('App\Models\App\Base\Page');
	}


	// single relation to a specific model
	/*
	public function author() {
		return $this->getSingleRelation('App\Models\Auth\User', 'author_id');
	}*/


	// relation for child models. Directly attached to parent model (InputChildModel)
	public function fragments() {

		return $this->getChildRelation('App\Models\App\Base\Fragment');
	}


	public function deleteRelations() {

		$this->pages()->detach();
		$this->fragments()->delete();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
