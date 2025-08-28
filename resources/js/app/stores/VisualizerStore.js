/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	import { ref, computed } from 'vue'
	import { defineStore } from 'pinia'
	import { useApi } from '@global/composables/useApi'



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	STORE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	export const useVisualizerStore = defineStore('visualizer', () => {

		const { apiGetSlug, apiPost } = useApi();


		/////////////////////////////////
		// DATA
		/////////////////////////////////

		const project = ref(null);
		const simulation = ref({
			jobId: null,
			isUmp: false,
			isLoaded: false
		});


		/////////////////////////////////
		// ACTIONS
		/////////////////////////////////

		async function loadProject(slug) {
			if (!slug) return;

			await apiGetSlug('project', (data) => {
				project.value = data;
			}).catch(err => console.error('Failed to load project:', err));
		}

		async function updateProject() {
			if (!project.value) return;

			await apiGetSlug('project', (data) => {
				project.value = data;
			}).catch(err => console.error('Failed to update project:', err));
		}


		////////////////////////////////////
		// SETTERS
		////////////////////////////////////

		function setProject(data) {
			project.value = data;
		}

		function setViewMode(mode) {
			if (project.value) {
				// Create visualizer_settings if it doesn't exist
				project.value.visualizer_settings ||= {};
				project.value.visualizer_settings.is_2d_view = mode;

				// Save the changes to the server
				apiPost("api.project.save", project.value).catch(err => {
					console.error('Failed to save view mode:', err);
				});
			}
		}

		function clearProject() {
			project.value = null;
			simulation.value = {
				jobId: null,
				isUmp: false,
				isLoaded: false
			};
		}

		function setSimulation(jobId, isUmp = false) {
			simulation.value = {
				jobId,
				isUmp,
				isLoaded: !!jobId
			};
		}


		/////////////////////////////////
		// GETTERS
		/////////////////////////////////

		const hasProject = computed(() => project.value !== null);
		const projectSlug = computed(() => project.value?.slug || null);
		const projectTitle = computed(() => project.value?.title || '');
		const mapping = computed(() => project.value?.mapping || null);
		const scene = computed(() => project.value?.scene || null);
		const is2dView = computed(() => project.value?.visualizer_settings?.is_2d_view ?? true);
		const currentSimulation = computed(() => simulation.value);
		const hasSimulation = computed(() => simulation.value.isLoaded);


		/////////////////////////////////
		// UTILITY FUNCTIONS
		/////////////////////////////////

		function cleanup() {
			// Reset all states
			project.value = null;
			simulation.value = {
				jobId: null,
				isUmp: false,
				isLoaded: false
			};
		}


		/////////////////////////////////
		// RETURN STORE INTERFACE
		/////////////////////////////////

		return {
			// State
			project,
			simulation,

			// Getters
			hasProject,
			projectSlug,
			projectTitle,
			mapping,
			scene,
			is2dView,
			currentSimulation,
			hasSimulation,

			// Actions
			loadProject,
			updateProject,
			setProject,
			setViewMode,
			clearProject,
			setSimulation,
			cleanup,
		};
	});
