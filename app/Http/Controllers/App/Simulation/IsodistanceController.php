<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\App\Simulation;

	// App
	use App\Models\App\Simulation;
	use App\Jobs\Simulation\IsodistanceSimulation;
	use App\Http\Requests\App\Simulation\IsodistanceExecuteRequest;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	ISODISTANCE SIMULATION CONTROLLER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class IsodistanceController extends BaseSimulationController {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SIMULATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function execute(Simulation $simulation): void {

		IsodistanceSimulation::dispatch($simulation);
	}

	public function setResults(Simulation $simulation): void {

		// Isodistance results are stored directly in the job
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	OGC API INTERFACE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getData(): array {

		return [
			'version' => '0.1.0',
			'id' => 'isodistance',
			'title' => 'Isodistance Simulation',
			'description' => 'Isodistance simulation using OpenRouteService',
			'keywords' => ['isodistance', 'paperscope'],
			'links' => [],
			"jobControlOptions" => "async-execute",
			"outputTransmission" => ["value"],
			"paperscope" => true,
		];
	}

	public function getInputs(): array {

		return [
			'costing' => [
				'title' => 'Costing',
				'description' => 'Costing method for the isodistance simulation.',
				'required' => false,
				'maxOccurrences' => 1,
				'minOccurrences' => 0,
				'metadata' => null,
				'schema' => [ 'type' => 'string', 'enum' => ['auto', 'bicycle', 'bus', 'bikeshare', 'truck', 'hov', 'taxi', 'motor_scooter', 'motorcycle', 'multimodal', 'pedestrian']]
			],
			'range_type' => [
				'title' => 'Range Type',
				'description' => 'Type of range for the isodistance simulation.',
				'required' => true,
				'maxOccurrences' => 1,
				'minOccurrences' => 1,
				'metadata' => null,
				'schema' => [ 'type' => 'string', 'enum' => ['distance', 'time']]
			],
			'range' => [
				'title' => 'Ranges',
				'description' => 'Ranges for the isodistance simulation. Can be either distance or time based on the range type.',
				'required' => true,
				'maxOccurrences' => 1,
				'minOccurrences' => 1,
				'metadata' => null,
				'schema' => [
					'type' => 'array',
					'items' => [
						'type' => 'number',
						'minimum' => 1,
						'maximum' => 10000,
					],
				],
			],
			'startType' => [
				'title' => 'Start Geometry Type',
				'description' => 'Filter starting locations by geometry type.',
				'required' => false,
				'maxOccurrences' => 1,
				'minOccurrences' => 0,
				'metadata' => null,
				'schema' => [ 'type' => 'string', 'enum' => ['all', 'rectangle', 'triangle', 'circle', 'organic', 'cross']]
			],
			'startColor' => [
				'title' => 'Start Color',
				'description' => 'Filter starting locations by color property.',
				'required' => false,
				'maxOccurrences' => 1,
				'minOccurrences' => 0,
				'metadata' => null,
				'schema' => [ 'type' => 'string', 'enum' => ['all', 'black', 'blue', 'green', 'yellow']]
			],
		];
	}

	public function getOutputs(): array {

		return [
			"isodistance" => [
				'title' => 'Isodistance Layer',
				'description' => 'The resulting isodistance geojson data of the simulation.',
				'schema' => [
					'type' => 'string',
					'contentMediaType' => 'application/geo+json',
					'example' => [
						'bbox' => [12.4924, 41.8902, 12.4926, 41.8904],
						'features' => [
							[
								'type' => 'Feature',
								'geometry' => [
									'type' => 'Polygon',
									'coordinates' => [[
										[12.4924, 41.8902],
										[12.4925, 41.8903],
										[12.4926, 41.8904],
										[12.4924, 41.8902]
									]],
								],
								'properties' => [
									'value' => 1000,
									'group_index' => 0,
								],
							],
						],
					],
				],
			],
		];
	}

	public function getExample(): array {

		return [
			'inputs' => [
				'costing' => 'auto',
				'range_type' => 'distance',
				'range' => [1000, 2000, 3000],
				'project_id' => '123e4567-e89b-12d3-a456-426614174000',
				'startType' => 'all',
				'startColor' => 'all',
			],
			'mode' => 'async',
		];
	}

	public function getValidationRequestClass(): string {

		return IsodistanceExecuteRequest::class;
	}
}
