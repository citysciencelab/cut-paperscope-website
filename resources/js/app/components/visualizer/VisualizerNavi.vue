<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="visualizer-navi">
			<p class="visualizer-navi-title">
				<btn class="small" icon="btn-reset" :label="t('Ansicht zurücksetzen')" @click="focus"/>
			</p>
			<div class="visualizer-navi-buttons">
				<btn :icon="socketConnected ? 'btn-connected' : 'btn-disconnected'" :class="['small secondary socket',{'disconnected': !socketConnected}]" @click="toggleWebsocket"/>
				<btn class="small" icon="btn-fullscreen" :label="fullscreenLabel" @click="toggleFullscreen"/>
				<btn class="small" icon="btn-simulation" label="Simulation" @click="openSimulation"/>
				<btn class="small" icon="btn-change" :label="toggleLabel" @click="toggleMode"/>
			</div>
		</div>


		<popup ref="simulationPopup" class="popup-simulation">

			<p v-if="simulationResult" class="textlink" style="margin-bottom:30px" @click="showSimulation(null)">Aktuelle Simulation ausblenden</p>
			<simulation :project="project"/>

		</popup>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, computed, onMounted, useTemplateRef, provide, inject, nextTick } from 'vue';
		import { useBroadcast } from '@global/composables/useBroadcast';
		import { useApi } from '@resources/js/global/composables/useApi';
		import { useConfig } from '@global/composables/useConfig';
		import { useLanguage } from '@global/composables/useLanguage';

		import * as Cesium from 'cesium';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			map: {type: Object, required: true},
		});

		const project = inject('project');
		const { baseUrl } = useConfig();
		const { t } = useLanguage();


		/////////////////////////////////
		// FOCUS
		/////////////////////////////////

		function focus() {

			project.value ? focusProject() : focusDefault();
		}

		function focusDefault() {

			if(is2D.value) {
				props.map.scene.camera.setView({
					destination: Cesium.Cartesian3.fromDegrees(10.005, 53.555, 1500),
					orientation: {
						heading: Cesium.Math.toRadians(0),
						pitch: Cesium.Math.toRadians(-90),
						roll: 0.0
					}
				});
			}
			else {
				props.map.scene.camera.lookAt(
					Cesium.Cartesian3.fromDegrees(10.005, 53.555),
					new Cesium.HeadingPitchRange(-0.3, Cesium.Math.toRadians(-28), 1500)
				);
			}
		}

		function focusProject() {

			const start = [project.value.start_longitude, project.value.start_latitude];
			const end = [project.value.end_longitude, project.value.end_latitude];

			if(is2D.value) {
				props.map.scene.camera.setView({
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
				props.map.scene.camera.flyToBoundingSphere(boundingSphere, {
					duration: 0.0,
					offset: new Cesium.HeadingPitchRange(-0.3, Cesium.Math.toRadians(-28), 600)
				});
			}
		}

		onMounted(focus);
		defineExpose({focus});


		/////////////////////////////////
		// 2D / 3D
		/////////////////////////////////

		const toggleLabel = ref("2D");
		const is2D = ref(false);

		function toggleMode() {

			is2D.value = !is2D.value;
			toggleLabel.value = is2D.value ? "3D" : "2D";

			focus();

			if(is2D.value) {
				props.map.scene.camera.switchToOrthographicFrustum();
			}
			else {
				props.map.scene.camera.switchToPerspectiveFrustum();
			}
		}


		/////////////////////////////////
		// SIMULATION
		/////////////////////////////////

		const simulationPopup = useTemplateRef('simulationPopup');

		async function openSimulation() {

			simulationPopup.value.open();
		}


		/////////////////////////////////
		// SIMULATION RESULTS
		/////////////////////////////////

		const simulationResult = ref(null);

		function showSimulation(jobId,isUmp = false) {

			// remove old layer
			if(simulationResult.value) {
				props.map.scene.imageryLayers.remove(simulationResult.value, true);
			}

			// clear simulation
			if(!jobId) {
				simulationResult.value = null;
				return;
			}

			// add new layer
			const wmsUrl = isUmp ? "https://scenarioexplorer.comodeling.city/geoserver/CUT/wms" : baseUrl+'ogc/result';
			const provider = new Cesium.WebMapServiceImageryProvider({
				url : wmsUrl,
				layers : isUmp ? "CUT:"+jobId : jobId,
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
			props.map.scene.imageryLayers.add(simulationResult.value);

			simulationPopup.value.close();
		}

		provide('showSimulation', showSimulation);


		/////////////////////////////////
		// FULLSCREEN
		/////////////////////////////////

		const fullscreenLabel = ref("Fullscreen");
		const isFullscreen = ref(false);

		function toggleFullscreen() {

			if (isFullscreen.value) {
				document.exitFullscreen();
			}
			else {
				document.documentElement.requestFullscreen();
			}

			u('#app').toggleClass('fullscreen', !isFullscreen.value);

			isFullscreen.value = !isFullscreen.value;
			fullscreenLabel.value = isFullscreen.value ? "Exit Fullscreen" : "Fullscreen";
		}


		/////////////////////////////////
		// BRODCAST
		/////////////////////////////////

		const { socketConnected, toggleWebsocket } = useBroadcast();


	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {

			},
			"en": {

			}
		}
	</i18n>

