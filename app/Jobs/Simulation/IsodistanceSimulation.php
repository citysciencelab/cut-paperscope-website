<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Jobs\Simulation;

	// Laravel
    use Illuminate\Contracts\Queue\ShouldBeUnique;
    use Illuminate\Support\Facades\Http;
	use Illuminate\Support\Facades\Log;

	// App
	use App\Jobs\Base\BaseJob;
	use App\Models\App\Project;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class IsodistanceSimulation extends BaseJob implements ShouldBeUnique
{


	public function uniqueId(): string {

		return 'run-simulation-' . $this->target->id;
	}

	public function failed(\Throwable $exception): void {

		Log::critical('Job failed: IsodistanceSimulation. Exception: ' . $exception->getMessage());
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HANDLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function handle(): void {

		parent::handle();

		// update simulation status
		if($this->target->status == 'running') { return; }
		$this->target->status = 'running';
		$this->target->save();

		// execute simulation
		$url = "https://ors.comaps.eu/v2/isochrones/" . ($this->target->params['costing']);

		$project = Project::find($this->target->params['project_id']);
		$locations = [];

		if (!$project || !$project->scene) {
			Log::critical("IsodistanceSimulation: Project with ID " . $this->target->params['project_id'] . " not found.");
			$this->target->status = 'failed';
			$this->target->save();
			return;
		}

		// Get location coordinates with optional filters
		$startType = $this->target->params['startType'] ?? null;
		$startColor = $this->target->params['startColor'] ?? null;
		$sceneLocations = $project->getLocationCoordinates($startType, $startColor);

		foreach ($sceneLocations as $location) {
			$locations[] = [
				'lon' => $location['longitude'],
				'lat' => $location['latitude']
			];
		}

		if (empty($locations)) {
			Log::critical("IsodistanceSimulation: No valid locations found.");
			$this->target->status = 'failed';
			$this->target->save();
			return;
		}

		// Build payload
        $payload = [
            'locations' => array_map(function($location) {
                return [$location['lon'], $location['lat']];
            }, $locations),
			'range_type' => $this->target->params['range_type'],
            'range' => $this->target->params['range'],
        ];

		// Send Request
        $response = Http::withHeaders([
            'Authorization' => config('app.ors_api_key'),
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

		// Handle Response
		if (!$response->successful()) {
			$this->target->status = 'failed';
			Log::critical('Job failed: IsodistanceSimulation. HTTP error: ' . $response->body());
			Log::critical("Request body: " . json_encode($payload, JSON_PRETTY_PRINT));
			return;
		}

		$this->target->status = 'successful';
		$this->target->results = [
			"type" => "geojson-features",
			"geojson" => $response->json()
		];

		$this->target->save();
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
