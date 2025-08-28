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
				Erstellt: {{ result.created_at }}<br>
				Fläche: {{ areaWidth }}x{{ areaHeight }} m<br>
				Berechnung: {{ time_taken }}<br>
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
					<span v-if="isVisualizer" class="textlink" @click="showSimulation(props.result.jobID, true)">Anzeigen</span>
					<br>
					<a v-if="result.links?.length" :href="result.links[0].href" target="_blank" rel="noreferrer noopener">Download data</a><br>
				</template>

				<!-- DELETE -->
				<span class="textlink" @click="deleteResult(result.id)">Löschen</span>
			</p>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { computed, inject } from 'vue';
		import { useRoute } from 'vue-router';
		import { useDate } from '@global/composables/useDate';
		import { getLength } from 'ol/sphere';
		import { LineString } from 'ol/geom';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			project: {type: Object, required: true},
			result: {type: Object, required: true},
		});

		const emit = defineEmits(['delete-result']);

		const { secondsToTime } = useDate();
		const time_taken = secondsToTime(props.result.computation_seconds || 0, true, true);

		const route = useRoute();


		/////////////////////////////////
		// RESULT INFOS
		/////////////////////////////////

		const areaWidth = parseInt(getLength(
			new LineString([
				[props.project.start_longitude, props.project.start_latitude],
				[props.project.end_longitude, props.project.start_latitude],
			]),
			{ projection: 'EPSG:4326' }
		));

		const areaHeight = parseInt(getLength(
			new LineString([
				[props.project.start_longitude, props.project.start_latitude],
				[props.project.start_longitude, props.project.end_latitude],
			]),
			{ projection: 'EPSG:4326' }
		));


		/////////////////////////////////
		// VISUALIZER
		/////////////////////////////////

		const isVisualizer = computed(() => route.name == 'visualizer');
		const showSimulation = inject('showSimulation');


		/////////////////////////////////
		// DELETE
		/////////////////////////////////

		function deleteResult(resultId) {
			emit('delete-result', resultId);
		}


		/////////////////////////////////
		// UTILS
		/////////////////////////////////

		function getTimeTaken(start, end) {
			if (!start || !end) return 'N/A';
			const duration = new Date(end) - new Date(start);
			const minutes = Math.floor((duration / (1000 * 60)) % 60);
			const hours = Math.floor((duration / (1000 * 60 * 60)) % 24);
			const pad = n => n.toString().padStart(2, '0');
			return `${pad(hours)}:${pad(minutes)} Std`;
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

			},
			"en": {

			}
		}
	</i18n>

