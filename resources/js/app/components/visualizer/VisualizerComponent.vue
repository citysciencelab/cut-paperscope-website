<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<Visualizer2d ref="visualizer2d" v-if="visualizerStore.is2dView"/>
		<Visualizer3d ref="visualizer3d" v-else/>

		<VisualizerNavi ref="navi" @resetView="resetView" @showSimulation="showSimulation"/>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
		import { useRoute } from 'vue-router';
		import { useVisualizerStore } from '@app/stores/VisualizerStore';
		import { useBroadcast } from '@global/composables/useBroadcast';

		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const route = useRoute();
		const visualizerStore = useVisualizerStore();
		const { socketConnected, subscribePrivateChannel } = useBroadcast();

		const navi = ref(null);
		const visualizer2d = ref(null);
		const visualizer3d = ref(null);


		/////////////////////////////////
		// PROJECT MANAGEMENT
		/////////////////////////////////

		async function loadProject() {

			if (!route.params.slug) return;

			await visualizerStore.loadProject(route.params.slug);

			if (!visualizerStore.project) return;

			// Initialize broadcast after project is loaded
			initBroadcast();
			updateHeaderLogo(visualizerStore.project.title);
		}

		onMounted(loadProject);
		onUnmounted(() => {
			cleanup();
			visualizerStore.cleanup();
			u('.header-logo-title').remove();
		});


		/////////////////////////////////
		// BROADCAST HANDLING
		/////////////////////////////////

		let pollingInterval = 0;

		function initBroadcast() {

			if (!visualizerStore.project) return;

			subscribePrivateChannel('project.' + visualizerStore.project.slug, onChannelMessage);
			initPollingFallback();
		}

		function onChannelMessage(event, data) {

			if (event === 'ProjectSceneUpdated') {
				visualizerStore.updateProject();
			}
		}

		function initPollingFallback() {

			// Setup polling as fallback when websocket is not available
			watch(socketConnected, (connected) => {
				clearInterval(pollingInterval);

				if (!connected && visualizerStore.project) {
					console.log('No socket connection, starting polling fallback');
					pollingInterval = setInterval(() => {
						visualizerStore.updateProject();
					}, 3000);
				}
			});
		}

		function cleanup() {

			clearInterval(pollingInterval);
			pollingInterval = 0;
		}


		/////////////////////////////////
		// VIEW CONTROL ACTIONS
		/////////////////////////////////

		const currentVisualizer = computed(() => visualizerStore.is2dView ? visualizer2d.value : visualizer3d.value);

		function resetView() {
			currentVisualizer.value.focus();
		}

		function showSimulation(jobId, isUmp = false) {
			currentVisualizer.value.showSimulation(jobId, isUmp);
		}


		/////////////////////////////////
		// UTILITY FUNCTIONS
		/////////////////////////////////

		function updateHeaderLogo(title) {

			// Update DOM directly for header logo
			const logoElement = document.querySelector('.header-logo');
			if (logoElement) {
				const existingTitle = logoElement.querySelector('.header-logo-title');
				if (existingTitle) {
					existingTitle.textContent = title;
				} else {
					const titleElement = document.createElement('p');
					titleElement.className = 'header-logo-title';
					titleElement.textContent = title;
					logoElement.appendChild(titleElement);
				}
			}
		}


	</script>
