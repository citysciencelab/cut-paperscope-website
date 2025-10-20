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

		// vue
		import { onMounted, onUnmounted, watch } from 'vue';
		import { storeToRefs } from 'pinia';
		import convert from 'color-convert';

		// OpenLayers
		import OSM from 'ol/source/OSM.js';
		import { Map, View } from 'ol/index.js';
		import { useGeographic } from 'ol/proj.js';
		import VectorSource from 'ol/source/Vector.js';
		import VectorLayer from 'ol/layer/Vector.js';
		import TileWMS from 'ol/source/TileWMS.js';
		import TileLayer from 'ol/layer/Tile.js';
		import { GeoJSON } from 'ol/format.js';
		import Feature from 'ol/Feature.js';
		import { Polygon } from 'ol/geom.js';
		import { Style, Stroke, Fill } from 'ol/style.js';
		import '@node_modules/ol/ol.css';

		// App
		import { useApi } from '@global/composables/useApi';
		import { useVisualizerStore } from '@app/stores/VisualizerStore';
		import PSObject from '@app/components/visualizer/PSObject.js';
		

		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { apiGetResponse } = useApi();


		/////////////////////////////////
		// PROJECT
		/////////////////////////////////

		const visualizerStore = useVisualizerStore();
		const { project, simulation, resetFocus } = storeToRefs(visualizerStore);


		function initProject() {

			if(!map || !project.value || areaFeature) { return; }

			initArea();
			focus();
			updateScene();
			updateSimulation();
		}


		watch(project, () => {

			if(!project.value) { return; }
			areaFeature ? updateScene() : initProject()
		});


		/////////////////////////////////
		// 2D MAP
		/////////////////////////////////

		var map = null;
		var vectorLayer = null;
		var vectorSource = null;


		function initMap() {

			// init coordinate reference system
			useGeographic();

			// init layer
			const tileLayer = new TileLayer({ source: new OSM() });
			vectorSource = new VectorSource();
			vectorLayer = new VectorLayer({ source: vectorSource });
			
			map = new Map({
				target: 'visualizer-map',
				layers: [tileLayer, vectorLayer],
				controls: [],
				view: new View({
					zoom: 14,
					center: [9.99, 53.565],
				})
			});
			
			initProject();
		}


		function destroyMap() {

			map?.dispose();
		}


		onMounted(initMap);
		onUnmounted(destroyMap);


		/////////////////////////////////
		// RENDER
		/////////////////////////////////

		async function updateScene() {

			if(!project.value?.mapping) { return; }

			// reset source
			vectorSource.getFeatures().forEach(f => f.ol_uid != areaFeature.ol_uid ? vectorSource.removeFeature(f) : null);

			// iterate all items in scene
			for(const f of project.value.scene?.features ?? []) {
				
				// paperscope object
				var psObject = new PSObject(f, map, project.value.mapping);
				if(!psObject.mapping) { continue; }

				// add feature
				const feature = await psObject.create2dFeature();
				vectorSource.addFeature(feature);
			}
		}


		/////////////////////////////////
		// FOCUS
		/////////////////////////////////

		function focus() {
			
			project.value ? focusProject() : focusDefault();
		}


		function focusProject() {

			const extent = areaFeature.getGeometry().getExtent();
			map.getView().fit(extent, {padding: [20, 50, 40, 50]});
		}


		function focusDefault() {

			map.getView().setCenter([10.005, 53.555]);
			map.getView().setZoom(14);
		}


		watch(resetFocus, focus);


		/////////////////////////////////
		// AREA
		/////////////////////////////////

		var areaFeature = null;

		function initArea() {

			// boundings
			var start = [project.value.start_latitude, project.value.start_longitude];
			var end = [project.value.end_latitude, project.value.end_longitude];

			// init shape
			const geometry = new Polygon([[
				[start[1], start[0]],
				[end[1], start[0]],
				[end[1], end[0]],
				[start[1], end[0]],
				[start[1], start[0]]
			]]);

			// OpenLayers feature
			areaFeature = new Feature({ geometry });
			areaFeature.setStyle(new Style({
				fill: new Fill({ color: 'rgba(255, 255, 255, 0.1)' }),
				stroke: new Stroke({ color: 'rgba(0, 255, 255, 1.0)', width: 5 })
			}));

			vectorSource.addFeature(areaFeature);
		}


		/////////////////////////////////
		// SIMULATION
		/////////////////////////////////
		
		var simulationSource = null;
		var simulationLayer = null;

		function updateSimulation() {

			if(!simulation.value) { 
				if(simulationSource) { simulationSource?.clear(); simulationSource = null; }
				if(simulationLayer) { map.removeLayer(simulationLayer); simulationLayer = null; }
				return; 
			}

			if(!simulation.value.isUmp) {
				
				apiGetResponse('api.ogc.job.results', {id: simulation.value.id}, onSimulationLoaded);
			}
			else {
				
				const data = { type: "wms", url: "https://scenarioexplorer.comodeling.city/geoserver/CUT/wms" };
				onSimulationLoaded({ data });
			}
		}


		function onSimulationLoaded(response) {
			
			const data = response?.data;
			if(!data) { return; }

			// reset layer
			if(simulationSource) { simulationSource?.clear(); }
			if(simulationLayer) { map.removeLayer(simulationLayer); }
		
			if(data.type == 'geojson-features' && data.geojson) {
				
				// create features
				const features = new GeoJSON().readFeatures(data.geojson);
				applyColorRamp(features);
				
				// create layer
				simulationSource = new VectorSource(); 
				simulationSource.addFeatures(features); 
				simulationLayer = new VectorLayer({ source: simulationSource });
			}
			else if(data.type == 'wms' && data.url) {

				const id = (simulation.value.isUmp ? 'CUT:':'') + simulation.value.id;
				
				simulationSource = new TileWMS({
					url: data.url,
					params: { 'LAYERS': id, 'VERSION': '1.1.1', 'WIDTH': 256, 'HEIGHT': 256, },
					ratio: 1,
					serverType: 'geoserver',
					projection: 'EPSG:4326',
				});
				simulationLayer = new TileLayer({ source: simulationSource });
			}
			else {
				console.error('Unsupported simulation result type:', data);
				return;
			}

			// add layer to map	
			const layers = map.getLayers();
			layers.insertAt(1, simulationLayer);
		}


		function applyColorRamp(features) {

			features.forEach((f,i) => {
				
				const offset = features.length > 1 ? i/(features.length-1) : 0;
				
				// calculate color from hue (hsl to rgb)
				const h = 90 * (1 - offset);
				const [r, g, b] = convert.hsl.rgb([h, 100, 50]);
				const a = 0.4 - (offset * 0.3);
				const color = `rgba(${r}, ${g}, ${b}, ${a})`;

				f.setStyle(new Style({
					fill: new Fill({ color }),
					stroke: new Stroke({ color: `rgb(${parseInt(r*0.7)}, ${parseInt(g*0.7)}, ${parseInt(b*0.7)})`, width: 1 }),
					zIndex: Math.round((1 - offset) * 100)
				}));
			});
		}


		watch(simulation, updateSimulation);


	</script>
