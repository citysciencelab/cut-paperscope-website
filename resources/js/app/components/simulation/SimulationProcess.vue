<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<!-- TEASER -->
		<div class="simulation-process" :class="$attrs.class">

			<p>{{ process.title }} - {{ process.version }}</p>
			<p class="small">
				{{ process.description }}
				<a v-if="process.info" :href="process.info" target="_blank" rel="noreferrer noopener">Mehr infos</a>
			</p>

			<!-- BUTTONS -->
			<div class="form-row-buttons">
				<btn class="small secondary" label="Szenarien" @click="openResultsPopup"/>
				<btn class="small" label="Ausführen" @click="openStartPopup"/>
			</div>
		</div>


		<!-- POPUP START -->
		<popup ref="startPopup">

			<!-- FORM -->
			<input-text label="Name des Szenarios" id="job_name" v-model="form" required/>
			<div v-for="(input,key) in inputs">
				<input-text
					v-if="input.schema.type=='string'"
					:label="input.title"
					:id="key"
					v-model="form"
					:required="input.required"
				/>
				<input-text
					v-else-if="input.schema.type=='number'"
					:label="input.title"
					:id="key"
					v-model="form"
					:required="input.required"
					type="number"
					:min="input.schema.minimum"
					:max="input.schema.maximum"
				/>
				<input-radio
					v-else-if="input.schema.type=='select'"
					:label="input.title"
					:id="key"
					v-model="form"
					:required="input.required"
					:options="input.schema.options.map(e => ({ [e.label]: e.value}))"
				/>
			</div>

			<!-- BUTTONS -->
			<div class="form-row-buttons">
				<btn class="small" label="Simulation starten" @click="submitForm" blocking/>
			</div>

		</popup>


		<!-- POPUP RESULTS -->
		<popup ref="resultPopup">
			<loading-spinner v-if="isResultsLoading"/>
			<p v-else-if="!results.length" class="empty">{{ t('Noch keine Szenarien vorhanden.') }}</p>
			<simulation-result v-for="result in results" :project="project" :result="result"/>
		</popup>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef } from 'vue';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useApi } from '@global/composables/useApi';
		import { useUmp } from '@app/composables/useUmp';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			project: {type: Object, required: true},
			process: {type: Object, required: true},
		});

		const { t } = useLanguage();
		const { apiGet, apiGetResponse, apiPost } = useApi();
		const { getProcess, executeProcess, getJobs } = useUmp();


		/////////////////////////////////
		// START POPUP
		/////////////////////////////////

		const form = ref({});
		const inputs = ref([]);
		const startPopup = useTemplateRef('startPopup');

		function openStartPopup() {

			startPopup.value.open();
			form.value = { id: props.process.id };
			inputs.value = [];

			if(props.process.paperscope) {

				apiGetResponse('ogc.process',{model:props.process.id}, r => onProcessLoaded(r.data));
			}
			else {

				getProcess(props.process.id).then(onProcessLoaded).catch(e => console.error(e));
			}
		}


		function onProcessLoaded(data) {

			inputs.value = data.inputs;

			// reshape form
			Object.keys(data.example.inputs).forEach(key => { form.value[key] = data.example.inputs[key]; });
			form.value.project_id = props.project.id;
		}


		function submitForm() {

			startPopup.value.close();

			// save local simulation
			if(props.process.paperscope) {

				const params = {
					'model': props.process.id,
				}

				const data = {
					'job_name': form.value.job_name || 'Simulation',
					'inputs': { ...form.value, ...{'project_id': props.project.id} },
				};

				apiPost('api.ogc.process.execute',data, () =>{}, params);
				return;
			}

			executeProcess(props.project, form.value);
		}


		/////////////////////////////////
		// RESULT POPUP
		/////////////////////////////////

		const results = ref([]);
		const resultPopup = useTemplateRef('resultPopup');
		const isResultsLoading = ref(false);

		function openResultsPopup() {

			isResultsLoading.value = true;
			resultPopup.value.open();
			results.value = [];

			if(props.process.paperscope) {

				apiGet('project.simulation',{slug:props.project.slug},data => {
					results.value = data;
					isResultsLoading.value = false;
				});
				return;
			}

			getJobs(props.project.id, props.process.id).then(r => {

				results.value = r;
				isResultsLoading.value = false;

			}).catch(e => console.error(e));
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

