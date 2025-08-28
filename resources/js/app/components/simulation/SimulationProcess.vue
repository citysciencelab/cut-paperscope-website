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
				<input-select
					v-if="input.schema.type=='string' && input.schema.enum"
					:label="input.title"
					:id="key"
					v-model="form"
					:required="input.required"
					:options="input.schema.enum.map(e => ({ [e]: e }))"
				/>
				<input-text
					v-else-if="input.schema.type=='string'"
					:label="input.title"
					:id="key"
					v-model="form"
					:required="input.required"
				/>
				<input-text
					v-else-if="input.schema.type=='array'"
					:label="input.title + ' (comma-separated values)'"
					:id="key"
					v-model="form"
					:required="input.required"
					placeholder="e.g., 100, 500, 600"
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
			<simulation-result v-for="result in results" :key="result.id" :project="project" :result="result" @delete-result="confirmDelete"/>
		</popup>

		<!-- DELETE -->
		<popup-modal ref="deleteModal"/>

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
		const { apiGet, apiGetResponse, apiPost, apiDelete } = useApi();
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
			Object.keys(data.example.inputs).forEach(key => {
				const value = data.example.inputs[key];
				// Convert arrays to comma-separated strings for display in text input
				if (data.inputs[key] && data.inputs[key].schema.type === 'array' && Array.isArray(value)) {
					form.value[key] = value.join(', ');
				} else {
					form.value[key] = value;
				}
			});
			form.value.project_id = props.project.id;
		}


		function submitForm() {

			// Process array inputs: convert comma-separated strings to arrays
			const processedForm = { ...form.value };
			Object.keys(inputs.value).forEach(key => {
				const input = inputs.value[key];
				if (input.schema.type != 'array' || typeof processedForm[key] !== 'string') {
					return;
				}

				// Parse comma-separated values into array
				processedForm[key] = processedForm[key]
					.split(',')
					.map(item => item.trim())
					.filter(item => item !== '')
					.map(item => {
						// Try to convert to number if it's numeric
						const num = Number(item);
						return !isNaN(num) && item !== '' ? num : item;
					});
			});

			// save local simulation
			if(props.process.paperscope) {

				const params = {
					'model': props.process.id,
				}

				const data = {
					'job_name': processedForm.job_name || 'Simulation',
					'inputs': { ...processedForm, ...{'project_id': props.project.id} },
				};

				apiPost('api.ogc.process.execute',data, () =>{
					startPopup.value.close();
				}, params);
				return;
			}

			executeProcess(props.project, processedForm);
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

				apiGet('project.simulation',{slug:props.project.slug, model:props.process.id}, data => {
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


		/////////////////////////////////
		// DELETE SIMULATION
		/////////////////////////////////

		const deleteModal = useTemplateRef('deleteModal');

		function confirmDelete(resultId) {

			deleteModal.value.open({
				title: t("Szenario löschen"),
				copy: t("szenario.delete.copy"),
				alert: true,
				confirmLabel: t("Szenario löschen"),
				callback: () => deleteSimulation(resultId)
			});
		}

		function deleteSimulation(resultId) {

			apiDelete('api.ogc.job.delete', {}, { id: resultId }).then(() => {
				results.value = results.value.filter(result => result.id !== resultId);
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

			},
			"en": {

			}
		}
	</i18n>

