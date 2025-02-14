<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="col-100 dashboard-item dashboard-analytics">

			<h4>{{ t('Website-Besucher') }}</h4>
			<div class="dashboard-analytics-range">
				<button v-for="range in ranges" :class="['small',{'active':range==analyticsRange}]" @click="updateRange(range)">{{ t(range) }}</button>
			</div>

			<svg-item class="dashboard-loading" v-if="isLoading" inline="backend/loading-page"/>
			<p class="dashboard-analytics-empty" v-if="!analyticsData && !isLoading">
				{{ t("empty") }}
			</p>

			<div class="dashboard-analytics-container">
				<canvas ref="chartCanvas"></canvas>
			</div>
		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, onMounted } from 'vue';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useApi } from '@global/composables/useApi';

		import Chart from 'chart.js/auto';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = useLanguage();


		/////////////////////////////////
		// DATA
		/////////////////////////////////

		const ranges = ['week', 'month', 'year'];
		const analyticsData = ref(null);
		const analyticsRange = ref('month');
		const isLoading = ref(false);
		const { apiGet } = useApi();

		function load() {

			analyticsData.value = null;
			isLoading.value = true;

			apiGet('backend.dashboard.analytics',{range: analyticsRange.value}, data => {
				analyticsData.value = data;
				isLoading.value = false;
				if(data) { initChart(); }
			},true);
		}

		onMounted(load);

		function updateRange(range) {

			analyticsRange.value = range;
			if(!analyticsData.value) { return; }

			chart.destroy();
			load();
		}


		/////////////////////////////////
		// CHART
		/////////////////////////////////

		var chart = null;
		const chartCanvas = useTemplateRef('chartCanvas');

		function initChart() {

			// format labels
			const labels = Object.keys(analyticsData.value).map(key => {
				const date = key.split('-');
				return date[2] + '.' + date[1] + '.';
			});

			chart = new Chart(chartCanvas.value.getContext('2d'), {
				type: 'line',
				data: {
					labels,
					datasets: [{
						label: 'Besucher',
						data: Object.values(analyticsData.value),
						borderWidth: 1
					}]
				},
				options: {
					borderColor: '#209EF9',
					pointRadius: analyticsRange.value == 'year' ? 1 : 2,
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { display: false }
					},
					scales: {
						y: {
							grid: { color: 'rgb(232, 237, 239' },
							beginAtZero: true,
							ticks: {
								stepSize: 1,
							}
						},
						x: {
							grid: { color: 'transparent' },
							ticks: {
								font: {
									size: 9,
								},
							},
						}
					}
				}
			});
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
				"week": "Woche",
				"month": "Monat",
				"year": "Jahr",
				"empty": "Daten konnten nicht geladen werden.",
			},
			"en": {
				"Website-Besucher": "Website visitors",
				"empty": "Data could not be loaded.",
			}
		}
	</i18n>
