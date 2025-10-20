<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<visualizer-2d v-if="project && is2dView"/>
		<visualizer-3d v-else="project"/>
		<visualizer-navi/>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, onMounted, onUnmounted, watch } from 'vue';
		import { storeToRefs } from 'pinia';
		import { useRoute } from 'vue-router';
		
		import { useApi } from '@global/composables/useApi';
		import { useBroadcast } from '@global/composables/useBroadcast';
		import { useVisualizerStore } from '@app/stores/VisualizerStore';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const route = useRoute();
		const { apiGetSlug } = useApi();
		const { socketConnected, subscribePrivateChannel } = useBroadcast();


		/////////////////////////////////
		// PROJECT
		/////////////////////////////////

		const visualizerStore = useVisualizerStore();
		const { project, is2dView, is2dActive } = storeToRefs(visualizerStore);

		const isLoading = ref(false);


		async function loadProject() {

			if(!route.params.slug || isLoading.value) { return; }
			isLoading.value = true;
			
			await apiGetSlug('project', data => {
				
				project.value = data;
				is2dActive.value = project.value.visualizer_settings?.is_2d_view;
				
				initBroadcast();
			})
			.finally(() => isLoading.value = false);
		}


		async function updateScene() {

			if(!project.value || isLoading.value) { return; }
			isLoading.value = true;

			await apiGetSlug('project.scene', data => {
				project.value = { ...project.value, ...data };
			})
			.finally(() => isLoading.value = false);
		}


		loadProject();

		onUnmounted(() => {
			
			project.value = null;
			clearPolling();
		});


		/////////////////////////////////
		// BROADCAST
		/////////////////////////////////

		function initBroadcast() {

			subscribePrivateChannel('project.' + project.value.slug, onChannelMessage);
		}


		function onChannelMessage(event, data) {

			if(event == 'ProjectSceneUpdated') { updateScene(); }
		}


		/////////////////////////////////
		// POLLING
		/////////////////////////////////
		
		// interval polling as a fallback if websocket is not available
		var pollingInterval = 0;
		

		watch(socketConnected, (connected) => {
			
			clearPolling();

			if(!connected && project.value) {
				pollingInterval = setInterval(updateScene, 3000);
			}
		});


		function clearPolling() {

			clearInterval(pollingInterval);
			pollingInterval = 0;
		}





	</script>
