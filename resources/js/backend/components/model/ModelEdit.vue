<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<!-- NAVI -->
		<div class="content model-edit-navi">
			<input-select class="model-edit-navi-lang" id="model-lang" :label="t('label.language')" v-model="modelLang" :options="itemsLang" :placeholder="null"/>
		</div>

		<h3 :style="{'visibility':isLoading?'hidden':'visible'}" class="content model-edit-name">
			{{ t( form.id?'model.edit':'model.new', {name:activeLang == 'en'?t(name).toLowerCase():t(name)} ) }}
		</h3>
		<form-errors class="content" :errors="errors"/>

		<!-- COMPONENT -->
		<div class="model-edit" v-if="!isLoading">
			<model-basic :errors="errors" v-bind="$props"/>
			<model-social v-if="page" :errors="errors" v-bind="$props" :folder="uploadFolder"/>
			<slot :folder="uploadFolder"></slot>
		</div>

		<!-- LOADING -->
		<div class="content model-accordion model-edit-loading" v-if="isLoading">
			<svg-item inline="backend/loading-page"/>
		</div>

		<!-- BUTTONS -->
		<div v-show="!isLoading" class="content form-row-buttons">
			<btn label="Abbrechen" class="secondary btn-model-cancel" @click="goBack"/>
			<btn ref="saveBtn" label="Zwischenspeichern" class="secondary btn-model-save" blocking @click="save"/>
			<btn ref="confirmBtn" label="Speichern" class="btn-model-confirm" blocking @click="confirm"/>
		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, provide, inject, watch } from 'vue';
		import { useRoute, onBeforeRouteLeave } from 'vue-router';
		import { useRouter } from 'vue-router';
		import { useApi } from '@global/composables/useApi';
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			name:			{ type: String, default: null }, 			// label for the model
			modelRoute:		{ type: String, required: true }, 			// name of the model route. Example: 'page', 'item', 'user'
			page:			{ type: Boolean, default: false },			// model is page model
			published:		{ type: Boolean, default: true },			// model requires published attributes
			slug:			{ type: Boolean, default: true },			// model requires a slug
		});

		const route = useRoute();
		const { apiGet, apiPost } = useApi();
		const { t, defaultLang, activeLang, langs } = useLanguage();


		/////////////////////////////////
		// MODEL
		/////////////////////////////////

		const form = inject('form', ref({}));
		const errors = inject('errors', ref({}));

		function addInternalFormData() {

			form.value.parent_id = route.params.parent;
			form.value.parent_type = route.params.parentType;
			if(route.query.order) {	form.value.order = route.query.order; }
		}


		/////////////////////////////////
		// UPLOADS
		/////////////////////////////////

		const uploadFolder = ref('/');

		function setUploadFolder() {

			if(form.value?.created_at) {
				var parts = form.value.created_at.split('.');
				var month = parts[2]+'-'+parts[1];
			}
			else {
				var now = new Date();
				var m = now.getMonth()+1;
				var month = now.getFullYear() + '-' + ( m<10?'0'+m:m );
			}

			uploadFolder.value = props.modelRoute+'s/'+month+'/_upload';
		}


		/////////////////////////////////
		// LOAD
		/////////////////////////////////

		const modelId = ref(route.params.id);
		const isLoading = ref(false);

		async function load() {

			// init existing model
			if(modelId.value) {

				isLoading.value = true;
				await apiGet('api.backend.'+props.modelRoute,{id:modelId.value}, data => {

					form.value = data;
					savedForm = Object.assign({},data); // clone data object
					isLoading.value = false;

					setUploadFolder();
				})
				.catch(e=>{
					form.value = ref({});
					savedForm = {};
				})
				.finally(()=>{ isLoading.value = false; });
			}
			// init new model
			else {
				setUploadFolder();
			}

			addInternalFormData();
		}

		load();


		/////////////////////////////////
		// BUTTONS
		/////////////////////////////////

		const router = useRouter();
		const saveBtn = useTemplateRef('saveBtn');
		const confirmBtn = useTemplateRef('confirmBtn');

		function goBack() {

			unsavedChanges.value = false;

			// has parent context
			if(route.params.parent && route.params.parentType) {
				router.push({name:'backend.'+route.params.parentType+'.edit', params:{id:route.params.parent}});
			}
			// back to list
			else {
				router.push({name:'backend.'+props.modelRoute});
			}
		}


		/////////////////////////////////
		// SAVE
		/////////////////////////////////

		function save() {

			form.value.preview = true;
			errors.value = {};

			apiPost(`api.backend.${props.modelRoute}.save`, form.value, data => {

				// update current route with new id
				router.replace({params:{id:data.id}});

				// update form with new data (keep reactive)
				Object.keys(data).forEach(k => {

					if(typeof data[k] === 'object') {
						Object.keys(data[k]).forEach(s => form.value[k][s] = data[key][s]);
					}
					else {
						form.value[k] = data[k];
					}
				});

				// reset unsaved changes
				setTimeout(()=> {
					unsavedChanges.value = false;
					savedForm = Object.assign({},form.value); // clone data object
				}, 100);

				setUploadFolder();
				addInternalFormData();
			})
			.catch(onError)
			.finally(() => saveBtn.value.setLoading(false));
		}


		function confirm() {

			form.value.preview = false;

			apiPost(`api.backend.${props.modelRoute}.save`, form.value, goBack)
			.catch(onError)
			.finally(() => confirmBtn.value.setLoading(false));
		}


		/////////////////////////////////
		// ERROR
		/////////////////////////////////

		function onError(error) {

			errors.value = error?.response?.data?.errors;
			if(errors.value) { window.scrollTo({top: 0, behavior: 'smooth'}); }
			else { console.log("useForm: error", error?.response); }
		}


		/////////////////////////////////
		// MULTILANG
		/////////////////////////////////

		const modelLang = ref(defaultLang);
		provide('modelLang', modelLang);

		const itemsLang = [];
		if(langs.includes('de')) { itemsLang.push({'Deutsch': 'de'}); }
		if(langs.includes('en')) { itemsLang.push({'Englisch': 'en'}); }


		/////////////////////////////////
		// UNSAVED CHANGES
		/////////////////////////////////

		const unsavedChanges = ref(false);
		var savedForm = {};

		watch(form, () => {

			unsavedChanges.value = false;

			for(const key in form.value) {

				const val = form.value[key];
				const savedVal = savedForm[key];

				// compare only array lenght instead of nested data
				if(val !== null && typeof val == "object") {
					if(val.length != savedVal?.length) {
						unsavedChanges.value = true;
						break;
					}
				}
				// compare values
				else if(val != savedVal && val!='' && savedVal!=null) {
					unsavedChanges.value = true;
					break;
				}
			}
		}, {deep:true});


		onBeforeRouteLeave((to, from, next) => {

			if(unsavedChanges.value && to.name.includes('edit')) {
				next( window.confirm(t('unsaved-changes')) );
			}
			else { next(); }
		});


	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
				"label.language": "Sprache für Daten",
				"unsaved-changes": "Es gibt ungespeicherte Änderungen. Die Seite wirklich verlassen?",
				"model.new": "{name} erstellen",
				"model.edit": "{name} bearbeiten",
			},
			"en": {
				"label.language": "Language for data",
				"unsaved-changes": "There are unsaved changes. Really leave the page?",
				"model.new": "Create {name}",
				"model.edit": "Edit {name}",
			}
		}
	</i18n>

