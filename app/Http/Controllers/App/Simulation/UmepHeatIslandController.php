<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\App\Simulation;

	// Laravel
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Facades\Log;

	// App
	use App\Models\App\Simulation;
	use App\Jobs\Simulation\UmepHeatmapSimulation;
	use App\Http\Requests\App\Simulation\UmepHeatIslandExecuteRequest;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	UMEP HEAT ISLAND SIMULATION CONTROLLER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class UmepHeatIslandController extends BaseSimulationController {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SIMULATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function execute(Simulation $simulation): void {

		UmepHeatmapSimulation::dispatch($simulation);
	}

	public function setResults(Simulation $simulation): void {

		if (!Storage::exists('simulations/' . $simulation->id . '/umep/layer/heatmap_layer.jpg')) {
			Log::error("UMEP Heat Island simulation results not found for simulation ID: {$simulation->id}");
			return;
		}

		$simulation->results = [
			'type' => 'wms',
			'data' => [
				'url' => config('app.url') . 'ogc/result',
				'layer' => $simulation->id
			]
		];
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	OGC API INTERFACE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getData(): array {

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

	public function getInputs(): array {

		return [
			'resolution' => [
				'title' => 'Resolution',
				'description' => 'Resolution of the simulation in meters.',
				'required' => true,
				'maxOccurrences' => 1,
				'minOccurrences' => 1,
				'metadata' => null,
				'schema' => [ 'type' => 'number', 'minimum' => 1, 'maximum' => 50]
			],
		];
	}

	public function getOutputs(): array {

		return [
			"heatmap" => [
				'title' => 'Heatmap',
				'description' => 'The resulting heatmap of the simulation.',
				'schema' => [
					'type' => 'string',
					'format' => 'uri',
					'contentMediaType' => 'image/jpg',
					'example' => config("app.url").'simulation/image/{id}'
				],
			],
		];
	}

	public function getExample(): array {

		return [
			'inputs' => [
				'resolution' => 10,
				'project_id' => '123e4567-e89b-12d3-a456-426614174000',
			],
			'mode' => 'async',
		];
	}

	public function getValidationRequestClass(): string {

		return UmepHeatIslandExecuteRequest::class;
	}
}
