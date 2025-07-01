<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="cols simulation-result">

			<p>{{ result.name }}</p>

			<!-- LEFT -->
			<p class="col-50 small">
				Erstellt: {{ result.created_at ?? formatDate(result.created) }}<br>
				Status: <span :class="'status-'+result.status">{{ result.status }}</span>
			</p>

			<!-- RIGHT -->
			<p class="col-50 small">

				<!-- PAPERSCOPE -->
				<template v-if="result.type == 'simulation' && result.status=='successful'">
					<span class="textlink" v-if="isVisualizer" @click="showSimulation(result.id)">Anzeigen</span>
					<a v-else :href="baseUrl+'simulation/image/'+result.id" target="_blank">Preview</a>
					<br>
					<a :href="baseUrl+'simulation/project/'+result.id" target="_blank">Download QGIS</a><br>
				</template>
				<!-- UMP -->
				<template v-else-if="result.status=='successful'">
					<span v-if="isVisualizer" class="textlink" @click="showUmpSimulation(props.result.jobID)">Anzeigen</span>
					<br>
					<a v-if="result.links?.length" :href="result.links[0].href" target="_blank" rel="noreferrer noopener">Download data</a><br>
				</template>
			</p>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, computed, inject, useTemplateRef } from 'vue';
		import { useRoute } from 'vue-router';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			project: {type: Object, required: true},
			result: {type: Object, required: true},
		});

		const route = useRoute();
		const isVisualizer = computed(() => route.name == 'visualizer');


		/////////////////////////////////
		// RESULT
		/////////////////////////////////

		function formatDate(dateString) {

			const date = new Date(dateString);
			return date.toLocaleDateString('de-DE', {
				year: 'numeric',
				month: '2-digit',
				day: '2-digit',
				hour: '2-digit',
				minute: '2-digit'
			});
		}


		/////////////////////////////////
		// VISUALIZER
		/////////////////////////////////

		const showSimulation = inject('showSimulation');
		const showUmpSimulation = inject('showUmpSimulation');


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

