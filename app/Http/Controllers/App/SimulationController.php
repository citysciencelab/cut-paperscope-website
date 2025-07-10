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
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Facades\Cache;

	// App
	use App\Models\App\Simulation;
	use App\Http\Resources\SimulationResource;
	use App\Http\Resources\SimulationListResource;
	use App\Http\Requests\App\SimulationExecuteRequest;
	use App\Http\Requests\App\SimulationSaveRequest;
	use App\Http\Requests\App\SimulationWmsRequest;
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
//	SIMULATION DATA
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getImage(string $id): Response {

		// get simulation
		$simulation = Simulation::find($id);
		if(!$simulation) { return response()->make(null, 404); }

		// get file
		$file = Storage::get('simulations/' . $simulation->id . '/umep/layer/heatmap_layer.jpg');

		return response($file, 200)
			->header('Content-Type', 'image/jpeg')
			->header('Cache-Control', 'no-cache, no-store, must-revalidate')
			->header('Pragma', 'no-cache')
			->header('Expires', '0');
	}


	public function getProject(string $id): Response {

		// get simulation
		$simulation = Simulation::find($id);
		if(!$simulation) { return response()->make(null, 404); }

		// get file
		$file = Storage::get('simulations/' . $simulation->id . '/project.zip');

		return response($file, 200)
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
		$oldStatus = $simulation->status;

		// update simulation
		$simulation->params = $validated->params;
		$simulation->status = $validated->status;
		$simulation->save();

		// trigger a waiting simulation
		if($oldStatus == 'running' && $simulation->status == 'successful') {
			$newSimulation = Simulation::whereStatus('waiting')->first();
			if($newSimulation) { RunSimulation::dispatch($newSimulation); }
		}

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



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	OGC API RESULT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getResult(SimulationWmsRequest $request): Response {

        $validated = $request->validated();

        $width = $validated->width;
        $height = $validated->height;
        $bbox = explode(',', $validated->bbox); 	// [minLon, minLat, maxLon, maxLat]
        $layers = explode(',', $validated->layers);

        // prepare a transparent canvas
        $destImage = imagecreatetruecolor($width, $height);
        imagesavealpha($destImage, true);
        $transparentColor = imagecolorallocatealpha($destImage, 0, 0, 0, 127);
        imagefill($destImage, 0, 0, $transparentColor);

        // get simulation
        $simulation = Simulation::with('project')->find($layers[0]);
        if(!$simulation || !$simulation->project) {
            return response()->make('Simulation or associated project not found', 404);
        }

		// get project
        $project = $simulation->project;
        $projectBbox = [
            'minLon' => (float)$project->start_longitude,
            'minLat' => (float)$project->start_latitude,
            'maxLon' => (float)$project->end_longitude,
            'maxLat' => (float)$project->end_latitude,
        ];

        // check for intersection
        $reqBbox = array_map('floatval', $bbox);
        $intersects = ($reqBbox[0] < $projectBbox['maxLon'] && $reqBbox[2] > $projectBbox['minLon'] &&
                       $reqBbox[1] < $projectBbox['maxLat'] && $reqBbox[3] > $projectBbox['minLat']);

		// return transparent
	   	if(!$intersects) {
            ob_start();
            imagepng($destImage);
            $imageData = ob_get_clean();
            imagedestroy($destImage);
            return response($imageData, 200)->header('Content-Type', 'image/png');
        }

        // load image data from cache or storage
		$imageData = Cache::rememberForever('simulation-'.$simulation->id.'-heatmap-data', function() use ($simulation) {
			return Storage::get('simulations/' . $simulation->id . '/umep/layer/heatmap_layer.jpg');
		});
		if(!$imageData) {
            return response()->make('Source image not found', 404);
        }

		// create image
		$sourceImage = imagecreatefromstring($imageData);
		if(!$sourceImage) {
            return response()->make('Source image is invalid', 404);
        }

        // calculate dimensions
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);
        $projectLonRange = $projectBbox['maxLon'] - $projectBbox['minLon'];
        $projectLatRange = $projectBbox['maxLat'] - $projectBbox['minLat'];
        $reqLonRange = $reqBbox[2] - $reqBbox[0];
        $reqLatRange = $reqBbox[3] - $reqBbox[1];

        // Find the intersection bounding box
        $interBbox = [
            max($reqBbox[0], $projectBbox['minLon']),
            max($reqBbox[1], $projectBbox['minLat']),
            min($reqBbox[2], $projectBbox['maxLon']),
            min($reqBbox[3], $projectBbox['maxLat'])
        ];

        // crop from the source image (in pixels)
        $srcX = floor(($interBbox[0] - $projectBbox['minLon']) / $projectLonRange * $sourceWidth);
        $srcY = floor(($projectBbox['maxLat'] - $interBbox[3]) / $projectLatRange * $sourceHeight);
        $srcW = floor(($interBbox[2] - $interBbox[0]) / $projectLonRange * $sourceWidth);
        $srcH = floor(($interBbox[3] - $interBbox[1]) / $projectLatRange * $sourceHeight);

        // place cropped part onto the destination tile (in pixels)
        $destX = ceil(($interBbox[0] - $reqBbox[0]) / $reqLonRange * $width);
        $destY = ceil(($reqBbox[3] - $interBbox[3]) / $reqLatRange * $height);
        $destW = ceil(($interBbox[2] - $interBbox[0]) / $reqLonRange * $width);
        $destH = ceil(($interBbox[3] - $interBbox[1]) / $reqLatRange * $height);

        imagecopyresampled(
            $destImage, $sourceImage,
            $destX, $destY, $srcX, $srcY,
            $destW, $destH, $srcW, $srcH
        );

        // output image
        ob_start();
        imagepng($destImage);
        $imageData = ob_get_clean();

        imagedestroy($sourceImage);
        imagedestroy($destImage);

        return response($imageData, 200)->header('Content-Type', 'image/png');
    }



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
