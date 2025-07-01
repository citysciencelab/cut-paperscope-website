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

		import { ref, onMounted, onUnmounted, watch, provide, nextTick } from 'vue';
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
		const { baseUrl, tilesetUrl } = useConfig();

		const navi = ref(null);


		/////////////////////////////////
		// PROJECT
		/////////////////////////////////

		const project = ref(null);
		const mapping = ref(null);

		var pollingInterval = 0;
		var updateTimestamp = 0;


		function loadProject() {

			if(route.params.slug) {
				apiGetSlug('project' ,onProjectLoaded).catch(error => console.log(error));
			}
		}


		function onProjectLoaded(data) {

			project.value = data;
			mapping.value = data.mapping;

			initMap();
			initBroadcast();

			u('.header-logo').append('<p class="header-logo-title">'+data.title+'</p>');
			navi.value?.focus();
		}


		function updateProject() {

			apiGetSlug('project' ,data => {

				// update only if changed
				//if(updateTimestamp == data.updated_at) { return; }
				//updateTimestamp = data.updated_at;

				project.value = data;
				mapping.value = data.mapping;

				updateScene();
			})
			.catch(error => console.log(error));
		}


		provide('project', project);
		onMounted(loadProject);


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

			// update map content
			initArea();
			await useTerrain();
			await useHamburgMap();
			await useHamburg3D();

			// map settings
			map.clock.currentTime = Cesium.JulianDate.fromIso8601("2013-06-25T12:00:00Z");
			map.scene.globe.tileCacheSize = 1000;
			map.scene.globe.depthTestAgainstTerrain = true;
			map.scene.light.intensity = 3.2;
			map.scene.light.color = Cesium.Color.fromCssColorString('#F9E6C7');
			mapLoaded.value = true;

			// testing
			// map.screenSpaceEventHandler.setInputAction(async function (event) {

			// 	const pickedFeature = map.scene.pick(event.position);

			// 	if(pickedFeature.boundingRectangle) {
			// 		const rectangle = pickedFeature.boundingRectangle;
			// 		showRectangle(rectangle, Cesium.Color.RED.withAlpha(0.3));
			// 		return;
			// 	}

			// 	const boundings = pickedFeature.getProperty('boundings');
			// 	if(!boundings) { return; }
			// 	const min = boundings.min;
			// 	const max = boundings.max;

			// 	// check for intersection
			// 	const rectangle = Cesium.Rectangle.fromDegrees(min[1], min[0], max[1], max[0]);
			// 	showRectangle(rectangle, Cesium.Color.YELLOW.withAlpha(0.1));
    		// },
			// Cesium.ScreenSpaceEventType.LEFT_CLICK);
		}


		function destroy() {

			map.destroy();
			u('.header-logo-title').remove();
		}


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

			// LOD2
			//var tilesets = ["https://daten-hamburg.de/gdi3d/datasource-data/LoD2/tileset.json"];

			// load tilesets
			for(var i=0; i<tilesets.length; i++) {
				const tileset = await Cesium.Cesium3DTileset.fromUrl(tilesets[i]);
				map.scene.primitives.add(tileset);
				hamburgTilesets.push(tileset);
				tileset.tileLoad.addEventListener(entityTileIntersection);
			}
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


		/////////////////////////////////
		// RENDER
		/////////////////////////////////

		const areaInitialized = ref(false);
		const entities = ref([]);

		async function updateScene() {

			if(!project.value || !map || !terrainLoaded.value) { return; }

			// init area
			if(!areaInitialized.value) {
				initArea();
				clipArea();
				areaInitialized.value = true;
			}

			// remove all old entities
			map.entities.removeAll();
			entities.value = [];

			// iterate all items in scene
			for(const f of project.value.scene?.features ?? []) {

				var psObject = new PSObject(f, map, mapping.value);
				if(!psObject.mapping) { continue; }

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
		// AREA
		/////////////////////////////////

		var areaRectangle = null;

		function initArea() {

			var start = [project.value.start_latitude, project.value.start_longitude];
			var end = [project.value.end_latitude, project.value.end_longitude];
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
		}


		function clipArea() {

			return; // not needed for now
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

		const { socketConnected, subscribePrivateChannel } = useBroadcast();

		function initBroadcast() {

			subscribePrivateChannel('project.'+project.value.slug, onChannelMessage);
		}

		function onChannelMessage(event, data) {

			if(event == 'ProjectSceneUpdated') { updateProject(); }
		}

		watch(socketConnected, value => {

			// activate polling mode if no websocket connection
			clearInterval(pollingInterval);
			if(!value) {
				console.log('no socket connection, start polling');
				pollingInterval = setInterval(()=>{ updateProject(); }, 3000);
			}
		});


	</script>


