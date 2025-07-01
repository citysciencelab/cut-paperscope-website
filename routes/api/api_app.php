<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// Laravel
	use Illuminate\Support\Facades\Route;

	// App
	use App\Helper\ApiRoutes;
	use App\Http\Controllers\App\Base\ContentController;
	use App\Http\Controllers\App\ProjectController;
	use App\Http\Controllers\App\SimulationController;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	APP PUBLIC
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// Content
	Route::get('content', [ContentController::class,'getPublicContent'])->name('api.content');
	Route::post('contact', [ContentController::class,'contact'])->name('api.contact')->middleware('throttle:critical');

	// Models
	ApiRoutes::setModelAppRoutes('page', false, 'Base\\Page');
	ApiRoutes::setModelAppRoutes('item', false, 'Base\\Item');

	// project
	Route::post('project/scene/save', [ProjectController::class,'saveScene'])->name('api.project.scene.save');
	Route::get('project/scene/{slug}', [ProjectController::class,'getScene'])->name('api.project.scene');
	Route::get('project/{slug}', [ProjectController::class,'getPublicBySlug'])->name('api.project');
	Route::get('project/simulation/{slug}', [ProjectController::class,'getSimulation'])->name('api.project.simulation');

	// simulation
	Route::get('simulation/{id}', [SimulationController::class,'getPublic'])->name('api.simulation');
	Route::post('simulation/save', [SimulationController::class,'save'])->name('api.simulation.save');
	Route::post('simulation/update', [SimulationController::class,'update'])->name('api.simulation.update');

	// [add model app routes]

	// ogc api
	$models = ['umep:heat_island'];
	Route::get('ogc/processes', [SimulationController::class,'getProcesses'])->name('api.ogc.process.list');
	Route::get('ogc/processes/{model}', [SimulationController::class,'getProcess'])->whereIn('model', $models)->name('api.ogc.process');
	Route::post('ogc/processes/{model}/execution', [SimulationController::class,'executeProcess'])->whereIn('model', $models)->name('api.ogc.process.execute');
	Route::get('ogc/jobs', [SimulationController::class,'getJobs'])->name('api.ogc.job.list');
	Route::get('ogc/jobs/job-{id}', [SimulationController::class,'getJob'])->name('api.ogc.job');



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	APP PROTECTED
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	Route::group(['middleware'=>'auth:sanctum'],function(){

		// project
		Route::get('project', [ProjectController::class,'getPublicList'])->name('api.project.list');
		Route::post('project/save', [ProjectController::class,'save'])->name('api.project.save');
		Route::post('project/delete', [ProjectController::class,'deleteModel'])->name('api.project.delete');
	});


