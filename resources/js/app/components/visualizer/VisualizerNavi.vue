<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="visualizer-navi">
			<p class="visualizer-navi-title">
				<btn class="small" icon="btn-reset" :label="t('Ansicht zurücksetzen')" @click="emit('resetView')"/>
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
			<simulation :project="visualizerStore.project"/>

		</popup>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, provide, computed } from 'vue';
		import { useBroadcast } from '@global/composables/useBroadcast';
		import { useConfig } from '@global/composables/useConfig';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useVisualizerStore } from '@app/stores/VisualizerStore';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const emit = defineEmits(['resetView', 'showSimulation']);

		const visualizerStore = useVisualizerStore();
		const { baseUrl } = useConfig();
		const { t } = useLanguage();


		/////////////////////////////////
		// 2D / 3D
		/////////////////////////////////

		const toggleLabel = computed(() => visualizerStore.is2dView ? "3D" : "2D");

		async function toggleMode() {
			const newMode = !visualizerStore.is2dView;
			visualizerStore.setViewMode(newMode);
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

			emit('showSimulation', jobId, isUmp);

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

