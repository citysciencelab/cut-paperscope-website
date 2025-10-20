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
					<br v-if="result.model=='umep:heat_island'">
					<a :href="baseUrl+'simulation/project/'+result.id" v-if="result.model=='umep:heat_island'" target="_blank">Download QGIS</a><br>
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

		import { computed } from 'vue';
		import { storeToRefs } from 'pinia';
		import { useRoute } from 'vue-router';

		import { useDate } from '@global/composables/useDate';
		import { useVisualizerStore } from '@app/stores/VisualizerStore';

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

		const { simulation } = storeToRefs(useVisualizerStore());
		const isVisualizer = computed(() => route.name == 'visualizer');

		function showSimulation(resultId, isUmp=false) {

			simulation.value = { id: resultId, isUmp: isUmp };
		}


		/////////////////////////////////
		// DELETE
		/////////////////////////////////

		function deleteResult(resultId) {
			emit('delete-result', resultId);
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

