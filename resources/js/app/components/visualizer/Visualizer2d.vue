<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div id="visualizer-map"></div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

	import { ref, onMounted, onUnmounted, watch } from 'vue';
	import { useConfig } from '@global/composables/useConfig';
	import { useApi } from '@global/composables/useApi';
	import { useVisualizerStore } from '@app/stores/VisualizerStore';
	import PSObject from '@app/components/visualizer/PSObject.js';
	import { bakeHeatmapColorsIntoGeoJSON } from '@app/components/visualizer/HeatmapHelper.js';

	import { Map, View } from 'ol/index.js';
	import TileLayer from 'ol/layer/Tile.js';
	import ImageLayer from 'ol/layer/Image.js';
	import OSM from 'ol/source/OSM.js';
	import { useGeographic } from 'ol/proj.js';
	import { GeoJSON } from 'ol/format.js';
	import Feature from 'ol/Feature.js';
	import { Polygon } from 'ol/geom.js';
	import { Style, Stroke, Fill } from 'ol/style.js';
	import VectorSource from 'ol/source/Vector.js';
	import VectorLayer from 'ol/layer/Vector.js';
	import TileWMS from 'ol/source/TileWMS.js';
	import '@node_modules/ol/ol.css';


	/////////////////////////////////
	// INIT
	/////////////////////////////////

	const { apiGetResponse } = useApi();
	const { baseUrl } = useConfig();
	const visualizerStore = useVisualizerStore();

	defineExpose({
		focus,
		showSimulation
	});


	/////////////////////////////////
	// PROJECT MANAGEMENT
	/////////////////////////////////


	let projectWatcher = null;
	let mapInitialized = ref(false);

	function startProjectWatch() {

		if (projectWatcher) return; // Prevent multiple watchers

		projectWatcher = watch(visualizerStore.project, (newProject) => {
			if (mapInitialized.value) {
				updateProject(newProject);
			}
		}, { immediate: true });
	}

	function updateProject(newProject) {

		if (!newProject || !mapInitialized.value || !map) return;

		initArea();
		updateScene(newProject.mapping);
		focus();

		// Load existing simulation if available
		if (visualizerStore.hasSimulation) return;

		const sim = visualizerStore.currentSimulation;

		if (!sim || !sim.jobId) return;

		showSimulation(sim.jobId, sim.isUmp);
	}


	/////////////////////////////////
	// MAP
	/////////////////////////////////

	var map = null;
	var vectorLayer = null;
	var vectorSource = null;

	async function initMap() {

		useGeographic();

		// container for content
		vectorSource = new VectorSource();
		vectorLayer = new VectorLayer({ source: vectorSource });
		map = new Map({
			target: 'visualizer-map',
			layers: [
				new TileLayer({ source: new OSM() }),
				vectorLayer
			],
			controls: [],
			view: new View({
				zoom: 14,
				center: [9.99, 53.565],
			})
		});

		// Wait for map to finish first render before allowing drawing
		map.once('rendercomplete', () => {
			mapInitialized.value = true;

			// If project is already set, update now
			if (visualizerStore.project) {
				updateProject(visualizerStore.project);
			}
		});

		startProjectWatch();
	}

	function destroy() {

		projectWatcher = null;

		if (!map) return;

		// Clean up cross image layers
		const layersToRemove = [];
		map.getLayers().forEach(layer => {
			if (layer instanceof ImageLayer && layer.get('crossObject')) {
				layersToRemove.push(layer);
			}
		});
		layersToRemove.forEach(layer => map.removeLayer(layer));

		map?.dispose();
		map = null;
	}

	onMounted(initMap);
	onUnmounted(destroy);


	/////////////////////////////////
	// RENDER
	/////////////////////////////////

	async function updateScene(mapping) {

		if(!visualizerStore.project || !map) { return; }

		// Clear existing features except area
		const features = vectorSource.getFeatures();
		features.forEach(feature => {
			if (!feature.get('isArea')) {
				vectorSource.removeFeature(feature);
			}
		});

		// Clear existing cross image layers
		const layersToRemove = [];
		map.getLayers().forEach(layer => {
			if (layer instanceof ImageLayer && layer.get('crossObject')) {
				layersToRemove.push(layer);
			}
		});
		layersToRemove.forEach(layer => map.removeLayer(layer));

		// iterate all items in scene
		for(const f of visualizerStore.project.scene?.features ?? []) {
			var psObject = new PSObject(f, map, mapping);
			if(!psObject.mapping) { continue; }

			const result = await psObject.get2D();

			// Handle image layers (for cross shapes)
			if (result instanceof ImageLayer) {
				map.addLayer(result);
			}
			// Handle regular features
			else {
				vectorSource.addFeature(result);
			}
		}
	}


	/////////////////////////////////
	// FOCUS
	/////////////////////////////////

	function focus() {
		visualizerStore.project ? focusProject() : focusDefault();
	}

	function focusDefault() {

		if (!map) return;

		map.getView().setCenter([10.005, 53.555]);
		map.getView().setZoom(14);
	}

	function focusProject() {

		if (!map || !visualizerStore.project) return;

		const start = [visualizerStore.project.start_longitude, visualizerStore.project.start_latitude];
		const end = [visualizerStore.project.end_longitude, visualizerStore.project.end_latitude];

		// Calculate center point
		const centerLon = (start[0] + end[0]) / 2;
		const centerLat = (start[1] + end[1]) / 2;

		// Set view to center of project area
		map.getView().setCenter([centerLon, centerLat]);
		map.getView().setZoom(17);
	}


	/////////////////////////////////
	// AREA
	/////////////////////////////////

	const areaInitialized = ref(false);

	function initArea() {

		if (!visualizerStore.project || areaInitialized.value) return;

		var start = [visualizerStore.project.start_latitude, visualizerStore.project.start_longitude];
		var end = [visualizerStore.project.end_latitude, visualizerStore.project.end_longitude];

		const area = new Polygon([[
			[start[1], start[0]],
			[end[1], start[0]],
			[end[1], end[0]],
			[start[1], end[0]],
			[start[1], start[0]]
		]]);
		const feature = new Feature({ geometry: area });
		feature.set('isArea', true); // Mark as area feature
		feature.setStyle(new Style({
			fill: new Fill({ color: 'rgba(255, 255, 255, 0.1)' }),
			stroke: new Stroke({ color: 'rgba(0, 255, 255, 1.0)', width: 5 })
		}));
		vectorSource.addFeature(feature);
		areaInitialized.value = true;
	}


	/////////////////////////////////
	// SIMULATION RESULTS
	/////////////////////////////////

	const UMP_RESULTS = { type: "wms", url: "https://scenarioexplorer.comodeling.city/geoserver/CUT/wms" };

	async function showSimulation(jobId, isUmp = false) {

		// Save simulation state to store
		visualizerStore.setSimulation(jobId, isUmp);

		var results = isUmp ? UMP_RESULTS : null;

		if (!isUmp) {
			await apiGetResponse(`api.ogc.job.results`, {id: jobId}, response => {
				if (response.status !== 200) {
					console.error("No results found for job:", jobId);
					return;
				}

				results = response.data;
			});
		}

		switch (results?.type) {
			case "wms":
				await showWMSSimulation(results.url, jobId, isUmp);
				break;
			case "geojson-features":
				await showGeoJSONFeatures(results.geojson);
				break;
			default:
				console.error("Unsupported result type:", results?.type);
				return;
		}
	}

	async function showWMSSimulation(url, jobID, isUmp = false) {

		if (!url || !jobID) return;

		// remove old layer
		map.getLayers().forEach(layer => {
			if (layer instanceof TileLayer && layer.getSource() instanceof TileWMS) {
				map.removeLayer(layer);
			}
		});

		var layer = new TileLayer({
			source: new TileWMS({
				url: url,
				params: {
					'LAYERS': isUmp ? "CUT:"+jobID : jobID,
					'VERSION': '1.1.1',
					'WIDTH': 256,
					'HEIGHT': 256,
				},
				ratio: 1,
				serverType: 'geoserver',
				projection: 'EPSG:4326',
			}),
			crossObject: true // Mark as cross object
		});

		map.getLayers().insertAt(1, layer);
	}

	async function showGeoJSONFeatures(features) {

		if (!features) return;

		// Clear existing layer
		map.getLayers().forEach(layer => {
			if (
				layer instanceof VectorLayer &&
				layer.getSource() instanceof VectorSource &&
				layer.get('className') === 'geojson-features'
			) {
				map.removeLayer(layer);
			}
		});

		const processedFeatures = bakeHeatmapColorsIntoGeoJSON(features);
		const geoJsonFeatures = new GeoJSON().readFeatures(processedFeatures);

		// Add new features with baked-in styling
		const vectorLayer = new VectorLayer({
			source: new VectorSource({
				features: geoJsonFeatures,
			}),
			style: (feature) => {
				// Use baked-in colors from the feature properties
				const fillColor = feature.get('_heatmap_fill_color');
				const strokeColor = feature.get('_heatmap_stroke_color');

				return new Style({
					fill: new Fill({ color: fillColor }),
					stroke: new Stroke({ color: strokeColor, width: 2 })
				});
			}
		});

		vectorLayer.set('className', 'geojson-features');
		map.getLayers().insertAt(1, vectorLayer);
	}

</script>
