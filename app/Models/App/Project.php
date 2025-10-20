<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Models\App;

	// App
	use App\Models\BaseModel;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class Project extends BaseModel {

	// the attributes that are mass assignable.
	protected $childFillable = [
		'title', 'ratio', 'scene', 'mapping', 'visualizer_settings',
	];

	protected $translateFillable = [
		'description'
	];

	// properties for model features
	public static $useSlug = true;
	public static $usePublished = false;
	public static $useSearch = true;

	// cast properties to correct type
	protected $casts = [
		'scene' => 'array',
		'mapping' => 'array',
		'visualizer_settings' => 'array',
	];


	public function __construct(array $attributes = []) {

		parent::__construct($attributes);

		$this->mergeFillable($this->childFillable, $this->translateFillable);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	GETTER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * Get location coordinates by extracting the middle point (centroid) of each feature from the project's scene
	 *
	 * @param string|null $geometryType Filter by geometry type
	 * @param string|null $color Filter by color property
	 * @return array Array of coordinates with feature UIDs
	 */
	
	public function getLocationCoordinates($geometryType = null, $color = null) {

		// Get the scene data
		$scene = $this->scene;

		if (!$scene || !isset($scene['features']) || !is_array($scene['features'])) {
			return [];
		}

		$coordinates = [];

		foreach ($scene['features'] as $feature) {
			if (!isset($feature['geometry']) || !isset($feature['geometry']['coordinates'])) {
				continue;
			}

			$geometry = $feature['geometry'];
			$featureCoords = $geometry['coordinates'];
			$uid = $feature['properties']['uid'] ?? null;

			// Apply geometry type filter
			if ($geometryType !== null && $geometryType !== 'all') {
				$featureShape = $this->resolveShapeFromMapping($feature['properties']['shape'] ?? null);
				if ($featureShape !== $geometryType) {
					continue;
				}
			}

			// Apply color filter
			if ($color !== null && $color !== 'all') {
				$featureColor = $this->resolveColorFromMapping($feature['properties']['color'] ?? null);
				if ($featureColor !== $color) {
					continue;
				}
			}

			// Calculate centroid based on geometry type
			$centroid = $this->calculateCentroid($geometry['type'], $featureCoords);

			if ($centroid) {
				$coordinates[] = [
					'uid' => $uid,
					'longitude' => $centroid[0],
					'latitude' => $centroid[1],
					'properties' => $feature['properties'] ?? []
				];
			}
		}

		return $coordinates;
	}


	/**
	 * Calculate the centroid of a geometry
	 *
	 * @param string $type Geometry type (Point, Polygon, etc.)
	 * @param array $coordinates Coordinate data
	 * @return array|null [longitude, latitude] or null if invalid
	 */
	
	private function calculateCentroid($type, $coordinates) {

		switch ($type) {
			case 'Point':
				return $coordinates;

			case 'Polygon':
				return $this->calculatePolygonCentroid($coordinates);

			case 'MultiPolygon':
				// For multi-polygons, calculate centroid of all rings
				$allPoints = [];
				foreach ($coordinates as $polygon) {
					$ring = $polygon[0] ?? [];
					$allPoints = array_merge($allPoints, $ring);
				}
				return $this->calculatePolygonCentroid($allPoints);

			default:
				return null;
		}
	}


	/**
	 * Calculate centroid of a polygon ring using the arithmetic mean
	 *
	 * @param array $ring Array of [longitude, latitude] coordinates
	 * @return array|null [longitude, latitude] or null if invalid
	 */
	
	private function calculatePolygonCentroid($ring) {

		// Return coords if polygon is point
		if (count($ring) === 1) {
			return $ring[0];
		}

		if (empty($ring) || count($ring) < 3) {
			return null;
		}

		$totalLng = 0;
		$totalLat = 0;
		$validPoints = 0;

		// Check if polygon is closed (first and last points are the same)
		$isClosed = count($ring) > 3 && $ring[0] === $ring[count($ring) - 1];
		$pointCount = $isClosed ? count($ring) - 1 : count($ring);

		for ($i = 0; $i < $pointCount; $i++) {
			if (!isset($ring[$i][0]) || !isset($ring[$i][1])) {
				continue;
			}
			$totalLng += $ring[$i][0];
			$totalLat += $ring[$i][1];
			$validPoints++;
		}

		if ($validPoints < 3) {
			return null;
		}

		return [
			$totalLng / $validPoints,  // longitude
			$totalLat / $validPoints   // latitude
		];
	}


	/**
	 * Resolve shape type from mapping based on shape index
	 *
	 * @param int|null $shapeIndex
	 * @return string|null
	 */
	
	private function resolveShapeFromMapping($shapeIndex) {

		if ($shapeIndex === null || !isset($this->mapping) || !is_array($this->mapping)) {
			return null;
		}

	       $shapeMap = [
		       0 => 'rectangle',
		       1 => 'circle',
		       2 => 'triangle',
		       3 => 'cross',
		       4 => 'organic',
		       5 => 'street',
	       ];

	       return $shapeMap[$shapeIndex] ?? null;
	}


	/**
	 * Resolve color from mapping based on color index
	 *
	 * @param int|null $colorIndex
	 * @return string|null
	 */
	
	private function resolveColorFromMapping($colorIndex) {

		if ($colorIndex === null) {
			return null;
		}

		$colorMap = [
			0 => 'black',
			1 => 'blue',
			2 => 'green',
			3 => 'yellow',
		];

		return $colorMap[$colorIndex] ?? null;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function fragments() {

		return $this->getChildRelation('App\Models\App\Base\Fragment');
	}


	public function user() {

		return $this->getSingleRelation('App\Models\Auth\User', 'user_id');
	}


	public function deleteRelations() {

		$this->fragments()->delete();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
