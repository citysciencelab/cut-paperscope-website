<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\App;

	// Laravel
	use App\Http\Controllers\App\AppController;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Str;
	use Illuminate\Http\Request;
	use Illuminate\Http\JsonResponse;
	use TCPDF;

	// App
	use App\Models\App\Project;
	use App\Http\Resources\ProjectResource;
	use App\Http\Resources\ProjectListResource;
	use App\Http\Resources\ProjectSceneResource;
	use App\Http\Requests\App\ProjectSaveRequest;
	use App\Http\Requests\App\ProjectSceneSaveRequest;
	use App\Jobs\Base\ProcessSharingUpload;
	use App\Jobs\ProcessProjectUpload;
	use App\Http\Requests\App\Base\ListWithFilterRequest;
	use App\Events\ProjectSceneUpdated;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ProjectController extends AppController {

	// model classes
	protected $modelClass = Project::class;
	protected $modelResourceClass = ProjectResource::class;
	protected $modelListResourceClass = ProjectListResource::class;

	// model relations
	protected $modelRelations = ['user','fragments'];
	protected $modelListRelations = ['user'];

	// return model list with pagination
	protected $paginator = false;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MODEL LIST FILTER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function applyFilterWhere(&$stmt, ListWithFilterRequest &$request, bool $usePublished) {

		$stmt = parent::applyFilterWhere($stmt, $request, $usePublished);

		$stmt->where('user_id', Auth::id());

		return $stmt;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SAVE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function save(ProjectSaveRequest $request): JsonResponse {

		$validated = $request->validated();

		$project = $this->saveBaseModel($request);

		// save project properties
		$project->public 			= true;
		$project->title				= $validated->title;
		$project->start_longitude	= $validated->start_longitude;
		$project->start_latitude	= $validated->start_latitude;
		$project->end_longitude		= $validated->end_longitude;
		$project->end_latitude		= $validated->end_latitude;

		// scene
		$project->ratio				= $this->calculateRatio($project);
		$project->mapping			= $validated->mapping;

		// save translatable properties
		$lang = config('app.fallback_locale');
		$project['description_'.$lang]	= $validated->{'description_'.$lang};

		// relations
		$project->user_id = Auth::id();

		$project->save();

		// add jobs to queue
		if(!$validated->preview) {
			ProcessSharingUpload::dispatch($project);
			ProcessProjectUpload::dispatch($project);
		}

		return $this->getPublic($project->id);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SCENE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getScene(string $slug): JsonResponse {

		$project = Project::whereSlug($slug)->first();

		return $this->responseGet($project, ProjectSceneResource::class);
	}


	public function saveScene(ProjectSceneSaveRequest $request): JsonResponse {

		$validated = $request->validated();

		$project = Project::whereSlug($validated->slug)->first();
		if(!$project) { return $this->responseError(); }

		$start = [$project->start_longitude, $project->start_latitude];
		$end = [$project->end_longitude, $project->end_latitude];

		// create geojson
		$geojson = [
			'type' => 'FeatureCollection',
			'features' => []
		];

		// add features
		foreach($validated->scene as $f) {

			$feature = [
				'type' => 'Feature',
				'properties' => [
					'uid' => $f['uid'],
					'shape' => $f['shape'],
					'color' => $f['color'],
				],
				'geometry' => [
					"type" => "Polygon",
					"coordinates" => []
				],
			];

			// add coordinates
			foreach($f['points'] as $p) {
				$x = $start[0] + ($end[0] - $start[0]) * $p['x'];
				$y = $start[1] + ($end[1] - $start[1]) * $p['y'];
				$feature['geometry']['coordinates'][] = [$x, $y];
			}

			$geojson['features'][] = $feature;
		}

		$project->scene = $geojson;
		$project->updated_at = now();
		$project->save();

 		ProjectSceneUpdated::dispatch($project);

		return $this->responseSuccess();
	}


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	GEOJSON
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function downloadGeoJson(string $slug) {

		$project = Project::whereSlug($slug)->first();
		if(!$project) { return abort(404); }

		$geojson = $project->scene;

		// return geojson as file download
		header('Content-Type: application/json');
		header('Content-Disposition: attachment; filename="'.Str::kebab($project->title).'.geojson"');

		echo json_encode($geojson, JSON_PRETTY_PRINT);
	}


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	PDF
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getPdf(Request $request) {

		$slug = $request->input('slug');
		if(!$slug) { return abort(404); }

		// project
		$project = Project::whereSlug($slug)->first();
		if(!$project) { return abort(404); }

		// init document
		$pdf = new TCPDF();
		$pdf->SetAuthor('HCU');
		$pdf->SetTitle('PaperScope');
		$pdf->SetMargins(27.7, 20, 15);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetAutoPageBreak(true, 30);
		$pdf->setImageScale(2);
		$pdf->setJPEGQuality(75);
		$pdf->AddPage('L', 'A4');
		$pdf->setFontSubsetting(true);

		// aruco
		$pdf->Image(public_path('img/app/pdf/aruco.png'), 13, 13, 30, 30, 'PNG');

		// calculate width and height for border based on ratio
		$ratio = $this->calculateRatio($project);
		$width = 297 - 26;
		$height = $width / $ratio;
		if($height > 210 - 26) {
			$height = 210 - 26;
			$width = $height * $ratio;
		}

		// rectangle border
		$pdf->setLineStyle(['width' => 0.2, 'color' => [220, 220, 220]]);
		$pdf->Rect(13, 13, $width, $height);

		// small black circles at each corner
		$pdf->setFillColor(0, 0, 0);
		$pdf->Circle(13 + $width, 13, 0.7, 0, 360, 'F');
		$pdf->Circle(13, 13 + $height, 0.7, 0, 360, 'F');
		$pdf->Circle(13 + $width, 13 + $height, 0.7, 0, 360, 'F');

		$pdf->Output(Str::kebab($project->title).'.pdf');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MAP
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getMap(Request $request) {

		$slug = $request->input('slug');
		if(!$slug) { return abort(404); }

		// get project
		$project = Project::whereSlug($slug)->first();
		if(!$project) { return abort(404); }

		// bounding box
		$sLong = $project->start_longitude;
		$sLat = $project->start_latitude;
		$eLong = $project->end_longitude;
		$eLat = $project->end_latitude;

		// get ratio between long and lat
		$ratio = $this->calculateRatio($project);

		$data = [
			'service' => 'WMS',
			'version' => '1.1.1',
			'request' => 'GetMap',
			'styles' => '',
			'format' => 'image/jpeg',
			'layers' => 'stadtplan',
			'bbox' => implode(',',[$sLong, $eLat, $eLong, $sLat]),
			'width' => $ratio < 1 ? intval(1024 * $ratio) : 1024,
			'height' => $ratio >= 1 ? intval(1024 / $ratio) : 1024,
			'srs' => 'EPSG:4326',
		];

		$url = "https://geodienste.hamburg.de/HH_WMS_Cache_Stadtplan";
		$url .= '?'.http_build_query($data);

		// get image
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$image = curl_exec($ch);
		if(curl_errno($ch)) { return abort(404); }
		curl_close($ch);

		// return image
		header('Content-Type: image/jpeg');
		echo $image;
	}


	private function calculateRatio(Project $project) {

		$startLat = $project->start_latitude;
		$startLong = $project->start_longitude;
		$endLat = $project->end_latitude;
		$endLong = $project->end_longitude;

		$distanceLong = $this->haversineDistance($startLat, $startLong, $startLat, $endLong);
		$distanceLat = $this->haversineDistance($startLat, $startLong, $endLat, $startLong);

		return $distanceLong / $distanceLat;
	}


	private function haversineDistance($lat1, $lon1, $lat2, $lon2) {

		// radius of the Earth in meters
		$earthRadius = 6371000;

		$dLat = deg2rad($lat2 - $lat1);
		$dLon = deg2rad($lon2 - $lon1);

		$a = sin($dLat / 2) * sin($dLat / 2) +
			cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
			sin($dLon / 2) * sin($dLon / 2);

		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));

		return $earthRadius * $c;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
