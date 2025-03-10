<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div id="visualizer-map"></div>
		<visualizer-navi ref="navi" v-if="mapLoaded" :map="map"/>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, onMounted, onUnmounted, watch, provide } from 'vue';
		import { useRoute } from 'vue-router';
		import { useApi } from '@global/composables/useApi';
		import { useConfig } from '@global/composables/useConfig';
		import { useBroadcast } from '@global/composables/useBroadcast';
		import PSObject from '@app/components/visualizer/PSObject.js';

		import * as Cesium from 'cesium';
		import "cesium/Build/Cesium/Widgets/widgets.css";


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const route = useRoute();
		const { apiGetSlug } = useApi();
		const { baseUrl } = useConfig();

		const navi = ref(null);


		/////////////////////////////////
		// PROJECT
		/////////////////////////////////

		const project = ref(null);
		const mapping = ref(null);

		var pollingInterval = 0;
		var updateTimestamp = 0;

		provide('project', project);

		function loadProject() {

			if(route.params.slug) {
				apiGetSlug('project' ,onProjectLoaded).catch(error => console.log(error));
			}
		}

		function onProjectLoaded(data) {

			project.value = data;
			mapping.value = data.mapping;

			u('.header-logo').append('<p class="header-logo-title">'+data.title+'</p>');
			navi.value?.focus();
			initBroadcast();
			updateScene();
		}

		function updateProject() {

			apiGetSlug('project' ,data => {

				// update only if changed
				if(updateTimestamp == data.updated_at) { return; }
				updateTimestamp = data.updated_at;

				project.value = data;
				mapping.value = data.mapping;

				updateScene();
			})
			.catch(error => console.log(error));
		}


		/////////////////////////////////
		// MAP
		/////////////////////////////////

		const mapLoaded = ref(false);
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

			await useHamburg3D();

			// map settings
			map.clock.currentTime = Cesium.JulianDate.fromIso8601("2013-12-25T12:00:00Z");
			map.scene.postProcessStages.fxaa.enabled = true;
			map.scene.globe.depthTestAgainstTerrain = true;
			mapLoaded.value = true;

			loadProject();
		}

		function destroy() {

			map.destroy();
			u('.header-logo-title').remove();
		}

		onMounted(initMap);
		onUnmounted(destroy);


		/////////////////////////////////
		// HAMBURG 3D
		/////////////////////////////////

		var hamburgTilesets = [];
		const terrainLoaded = ref(false);

		async function useHamburg3D() {

			// add terrain
			const terrain = "https://daten-hamburg.de/gdi3d/datasource-data/Gelaende";
			const terrainProvider = await Cesium.CesiumTerrainProvider.fromUrl(terrain);
			map.scene.terrainProvider = terrainProvider;

			// add 2d map
			const hamburgMap = "https://geodienste.hamburg.de/HH_WMS_Cache_Stadtplan";
			const provider = new Cesium.WebMapServiceImageryProvider({
				url : hamburgMap,
				layers : 'stadtplan',
				rectangle : Cesium.Rectangle.fromDegrees(8.5, 53.5, 10.5, 54.5),
				parameters: { format: 'image/png', SINGLETILE: false }
			});
			const imageryLayer = new Cesium.ImageryLayer(provider);
			map.scene.imageryLayers.add(imageryLayer);

			// add 3d tileset
			for(var i=1; i<6; i++) {
				const url = "https://daten-hamburg.de/gdi3d/datasource-data/LoD3_tex20cm_Area"+i+"/tileset.json"
				const tileset = await Cesium.Cesium3DTileset.fromUrl(url);
				map.scene.primitives.add(tileset);
				hamburgTilesets.push(tileset);
			}

			setTimeout(()=> {
				terrainLoaded.value = true;
				updateScene();
			},5000);
		}


		/////////////////////////////////
		// RENDER
		/////////////////////////////////

		const areaInitialized = ref(false);

		function updateScene() {

			if(!project.value || !map || !terrainLoaded.value) { return; }

			console.log('updateScene');

			// init area
			if(!areaInitialized.value) {
				drawArea();
				clipArea();
				areaInitialized.value = true;
			}

			// remove all old entities
			map.entities.removeAll();

			// iterate all items in scene
			for(const f of project.value.scene?.features ?? []) {

				var item = new PSObject(f, mapping.value);
				if(!item.mapping) { continue; }

				// add to 3d map
				const entity = item.get3D(map.scene);
				map.entities.add(entity);
			}
		}


		/////////////////////////////////
		// AREA
		/////////////////////////////////

		function drawArea() {

			const start = [project.value.start_longitude, project.value.start_latitude];
			const end = [project.value.end_longitude, project.value.end_latitude];

			// 3D area
			const positions = [
				Cesium.Cartesian3.fromDegrees(start[0], start[1]),
				Cesium.Cartesian3.fromDegrees(start[0], end[1]),
				Cesium.Cartesian3.fromDegrees(end[0], end[1]),
				Cesium.Cartesian3.fromDegrees(end[0], start[1]),
				Cesium.Cartesian3.fromDegrees(start[0], start[1]),
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
		}


		function clipArea() {

			if(hamburgTilesets.length == 0) { return;}

			hamburgTilesets.forEach(t => {

				// bounding sphere of tile is origin
				const origin = t.boundingSphere;
				const originLongLat = Cesium.Cartographic.fromCartesian(origin.center);
				const long = Cesium.Math.toDegrees(originLongLat.longitude);
				const lat = Cesium.Math.toDegrees(originLongLat.latitude);

				// Y negative
				var coords = Cesium.Cartesian3.fromDegrees(long, project.value.end_latitude, originLongLat.height);
				var yn = Cesium.Cartesian3.distance(origin.center, coords);
				yn *= coords.y < origin.center.y ? 1 : -1;

				// Y positive
				coords = Cesium.Cartesian3.fromDegrees(long, project.value.start_latitude, originLongLat.height);
				var yp = Cesium.Cartesian3.distance(origin.center, coords);
				yp *= coords.y > origin.center.y ? 1 : -1;

				// X positive
				coords = Cesium.Cartesian3.fromDegrees(project.value.end_longitude, lat, originLongLat.height);
				var xp = Cesium.Cartesian3.distance(origin.center, coords);
				xp *= coords.x > origin.center.x ? 1 : -1;

				// X negative
				coords = Cesium.Cartesian3.fromDegrees(project.value.start_longitude, lat, originLongLat.height);
				var xn = Cesium.Cartesian3.distance(origin.center, coords);
				xn *= coords.x < origin.center.x ? 1 : -1;

				const planes = [
					new Cesium.ClippingPlane(new Cesium.Cartesian3(0.0, -1.0, 0.0), yn),
					new Cesium.ClippingPlane(new Cesium.Cartesian3(0.0, 1.0, 0.0), yp),
					new Cesium.ClippingPlane(new Cesium.Cartesian3(1.0, 0.0, 0.0), xp),
					new Cesium.ClippingPlane(new Cesium.Cartesian3(-1.0, 0.0, 0.0), xn),
				];

				t.clippingPlanes = new Cesium.ClippingPlaneCollection({
					planes,
					edgeWidth: 1.0,
					edgeColor: Cesium.Color.AQUA,
				});
			});
		}


		/////////////////////////////////
		// BROADCAST
		/////////////////////////////////

		const { socketConnected, subscribeChannel } = useBroadcast();

		function initBroadcast() {

			subscribeChannel('project.'+project.value.slug, onChannelMessage);
		}

		function onChannelMessage(event, data) {

			if(event == 'ProjectSceneUpdated') { updateProject(); }
		}

		watch(socketConnected, value => {

			// activate polling mode if no websocket connection
			clearInterval(pollingInterval);
			if(!value) {
				pollingInterval = setInterval(()=>{ updateProject(); }, 3000);
			}
		});


	</script>


