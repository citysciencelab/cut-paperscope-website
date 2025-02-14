<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\Backend;

	// Laravel
	use App\Http\Controllers\Backend\BackendController;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Http\JsonResponse;

	// App
	use App\Models\App\Project;
	use App\Http\Resources\ProjectResource;
	use App\Http\Resources\ProjectListResource;
	use App\Http\Requests\Backend\ProjectSaveRequest;
	use App\Jobs\Base\ProcessSharingUpload;
	use App\Jobs\ProcessProjectUpload;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ProjectController extends BackendController {

	// model classes
	protected $modelClass = Project::class;
	protected $modelResourceClass = ProjectResource::class;
	protected $modelListResourceClass = ProjectListResource::class;

	// model relations
	protected $modelRelations = ['user','fragments'];
	protected $modelListRelations = ['user'];

	// return model list with pagination
	protected $paginator = true;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SAVE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function save(ProjectSaveRequest $request): JsonResponse {

		$validated = $request->validated();

		$project = $this->saveBaseModel($request);

		// save project properties
		$project->title				= $validated->title;
		$project->start_longitude	= $validated->start_longitude;
		$project->start_latitude	= $validated->start_latitude;
		$project->end_longitude		= $validated->end_longitude;
		$project->end_latitude		= $validated->end_latitude;
		$project->mapping			= $validated->mapping;

		// save translatable properties
		foreach($this->langKeys as $lang) {
			$project['description'.$lang]	= $validated->{'description'.$lang};
		}

		// relations
		$project->user_id = $validated->user_id ?? Auth::id();

		$project->save();

		// add jobs to queue
		ProcessSharingUpload::dispatch($project);
		ProcessProjectUpload::dispatch($project);

		return $this->getBackend($project->id);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
