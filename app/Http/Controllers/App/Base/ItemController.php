<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\App\Base;

	// Laravel
	use App\Http\Controllers\App\AppController;

	// App
	use App\Models\App\Base\Item;
	use App\Http\Resources\Base\ItemResource;
	use App\Http\Resources\Base\ItemListResource;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ItemController extends AppController {

	// model classes
	protected $modelClass = Item::class;
	protected $modelResourceClass = ItemResource::class;
	protected $modelListResourceClass = ItemListResource::class;

	// model relations
	protected $modelRelations = ['fragments'];
	protected $modelListRelations = [];

	// return model list with pagination
	protected $paginator = true;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
