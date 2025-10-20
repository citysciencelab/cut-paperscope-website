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
		import { ref, onMounted, onUnmounted, watch } from 'vue';
		import { storeToRefs } from 'pinia';
		import convert from 'color-convert';

		// Cesium
		import * as Cesium from 'cesium';
		import "cesium/Build/Cesium/Widgets/widgets.css";
		
		// app
		import { useConfig } from '@global/composables/useConfig';
		import { useApi } from '@global/composables/useApi';
		import { useVisualizerStore } from '@app/stores/VisualizerStore';
		import PSObject from '@app/components/visualizer/PSObject.js';


		/////////////////////////////////
		// INIT
		/////////////////////////////////
		
		const { apiGetResponse } = useApi();
		const { baseUrl, tilesetUrl } = useConfig();


		/////////////////////////////////
		// PROJECT
		/////////////////////////////////

		const visualizerStore = useVisualizerStore();
		const { project, simulation, resetFocus } = storeToRefs(visualizerStore);

		function initProject() {

			if(!map || !project.value || !terrainLoaded.value || areaInitialized.value) { return; }

			initArea();
			focus();
			updateScene();
			updateSimulation();
		}


		watch(project, () => {

			if(!project.value) { return; }
			areaInitialized.value ? updateScene() : initProject()
		});


		/////////////////////////////////
		// 3D MAP
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

			initProject();
			//initTesting();
		}


		function destroyMap() {

			map?.destroy();
		}


		onMounted(initMap);
		onUnmounted(destroyMap);


		/////////////////////////////////
		// RENDER
		/////////////////////////////////

		const entities = ref([]);

		async function updateScene() {

			if(!terrainLoaded.value || !project.value.mapping) { return; }

			// remove all old entities
			map.entities.removeAll();
			entities.value = [];

			// iterate all items in scene
			for(const f of project.value.scene?.features ?? []) {

				var psObject = new PSObject(f, map, project.value.mapping);
				if(!psObject.mapping) { continue; }
				
				// add to 3d map
				const entity = psObject.create3dFeature();
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
					initProject();
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
		// FOCUS
		/////////////////////////////////

		function focus() {
			
			project.value ? focusProject() : focusDefault();
		}


		function focusProject() {

			const start = [project.value.start_longitude, project.value.start_latitude];
			const end = [project.value.end_longitude, project.value.end_latitude];

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


		function focusDefault() {

			map.scene.camera.lookAt(
				Cesium.Cartesian3.fromDegrees(10.005, 53.555),
				new Cesium.HeadingPitchRange(-0.3, Cesium.Math.toRadians(-28), 1500)
			);
		}

		
		watch(resetFocus, focus);


		/////////////////////////////////
		// AREA
		/////////////////////////////////

		var areaRectangle = null;
		const areaInitialized = ref(false);

		function initArea() {

			// boundings
			var start = [project.value.start_latitude, project.value.start_longitude];
			var end = [project.value.end_latitude, project.value.end_longitude];
			areaRectangle = Cesium.Rectangle.fromDegrees(start[1], start[0], end[1], end[0]);

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
		// SIMULATION
		/////////////////////////////////

		var simulationSource = null;
		var simulationLayer = null;
		
		function updateSimulation() {

			if(!simulation.value) { 
				if(simulationSource) { map.dataSources.remove(simulationSource, true); simulationSource = null; }
				if(simulationLayer) { map.scene.imageryLayers.remove(simulationLayer, true); simulationLayer = null; }
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

		async function onSimulationLoaded(response) {
			
			const data = response?.data;
			if(!data) { return; }

			// reset layer
			if(simulationSource) { map.dataSources.remove(simulationSource, true); }	
			if(simulationLayer) { map.scene.imageryLayers.remove(simulationLayer, true); }

			if(data.type == 'geojson-features' && data.geojson) {
				
				simulationSource = await Cesium.GeoJsonDataSource.load(data.geojson,{
					clampToGround: true,
					markerSize: 10
				});

				applyColorRamp(simulationSource.entities.values, data.geojson);

				map.dataSources.add(simulationSource);
			}
			else if(data.type == 'wms' && data.url) {

				const id = (simulation.value.isUmp ? 'CUT:':'') + simulation.value.id;
			
				const provider = new Cesium.WebMapServiceImageryProvider({
					url: data.url,
					layers: id,
					parameters: {
						format: 'image/png',
						SINGLETILE: false,
						transparent: true,
						STYLES: '',
						width: 256,
						height: 256,
					}
				});

				simulationLayer = new Cesium.ImageryLayer(provider);
				map.scene.imageryLayers.add(simulationLayer);
			}
		}


		function applyColorRamp(entities, geojson) {

			entities.forEach((f,i) => {
				
				const offset = entities.length > 1 && geojson.features.length > 1 ? i/(entities.length-1) : 0;
				
				// calculate color from hue (hsl to rgb)
				const h = 100 * (1 - offset);
				const [r, g, b] = convert.hsl.rgb([h, 100, 50]);
				const a = 0.4 - (offset * 0.3);
				const color = `rgba(${r}, ${g}, ${b}, ${a})`;

				f.polygon.material = Cesium.Color.fromCssColorString(color);
			});
		}


		watch(simulation, updateSimulation);


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


