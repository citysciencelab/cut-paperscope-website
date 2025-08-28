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
		import { useRoute } from 'vue-router';
		import { useConfig } from '@global/composables/useConfig';
		import { useApi } from '@global/composables/useApi';
		import { useVisualizerStore } from '@app/stores/VisualizerStore';
		import PSObject from '@app/components/visualizer/PSObject.js';
		import { bakeHeatmapColorsIntoGeoJSON, rgbaStringToCesiumColor } from '@app/components/visualizer/HeatmapHelper.js';

		import * as Cesium from 'cesium';
		import "cesium/Build/Cesium/Widgets/widgets.css";


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const route = useRoute();
		const { apiGetResponse } = useApi();
		const { baseUrl, tilesetUrl } = useConfig();
		const visualizerStore = useVisualizerStore();

		defineExpose({
			focus,
			showSimulation
		});


		/////////////////////////////////
		// PROJECT MANAGEMENT
		/////////////////////////////////

		const mapping = ref(null);
		let projectWatcher = null;

		function startProjectWatch() {

			if (projectWatcher) return; // Prevent multiple watchers

			projectWatcher = watch(visualizerStore.project, updateProject);

			// Check if project already has a value when starting the watch
			if (!visualizerStore.project) return;

			updateProject(visualizerStore.project);
		}

		function updateProject(newProject) {

			if (!newProject) return;

			mapping.value = newProject.mapping;
			initArea();
			updateScene();
			focus();

			// Load existing simulation if available
			if (visualizerStore.hasSimulation) return;

			const sim = visualizerStore.currentSimulation;
			showSimulation(sim.jobId, sim.isUmp);
		}


		/////////////////////////////////
		// MAP
		/////////////////////////////////

		var map = null;

		async function initMap() {

			window.CESIUM_BASE_URL = baseUrl + 'cesium/';
			Cesium.Ion.defaultAccessToken = import.meta.env.VITE_CESIUM_ION_TOKEN;

			map = new Cesium.Viewer("visualizer-map", {
				animation: false,
				baseLayerPicker: false,
				baseLayer: false,
				fullscreenButton: false,
				homeButton: false,
				infoBox: false,
				geocoder: false,
				sceneModePicker: false,
				timeline: false,
				navigationHelpButton: false,
				selectionIndicator: false,
				navigationInstructionsInitiallyVisible: false,
			});

			// update map content
			await useTerrain();
			await useHamburgMap();
			await useHamburg3D();

			// map settings
			map.clock.currentTime = Cesium.JulianDate.fromIso8601("2013-06-25T12:00:00Z");
			map.scene.globe.tileCacheSize = 1000;
			map.scene.globe.depthTestAgainstTerrain = true;
			map.scene.light.intensity = 3.2;
			map.scene.light.color = Cesium.Color.fromCssColorString('#F9E6C7');

			//initTesting();

			startProjectWatch();
		}


		function destroy() {

			projectWatcher = null;
			map?.destroy();
			map = null;
		}

		onMounted(initMap);
		onUnmounted(destroy);


		/////////////////////////////////
		// TILESETS
		/////////////////////////////////

		var hamburgTilesets = [];
		const terrainLoaded = ref(false);


		async function useTerrain() {

			const url = "https://daten-hamburg.de/gdi3d/datasource-data/Gelaende";
			const terrainProvider = await Cesium.CesiumTerrainProvider.fromUrl(url);
			const terrain = new Cesium.Terrain(terrainProvider);

			// wait for terrain to load
			map.scene.globe.tileLoadProgressEvent.addEventListener(function (queuedTileCount) {

				if(map.scene.globe.tilesLoaded && !terrainLoaded.value) {
					terrainLoaded.value = true;
					updateScene();
				}
			});

			map.scene.setTerrain(terrain);
		}


		async function useHamburgMap() {

			// bugfix: intercept missing tiles on geodienste.hamburg.de
			var orgFunc = Cesium.ImageryLayer.prototype._requestImagery;
			Cesium.ImageryLayer.prototype._requestImagery = function(img) {

				if((img.x == 1 || img.x == 2) && img.y == 0) { img.x = 4; img.level = 2; }
				return orgFunc.call(this, img);
			}

			const hamburgMap = "https://geodienste.hamburg.de/HH_WMS_Cache_Stadtplan";
			const provider = new Cesium.WebMapServiceImageryProvider({
				url : hamburgMap,
				layers : 'stadtplan',
				rectangle : Cesium.Rectangle.fromDegrees(9.8, 53.5, 10.5, 53.8),
				parameters: { format: 'image/png', SINGLETILE: false }
			});

			const imageryLayer = new Cesium.ImageryLayer(provider);
			map.scene.imageryLayers.add(imageryLayer);
		}


		async function useHamburg3D() {

			// LOD3
			var tilesets = [];
			for(var i=1; i<6; i++) { tilesets.push(tilesetUrl.replace('1/', i+"/")); }

			// load tilesets
			for(var i=0; i<tilesets.length; i++) {
				const tileset = await Cesium.Cesium3DTileset.fromUrl(tilesets[i]);
				map.scene.primitives.add(tileset);
				hamburgTilesets.push(tileset);
				tileset.tileVisible.addEventListener(entityTileIntersection);
			}
		}


		/////////////////////////////////
		// RENDER
		/////////////////////////////////

		const entities = ref([]);

		async function updateScene() {

			if(!visualizerStore.project || !map || !terrainLoaded.value || !mapping.value) { return; }

			// remove all old entities
			map.entities.removeAll();
			entities.value = [];

			// iterate all items in scene
			for(const f of visualizerStore.project.scene?.features ?? []) {

				var psObject = new PSObject(f, map, mapping.value);
				if(!psObject.mapping) { continue; }

				// add to 3d map
				const entity = psObject.addEntity();
				entities.value.push(entity);
			}

			// update boundings
			hamburgTilesets.forEach(set => set._selectedTiles.forEach(entityTileIntersection) );
		}


		function entityTileIntersection(tile) {

			// skip if tile not intersecting with area or very large
			const rectangle = tile.boundingVolume.rectangle;
			if(rectangle.width * rectangle.height * 1000 > 0.0005) { return; }
			if(!Cesium.Rectangle.simpleIntersection(rectangle, areaRectangle)) { return; }

			// iterate all features
			for(let i=0; i<tile.content.featuresLength; i++) {

				// get boundings of feature
				const feature = tile.content.getFeature(i);
				const boundings = feature.getProperty("boundings");
				if(!boundings) { feature.color = Cesium.Color.RED; continue; }
				const min = boundings.min;
				const max = boundings.max;

				// check for intersection
				const rectangle = Cesium.Rectangle.fromDegrees(min[1], min[0], max[1], max[0]);
				for(let j=0; j < entities.value.length; j++) {

					const entityRectangle = entities.value[j].boundingRectangle;
					if(!entityRectangle) { continue; }

					const intersect = Cesium.Rectangle.intersection(rectangle, entityRectangle);
					if(intersect) { feature.show = false; break; }
					feature.show = true;
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

			map.scene.camera.lookAt(
				Cesium.Cartesian3.fromDegrees(10.005, 53.555),
				new Cesium.HeadingPitchRange(-0.3, Cesium.Math.toRadians(-28), 1500)
			);
		}

		function focusProject() {

			if (!map || !visualizerStore.project) return;

			const start = [visualizerStore.project.start_longitude, visualizerStore.project.start_latitude];
			const end = [visualizerStore.project.end_longitude, visualizerStore.project.end_latitude];

			// camera position
			const boundingSphere = Cesium.BoundingSphere.fromPoints([
				Cesium.Cartesian3.fromDegrees(start[0], start[1], 20),
				Cesium.Cartesian3.fromDegrees(end[0], end[1], 0)
			]);

			// focus
			map.scene.camera.flyToBoundingSphere(boundingSphere, {
				duration: 0.0,
				offset: new Cesium.HeadingPitchRange(-0.3, Cesium.Math.toRadians(-28), 600)
			});
		}


		/////////////////////////////////
		// AREA
		/////////////////////////////////

		var areaRectangle = null;
		const areaInitialized = ref(false);

		function initArea() {

			if (!visualizerStore.project || !map || areaInitialized.value) return;

			var start = [visualizerStore.project.start_latitude, visualizerStore.project.start_longitude];
			var end = [visualizerStore.project.end_latitude, visualizerStore.project.end_longitude];
			areaRectangle = Cesium.Rectangle.fromDegrees(start[1], start[0], end[1], end[0]);
			//showRectangle(areaRectangle, Cesium.Color.RED.withAlpha(0.3));

			const positions = [
				Cesium.Cartesian3.fromDegrees(start[1], start[0]),
				Cesium.Cartesian3.fromDegrees(start[1], end[0]),
				Cesium.Cartesian3.fromDegrees(end[1], end[0]),
				Cesium.Cartesian3.fromDegrees(end[1], start[0]),
				Cesium.Cartesian3.fromDegrees(start[1], start[0]),
			];

			const instance = new Cesium.GeometryInstance({
				geometry : new Cesium.GroundPolylineGeometry({ positions, width : 5.0 }),
			});

			map.scene.groundPrimitives.add(new Cesium.GroundPolylinePrimitive({
				geometryInstances : instance,
				appearance : new Cesium.PolylineMaterialAppearance({
					material: Cesium.Material.fromType('Color', {
						color: Cesium.Color.fromCssColorString('rgba(0, 255, 255, 1.0)')
					})
				})
			}));
			areaInitialized.value = true;
		}


		/////////////////////////////////
		// SIMULATION RESULTS
		/////////////////////////////////

		const UMP_RESULTS = { type: "wms", url: "https://scenarioexplorer.comodeling.city/geoserver/CUT/wms" };

		var simulationResult = ref(null);

		async function showSimulation(jobId, isUmp = false) {

			if (!map) return;

			// Save simulation state to store
			visualizerStore.setSimulation(jobId, isUmp);

			// remove old layer
			if(simulationResult.value) {
				map.scene.imageryLayers.remove(simulationResult.value, true);
			}

			// clear simulation
			if(!jobId) {
				simulationResult.value = null;
				return;
			}

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

		async function showWMSSimulation(url, jobId, isUmp = false) {

			if (!url || !jobId) return;

			const provider = new Cesium.WebMapServiceImageryProvider({
				url: url,
				layers: isUmp ? "CUT:" + jobId : jobId,
				parameters: {
					format: 'image/png',
					SINGLETILE: false,
					transparent: true,
					STYLES: '',
					width: 256,
					height: 256,
				}
			});

			simulationResult.value = new Cesium.ImageryLayer(provider);
			map.scene.imageryLayers.add(simulationResult.value);
		}

		async function showGeoJSONFeatures(features) {

			if (!features) return;

			// Clear existing layer
			map.dataSources.getByName("geojson-features").forEach(ds => {
				map.dataSources.remove(ds, true);
			});

			// First, bake heatmap colors into the GeoJSON features
			const processedFeatures = bakeHeatmapColorsIntoGeoJSON(features);

			// Add new features with heatmap coloring
			const geojsonSource = await Cesium.GeoJsonDataSource.load(processedFeatures, {
				clampToGround: true,
				markerSize: 10
			});
			geojsonSource.name = "geojson-features";

			// Apply heatmap colors to the loaded entities
			const entities = geojsonSource.entities.values;
			for (let i = 0; i < entities.length; i++) {
				const entity = entities[i];
				const fillColorString = entity.properties._heatmap_fill_color?.getValue();
				const strokeColorString = entity.properties._heatmap_stroke_color?.getValue();

				if (fillColorString && strokeColorString) {
					const fillColor = rgbaStringToCesiumColor(fillColorString);
					const strokeColor = rgbaStringToCesiumColor(strokeColorString);

					// Apply colors based on geometry type
					if (entity.polygon) {
						entity.polygon.material = fillColor;
						entity.polygon.outline = true;
						entity.polygon.outlineColor = strokeColor;
					} else if (entity.polyline) {
						entity.polyline.material = strokeColor;
						entity.polyline.width = 3;
					} else if (entity.point) {
						entity.point.color = fillColor;
						entity.point.outlineColor = strokeColor;
						entity.point.outlineWidth = 2;
						entity.point.pixelSize = 8;
					} else if (entity.billboard) {
						entity.billboard.color = fillColor;
					}
				}
			}

			map.dataSources.add(geojsonSource);
		}


		/////////////////////////////////
		// TESTING
		/////////////////////////////////

		function initTesting() {

			map.screenSpaceEventHandler.setInputAction(async function (event) {

				const pickedFeature = map.scene.pick(event.position);

				if(pickedFeature.boundingRectangle) {
					const rectangle = pickedFeature.boundingRectangle;
					showRectangle(rectangle, Cesium.Color.RED.withAlpha(0.3));
					return;
				}

				const boundings = pickedFeature.getProperty('boundings');
				if(!boundings) { return; }
				const min = boundings.min;
				const max = boundings.max;

				// check for intersection
				const rectangle = Cesium.Rectangle.fromDegrees(min[1], min[0], max[1], max[0]);
				showRectangle(rectangle, Cesium.Color.YELLOW.withAlpha(0.1));
			},
			Cesium.ScreenSpaceEventType.LEFT_CLICK);
		}


		function showRectangle(rectangle, color) {

			const west = Cesium.Math.toDegrees(rectangle.west);
			const south = Cesium.Math.toDegrees(rectangle.south);
			const east = Cesium.Math.toDegrees(rectangle.east);
			const north = Cesium.Math.toDegrees(rectangle.north);

			const coords = [
				west, south,
				west, north,
				east, north,
				east, south
			];

			map.entities.add({
				polygon: {
					hierarchy: Cesium.Cartesian3.fromDegreesArray(coords),
					height: 0.0,
					extrudedHeight: 50.0, // 50 meters tall box
					material: color ?? Cesium.Color.RED.withAlpha(0.3),
					outline: true,
					outlineColor: Cesium.Color.PINK
				}
			});
		}

	</script>


