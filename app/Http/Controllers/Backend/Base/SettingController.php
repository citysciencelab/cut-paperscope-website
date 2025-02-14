<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\Backend\Base;

	// Laravel
	use App\Http\Controllers\Backend\BackendController;
	use Illuminate\Http\JsonResponse;

	// App
	use App\Models\Backend\Setting;
	use App\Http\Resources\Base\SettingResource;
	use App\Http\Resources\Base\SettingListResource;
	use App\Http\Requests\Backend\Base\SettingSaveRequest;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class SettingController extends BackendController {

	// model classes
	protected $modelClass = Setting::class;
	protected $modelResourceClass = SettingResource::class;
	protected $modelListResourceClass = SettingListResource::class;

	// model relations
	protected $modelRelations = [];
	protected $modelListRelations = [];

	// return model list with pagination
	protected $paginator = true;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SAVE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function save(SettingSaveRequest $request): JsonResponse {

		$validated = $request->validated();

		$setting  = $this->saveBaseModel($request);

		// save setting properties
		$setting->data_type		= $validated->data_type;
		$setting->category		= $validated->category;
		$setting->identifier 	= $validated->identifier;
		$setting->reference 	= $this->createReference($validated->category, $validated->identifier);

		foreach($this->langKeys as $lang) {
			$setting['content'.$lang] = $validated->{'content'.$lang};
		}

		$setting->save();

		return $this->getBackend($setting->id);
	}


	protected function createReference(string $category, string $identifier) {

		$category 	= trim( strtolower($category) );
		$identifier = trim( strtolower($identifier) );

		// replace special chars with underscore
		$category 	= preg_replace('/[\.\-]/', '_', $category);
		$identifier = preg_replace('/[\.\-]/', '_', $identifier);

		$reference = $category . '.' . $identifier;

		// replace double spaces
		$reference = preg_replace('/\s+/', ' ', $reference);

		// replace spaces with underscore
		$reference = preg_replace('/\s/', '_', $reference);

		return $reference;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
