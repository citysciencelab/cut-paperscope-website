<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\App;

	// Laravel
	use App\Http\Controllers\App\AppController;
	{{includesLaravel}}

	// App
	use App\Models\App\{{ModelClass}};
	use App\Http\Resources\{{ModelClass}}Resource;
	use App\Http\Resources\{{ModelClass}}ListResource;
	{{includesApp}}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class {{ModelClass}}Controller extends AppController {

	// model classes
	protected $modelClass = {{ModelClass}}::class;
	protected $modelResourceClass = {{ModelClass}}Resource::class;
	protected $modelListResourceClass = {{ModelClass}}ListResource::class;

	// model relations
	protected $modelRelations = [{{relations}}];
	protected $modelListRelations = [];

	// return model list with pagination
	protected $paginator = false;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
