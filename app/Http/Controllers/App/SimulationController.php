<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\App;

	// Laravel
	use App\Http\Controllers\App\AppController;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Response;
	use Illuminate\Http\RedirectResponse;
	use Illuminate\Support\Facades\Auth;

	// App
	use App\Models\App\Simulation;
	use App\Http\Resources\SimulationResource;
	use App\Http\Resources\SimulationListResource;
	use App\Http\Requests\App\SimulationExecuteRequest;
	use App\Http\Requests\App\SimulationSaveRequest;
	use App\Jobs\ProcessSimulationUpload;
	use App\Jobs\RunSimulation;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class SimulationController extends AppController {

	// model classes
	protected $modelClass = Simulation::class;
	protected $modelResourceClass = SimulationResource::class;
	protected $modelListResourceClass = SimulationListResource::class;

	// model relations
	protected $modelRelations = ['project'];
	protected $modelListRelations = [];

	// return model list with pagination
	protected $paginator = false;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	GET
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function image(string $id): Response {

		// get simulation
		$simulation = Simulation::find($id);
		if(!$simulation) { return response()->make(null, 404); }

		// get image file
		$imgUrl = "http://localhost:8088/result/".$id;
		$imgFile = file_get_contents($imgUrl);
		if(!$imgFile) { return response()->make(null, 404); }

		// return image response
		return response($imgFile, 200)
			->header('Content-Type', 'image/jpg')
			->header('Cache-Control', 'no-cache, no-store, must-revalidate')
			->header('Pragma', 'no-cache')
			->header('Expires', '0');
	}


	public function project(string $id): Response {

		// get simulation
		$simulation = Simulation::find($id);
		if(!$simulation) { return response()->make(null, 404); }

		// get image file
		$zipUrl = "http://localhost:8088/project/".$id;
		$zipFile = file_get_contents($zipUrl);
		if(!$zipFile) { return response()->make(null, 404); }

		// return image response
		return response($zipFile, 200)
			->header('Content-Type', 'application/zip')
			->header('Content-Disposition', 'attachment; filename="project_'.$id.'.zip"')
			->header('Cache-Control', 'no-cache, no-store, must-revalidate')
			->header('Pragma', 'no-cache')
			->header('Expires', '0');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SAVE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function save(SimulationSaveRequest $request): JsonResponse {

		$validated = $request->validated();

		$simulation = $this->saveBaseModel($request);

		// save simulation properties
		$simulation->public = true;
		$simulation->model = $validated->model;
		$simulation->params = $this->getInputJson($validated->params);
		$simulation->project_id = $validated->project_id;

		$simulation->save();

		// add jobs to queue
		if(!$validated->preview) {
			ProcessSimulationUpload::dispatch($simulation);
		}

		$this->startSimulation($simulation);

		return $this->getPublic($simulation->id);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	UPDATE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function update(SimulationSaveRequest $request): JsonResponse {

		$validated = $request->validated();

		// get simulation
		$simulation = Simulation::where(['id' => $validated->id, 'project_id' => $validated->project_id])->first();
		if(!$simulation) { return $this->responseError(404, "Simulation not found"); }

		// update simulation
		$simulation->params = $validated->params;
		$simulation->status = $validated->status;
		$simulation->save();

		return $this->responseData($simulation);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	OGC API PROCESSES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getProcesses(): JsonResponse {

		$processes = [
			$this->getProcessData('umep:heat_island'),
		];

		return response()->json($processes, 200);
	}


	public function getProcess(string $model): JsonResponse {

		$process = $this->getProcessData($model);

		// inputs
		$process['inputs'] = [
			[
				'resolution' => [
					'title' => 'Resolution',
					'description' => 'Resolution of the simulation in meters.',
					'required' => true,
					'maxOccurrences' => 1,
					'minOccurrences' => 1,
					'metadata' => null,
					'schema' => [ 'type' => 'number', 'minimum' => 5, 'maximum' => 50]
				],
				'project_id' => [
					'title' => 'Project ID',
					'description' => 'ID of the PaperScope project to run the simulation on.',
					'required' => true,
					'maxOccurrences' => 1,
					'minOccurrences' => 1,
					'metadata' => null,
					'schema' => [ 'type' => 'string', 'format' => 'uuid']
				],
			]
		];

		// example
		$process['example'] = [
			'inputs' => [
				'resolution' => 10,
			],
			'mode' => 'async',
		];

		// outputs
		$process['outputs'] = [
			"heatmap" => [
				'title' => 'Heatmap',
				'description' => 'The resulting heatmap of the simulation.',
				'schema' => [
					'type' => 'string',
					'format' => 'uri',
					'contentMediaType' => 'image/jpg',
					'example' => config("app.url").'simulation/image/{id}'
				],
			]
		];

		return response()->json($process, 200);
	}


	private function getProcessData(string $model): array {

		// get process data
		if($model == 'umep:heat_island') {
			return [
				'version' => '0.1.0',
				'id' => 'umep:heat_island',
				'title' => 'UMEP Urban Heat Island',
				'description' => 'Urban Heat Island effect simulation',
				'keywords' => ['umep', 'heat island', 'paperscope'],
				'links' => [],
				"jobControlOptions" => "async-execute",
				"outputTransmission" => ["value"],
				"paperscope" => true,
			];
		}

		return [];
	}


	public function executeProcess(string $model, SimulationExecuteRequest $request): RedirectResponse {

		$validated = $request->validated();

		// create simulation
		$simulation = Simulation::create([
			'name' => $validated->job_name ?? 'Simulation',
			'public' => true,
			'model' => $model,
			'params' => [
				'resolution' => $validated->inputs['resolution'],
				'project_id' => $validated->inputs['project_id'],
			],
			'project_id' => $validated->inputs['project_id']

		]);

		// run simulation
		RunSimulation::dispatch($simulation);

		// redirect to job
		return redirect()->route('api.ogc.job', ['id' => $simulation->id]);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	OGC API JOBS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getJobs(): JsonResponse {

		$data = [];

		$simulations = Simulation::all();
		foreach($simulations as $simulation) {
			$data[] = $this->getSimulationData($simulation);
		}

		return response()->json($data, 200);
	}


	public function getJob(string $id): JsonResponse {

		// get simulation
		$simulation = Simulation::find($id);
		if(!$simulation) { return $this->responseError(404, "Simulation not found"); }

		return response()->json($this->getSimulationData($simulation), 200);
	}


	private function getSimulationData(Simulation $simulation): array {

		return [

			"processId" => $simulation->model,
			"type" => "process",
			"jobID" => $simulation->id,
			"status" => $simulation->status,
			"created" => $simulation->created_at->toIso8601String(),
			"started" => $simulation->created_at->toIso8601String(),
			"updated" => $simulation->updated_at->toIso8601String(),
			"finished" => $simulation->updated_at->toIso8601String(),
			"progress" => $simulation->status == "successful" ? 100 : 0,
			"links" => [],
			"parameters" => [
				"mode" => "async",
				"inputs" => $simulation->params,
			],
			"name" => $simulation->name,
			"process_title" => $simulation->model,
			"process_description" => "Simulation of the ".$simulation->model." model.",
			"user_id" => Auth::id() ?? null,
			"ensembles" => [],
		];
	}


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
