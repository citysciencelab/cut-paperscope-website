<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div :class="['form-row', rowId]">

			<input-label :label="$attrs.label" :info="$attrs.info ?? 'max. 8MB'" v-bind="labelAttrs"/>

			<!-- INPUT -->
			<div :id="inputId" class="input-userupload" v-bind="inputAttrs">

				<!-- IMAGE -->
				<div
					class="input-file-image"
					:style="!isEmpty && fileType=='image' ? 'background-image: url('+(previewFile ?? fileUrl)+')' : null"
				>
					<svg-item v-if="!isEmpty && fileType!='image'" :icon="webContext+'/file-'+fileType"/>
					<svg-item v-if="isLoading" :inline="webContext+'/loading-api'" :class="['input-userupload-loading',{empty:isEmpty}]"/>
				</div>

				<!-- LABEL -->
				<p :class="['input-file-label',{error:errorMessage}]">{{ t(errorMessage ?? fileName) }}</p>

				<!-- DELETE -->
				<div class="input-file-delete" v-if="!isEmpty" @click="deleteValue">
					<svg-item :icon="webContext+'/input-delete'"/>
				</div>
			</div>

			<!-- BUTTONS -->
			<div class="input-file-buttons" v-show="isEmpty && !isLoading">
				<span ref="uploadBtn"></span>
			</div>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { computed, ref, useTemplateRef, onMounted, getCurrentInstance } from 'vue';
		import { useInput } from '@global/composables/useInput';
		import { useApi } from '@global/composables/useApi';
		import { useFile } from '@global/composables/useFile';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useUser } from '@global/composables/useUser';

		import Uppy from '@uppy/core';
		import FileInput from '@uppy/file-input';
		import XHRUpload from '@uppy/xhr-upload';
		import German from '@uppy/locales/lib/de_DE';
		import Cookies from 'js-cookie';

		import '@uppy/core/dist/style.css';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			modelValue:		{ type: [String, Number, Object] },				// bind variable to v-model
			error: 			{ default: null },								// form data to show error

			id:				{ type: String, default: null }, 				// unique form id for this input element
			placeholder: 	{ type: String, default: "Noch keine Datei" },	// show a placeholder text input element
			multilang: 		{ type: Boolean, default: false },				// same input for all languages

			// html
			readonly: 		{ type: Boolean },								// html readonly attribute
			required: 		{ type: Boolean },								// html required attribute (show asterisk)

			// file uploader
			folder: 		{ type: String, default: undefined }, 							// sub folder in storage
			type: 			{ type: String, default: 'image' }, 							// file, image, video, audio, media, doc, code
			storage: 		{ type: String, default: window.config.storage_default }, 		// laravel storage: public, s3
		});

		const emit = defineEmits(['update:modelValue']);

		const { value, rowId, inputId, showError, labelAttrs, updateInput } = useInput(props, emit);
		const { user } = useUser();
		const { createRoute } = useApi();


		/////////////////////////////////
		// MULTILANG
		/////////////////////////////////

		const { t, activeLang } = useLanguage();


		/////////////////////////////////
		// ATTRIBUTES
		/////////////////////////////////

		const inputAttrs = computed(() => ({

			class: ['input-file', {'error':showError.value}, {'empty':isEmpty.value}],
		}));

		const fileName = computed(() => value.value?.split('/').pop() ?? props.placeholder);
		const fileUrl = computed(() => value.value);
		const isEmpty = computed(() => !value.value);


		/////////////////////////////////
		// FILE TYPES
		/////////////////////////////////

		const { getFileType } = useFile();
		var fileType = ref('file');

		function updateFileType() {

			fileType.value = getFileType(fileUrl.value);
		}

		updateFileType();


		function getAllowedFileTypes() {

			var images 		= ['image/jpeg', 'image/png', 'image/gif', 'image/webp','image/svg+xml'];
			var docs 		= ['application/pdf'];
			var archives 	= ['application/pdf'];

			switch(props.type) {
				case 'image':  	return images;
				case 'video': 	return ['video/mp4'];
				case 'audio': 	return ['audio/mpeg'];
				case 'media': 	return [...images, 'video/mp4', 'audio/mpeg'];
				case 'doc': 	return [...docs, ...archives];
				case '3d': 		return ['.gltf', '.glb', 'model/gltf+json', 'model/gltf-binary'];
				default: 		return [...images, 'video/mp4', 'audio/mpeg', ...docs, ...archives];
			}
		}


		/////////////////////////////////
		// UPPY
		/////////////////////////////////

		const uppyId = 'upload-' + getCurrentInstance().uid;
		const uploadBtn = useTemplateRef('uploadBtn');
		var uppy = null;

		function initUppy() {

			let config = {
				id: uppyId,
				autoProceed: true,
				allowMultipleUploads: false,
				formData: true,
				locale: activeLang.value == 'en' ? null : German,
				restrictions: {
					allowedFileTypes: getAllowedFileTypes(),
					maxNumberOfFiles: 1,
					maxFileSize: 1024 * 1000 * (props.type == '3d' ? 100 : 8), // max. 8MB
				},
				meta: {
					folder: props.folder ?? '/',
					'stream_offset': '78fb153f02e9d3a43b4e5a81273ed716=',
					'id': user.value.id,
					'file_type': props.type,
				},
			};

			// create uppy instance
			if(uppy) { uppy.cancelAll(); uppy.destroy(); }
			uppy = new Uppy(config);

			// Simple Upload Button
			uppy.use(FileInput, {
				target: uploadBtn.value,
				pretty: true,
				inputName: 'files[]',
				debug: true,
				locale: {
						strings: {
						chooseFiles: t('Datei hochladen'),
					},
				},
			});
			u('.uppy-FileInput-btn').addClass('btn small secondary');

			// uploader
			uppy.use(XHRUpload, {
				endpoint: createRoute('user.upload'),
				headers: {
					'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN'),
					'Credentials': true,
					'X-Requested-With': 'XMLHttpRequest',
					'X-Stream-Offset': '78fb153f02e9d3a43d4e5a81273eb716=',
				},
			});

			// Uppy Events
			uppy.on('upload', onUpload);
			uppy.on('upload-success', onUploadSuccess);
			uppy.on('upload-error', onUploadError);
			uppy.on('restriction-failed',onRestrictionError);
		}

		onMounted(initUppy);


		/////////////////////////////////
		// UPPY EVENTS
		/////////////////////////////////

		const errorMessage = ref(null);
		const isLoading = ref(false);
		const previewFile = ref(null);

		function onUpload() {

			errorMessage.value = null;
			isLoading.value = true;
			previewFile.value = null;
		}

		function onUploadSuccess(data, response) {

			isLoading.value = false;

			value.value = response.body.file;
			updateInput();
			updateFileType();

			// creat preview from local file
			if(props.type=='image') {
				var fr = new FileReader();
				fr.addEventListener("load", ()=>{ previewFile.value = fr.result; }, false);
				fr.readAsDataURL(data.data);
			}

			// reset all files from uppy
			uppy.cancelAll();
		}


		function onUploadError(file, error, response) {

			switch(response?.status) {
				case 403: errorMessage.value = t('Keine Berechtigung'); break;
				case 422: errorMessage.value = t('Datei fehlerhaft'); break;
				default: errorMessage.value = t('Unbekannter Upload-Fehler');
			}

			isLoading.value = false;

			// remove all files from uppy
			uppy.cancelAll();
		}


		function onRestrictionError(file, error) {

			errorMessage.value = t('Datei größer als 8MB.');
			isLoading.value = false;

			// remove all files from uppy
			uppy.cancelAll();
		}


		/////////////////////////////////
		// DELETE
		/////////////////////////////////

		function deleteValue() {

			value.value = undefined;
			previewFile.value = null;

			updateInput();
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
				"Keine Berechtigung": "Not authorized",
				"Datei fehlerhaft": "File corrupted",
				"Unbekannter Upload-Fehler": "Unknown upload error",
				"Datei größer als 8MB.": "File larger than 8MB",
			}
		}
	</i18n>

