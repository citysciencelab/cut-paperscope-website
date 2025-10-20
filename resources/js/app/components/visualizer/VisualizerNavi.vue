<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="visualizer-navi">
			<p class="visualizer-navi-title">
				<btn class="small" icon="btn-reset" :label="t('Ansicht zurücksetzen')" @click="resetFocus++"/>
			</p>
			<div class="visualizer-navi-buttons">
				<btn :icon="socketConnected ? 'btn-connected' : 'btn-disconnected'" :class="['small secondary socket',{'disconnected': !socketConnected}]" @click="toggleWebsocket"/>
				<btn class="small" icon="btn-fullscreen" :label="fullscreenLabel" @click="toggleFullscreen"/>
				<btn class="small" icon="btn-simulation" label="Simulation" @click="openSimulation"/>
				<btn class="small" icon="btn-change" :label="toggleLabel" @click="toggleMode"/>
			</div>
		</div>


		<popup ref="simulationPopup" class="popup-simulation">

			<p v-if="simulation" class="textlink" style="margin-bottom:30px" @click="hideSimulation">Aktuelle Simulation ausblenden</p>
			<simulation-list :project/>

		</popup>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, computed, watch } from 'vue';
		import { storeToRefs } from '@node_modules/pinia/dist/pinia';

		import { useBroadcast } from '@global/composables/useBroadcast';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useVisualizerStore } from '@app/stores/VisualizerStore';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = useLanguage();

		const visualizerStore = useVisualizerStore();
		const { project, is2dView, simulation, resetFocus } = storeToRefs(visualizerStore);


		/////////////////////////////////
		// 2D / 3D
		/////////////////////////////////

		const toggleLabel = computed(() => is2dView.value ? "3D" : "2D");

		async function toggleMode() {

			visualizerStore.toggleViewMode();
		}


		/////////////////////////////////
		// SIMULATION
		/////////////////////////////////

		const simulationPopup = useTemplateRef('simulationPopup');

		function openSimulation() {

			simulationPopup.value.open();
		}


		function closeSimulation() {

			simulationPopup.value.close();
		}


		function hideSimulation() {

			simulation.value = null;
			closeSimulation();
		}

		watch(simulation, () => {

			if(simulation.value) closeSimulation();
		});


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

