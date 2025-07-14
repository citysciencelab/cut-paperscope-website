<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="project-mapping">

			<div class="project-mapping-map" id="project-map"></div>

			<div class="project-mapping-popup" v-fade="selectedEntity">
				<span>
					<btn icon="btn-save" class="secondary small remove-longitude" @click="moveLongitude(-1)"/>
					<btn icon="btn-save" class="secondary small add-longitude" @click="moveLongitude(1)"/>
					<btn icon="btn-save" class="secondary small remove-latitude" @click="moveLatitude(-1)"/>
					<btn icon="btn-save" class="secondary small add-latitude" @click="moveLatitude(1)"/>
				</span>
				<span>
					<btn icon="btn-delete" class="secondary small" @click="confirmDelete"/>
				</span>
			</div>
			<popup-modal ref="deleteModal"/>

			<btn v-if="form.id" :href="downloadUrl" :label="t('Download GeoJson')" class="btn-download" icon="btn-download"/>

		</div>


	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, computed, useTemplateRef, onMounted, onUnmounted, inject } from 'vue';
		import { useConfig } from '@global/composables/useConfig';
		import { useLanguage } from '@global/composables/useLanguage';
		import PSObject from '@app/components/visualizer/PSObject.js';

		import * as Cesium from 'cesium';
		import "cesium/Build/Cesium/Widgets/widgets.css";


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const form = inject('form');
		const { baseUrl } = useConfig();
		const { t } = useLanguage();
		const downloadUrl = computed(() => baseUrl + `project/geojson/${form.value.slug}`);


		/////////////////////////////////
		// MAP
		/////////////////////////////////

		const mapLoaded = ref(false);
		var map = null;
		const is2D = ref(false);


		async function initMap() {

			window.CESIUM_BASE_URL = baseUrl + 'cesium/';
			Cesium.Ion.defaultAccessToken = import.meta.env.VITE_CESIUM_ION_TOKEN;

			map = new Cesium.Viewer("project-map", {
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
			focusProject();
			initInteractions();

			// map settings
			map.clock.currentTime = Cesium.JulianDate.fromIso8601("2013-06-25T12:00:00Z");
			map.scene.globe.tileCacheSize = 1000;
			map.scene.globe.depthTestAgainstTerrain = true;
			map.scene.light.intensity = 3.2;
			map.scene.light.color = Cesium.Color.fromCssColorString('#F9E6C7');
			mapLoaded.value = true;
		}


		onMounted(initMap);
		onUnmounted(() => map?.destroy());


		/////////////////////////////////
		// AREA
		/////////////////////////////////

		var areaRectangle = null;

		function initArea() {

			var start = [form.value.start_latitude, form.value.start_longitude];
			var end = [form.value.end_latitude, form.value.end_longitude];
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
		}


		/////////////////////////////////
		// TILESETS
		/////////////////////////////////

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


		/////////////////////////////////
		// RENDER
		/////////////////////////////////

		const areaInitialized = ref(false);
		const entities = ref([]);

		async function updateScene() {

			if(!form.value || !map || !terrainLoaded.value) { return; }

			// init area
			if(!areaInitialized.value) {
				initArea();
				areaInitialized.value = true;
			}

			// remove all old entities
			map.entities.removeAll();
			entities.value = [];

			// iterate all items in scene
			for(const f of form.value.scene?.features ?? []) {

				var psObject = new PSObject(f, map, form.value.mapping);
				if(!psObject.mapping) { continue; }

				const entity = psObject.addEntity();
				entities.value.push(entity);
			}
		}


		function focusProject() {

			const start = [form.value.start_longitude, form.value.start_latitude];
			const end = [form.value.end_longitude, form.value.end_latitude];

			if(is2D.value) {
				map.scene.camera.setView({
					destination: Cesium.Rectangle.fromDegrees(start[0], start[1], end[0], end[1]),
					orientation: {
						heading: Cesium.Math.toRadians(0),
						pitch: Cesium.Math.toRadians(-90),
						roll: 0.0
					},
				});
			}
			else {
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
		}


		/////////////////////////////////
		// INTERACTIONS
		/////////////////////////////////

		const selectedEntity = ref(null);
		let originalMaterial = null;


		function initInteractions() {

			const handler = new Cesium.ScreenSpaceEventHandler(map.scene.canvas);

			handler.setInputAction((click) => {

				// deselect previous entity
				if(selectedEntity.value) {

					if(selectedEntity.value.model) { selectedEntity.value.model.color = originalMaterial; }
					else if(selectedEntity.value.polygon) { selectedEntity.value.polygon.material = originalMaterial; }

					selectedEntity.value = null;
					originalMaterial = null;
				}

				// get entity
				const pickedObject = map.scene.pick(click.position);
				if(!pickedObject || !Cesium.defined(pickedObject.id)) { return; }
				selectedEntity.value = pickedObject.id;

				// highlight selection
				if(selectedEntity.value.polygon) {
					originalMaterial = selectedEntity.value.polygon.material;
					selectedEntity.value.polygon.material = Cesium.Color.CYAN.withAlpha(0.7);
				}
				else if(selectedEntity.value.model) {
					originalMaterial = selectedEntity.value.model.color;
					selectedEntity.value.model.color = Cesium.Color.CYAN.withAlpha(0.7);
				}

			}, Cesium.ScreenSpaceEventType.LEFT_CLICK);
		}


		/////////////////////////////////
		// MOVE
		/////////////////////////////////

		function moveLatitude(direction) {

			if(!selectedEntity.value) { return; }

			// update form
			const object = form.value.scene.features.find(f => f.properties.uid === selectedEntity.value.id);
			for(const point of object.geometry.coordinates) {
				point[1] += direction * 0.00001; // move latitude by 0.0001 degrees
			}

			// update entity
			if(selectedEntity.value.polygon) {
				selectedEntity.value.polygon.hierarchy = Cesium.Cartesian3.fromDegreesArray(object.geometry.coordinates.flat());
			}
			else if(selectedEntity.value.model && selectedEntity.value.position) {
				const position = selectedEntity.value.position.getValue(map.clock.currentTime);
				const height = position ? Cesium.Cartographic.fromCartesian(position).height : 0;
				selectedEntity.value.position = Cesium.Cartesian3.fromDegrees(
					object.geometry.coordinates[0][0],
					object.geometry.coordinates[0][1],
					height
				);
			}
		}


		function moveLongitude(direction) {

			if(!selectedEntity.value) { return; }

			// update form
			const object = form.value.scene.features.find(f => f.properties.uid === selectedEntity.value.id);
			for(const point of object.geometry.coordinates) {
				point[0] += direction * 0.00001; // move longitude by 0.0001 degrees
			}

			// update entity
			if(selectedEntity.value.polygon) {
				selectedEntity.value.polygon.hierarchy = Cesium.Cartesian3.fromDegreesArray(object.geometry.coordinates.flat());
			}
			else if(selectedEntity.value.model && selectedEntity.value.position) {
				const position = selectedEntity.value.position.getValue(map.clock.currentTime);
				const height = position ? Cesium.Cartographic.fromCartesian(position).height : 0;
				selectedEntity.value.position = Cesium.Cartesian3.fromDegrees(
					object.geometry.coordinates[0][0],
					object.geometry.coordinates[0][1],
					height
				);
			}
		}


		/////////////////////////////////
		// DELETE
		/////////////////////////////////

		const deleteModal = useTemplateRef('deleteModal');


		function confirmDelete() {

			deleteModal.value.open({
				title: t("Objekt löschen"),
				copy: t("delete.copy"),
				alert: true,
				confirmLabel: t("Objekt löschen"),
				callback: () => deleteObject()
			});
		}


		function deleteObject() {

			if(!selectedEntity.value) { return; }

			// remove entity from map
			map.entities.remove(selectedEntity.value);
			entities.value = entities.value.filter(e => e !== selectedEntity.value);

			// remove from form
			const index = form.value.scene.features.findIndex(f => f.properties.uid === selectedEntity.value.id);
			if(index >= 0) { form.value.scene.features.splice(index, 1); }

			selectedEntity.value = null;
			updateScene();
		}


	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
				"delete.copy": "Möchtest dieses Objekt wirklich löschen?",
			},
			"en": {
				"Objekt löschen": "Delete object",
				"delete.copy": "Do you really want to delete this object?",
			}
		}
	</i18n>

