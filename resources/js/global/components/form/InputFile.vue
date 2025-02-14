<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div :class="['form-row', rowId]">

			<input-label :label="$attrs.label" :info="$attrs.info" v-bind="labelAttrs"/>

			<!-- INPUT -->
			<div :id="inputId" class="input-file" v-bind="inputAttrs">
				<!-- IMAGE -->
				<component
					:is="isEmpty?'div':'a'"
					:href="fileUrl"
					target="_blank"
					rel="noopener noreferrer"
					class="input-file-image"
					:style="!isEmpty && fileType=='image' ? 'background-image: url('+fileUrl+')' : null"
				>
					<svg-item v-if="!isEmpty && fileType!='image'" :icon="webContext+'/file-'+fileType"/>
				</component>
				<!-- LABEL -->
				<p class="input-file-label">{{ t(fileName) }}</p>
				<!-- DELETE -->
				<div class="input-file-delete" v-if="!isEmpty" @click="deleteValue">
					<svg-item :icon="webContext+'/input-delete'"/>
				</div>
			</div>

			<!-- BUTTONS -->
			<div class="input-file-buttons">
				<file-uploader :type="type" :folder="folder" @upload-success="onUploadSuccess" :storage="storage"/>
				<btn class="small secondary" label="File-Manager" @click="openFileManager"/>
			</div>

			<popup ref="popupFileManager">
				<file-manager ref="manager" :storage="storage" file-input/>

				<!-- BUTTONS -->
				<template #buttons>
					<btn label="Abbrechen" @click="closeFileManager" class="small"/>
					<btn label="Bestätigen" @click="confirmFileManager" class="small cta"/>
				</template>
			</popup>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { computed, ref, useTemplateRef } from 'vue';
		import { useInput } from '@global/composables/useInput';
		import { useFile } from '@global/composables/useFile';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useConfig } from '@global/composables/useConfig';


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
			type: 			{ type: String, default: 'file' }, 								// file, image, video, audio, media, doc, code
			storage: 		{ type: String, default: window.config.storage_default }, 		// laravel storage: public, s3
		});

		const emit = defineEmits(['update:modelValue']);

		const { value, rowId, inputId, showError, updateInput, labelAttrs } = useInput(props, emit);
		const { setStorageUrl } = useConfig();


		/////////////////////////////////
		// MULTILANG
		/////////////////////////////////

		const { t } = useLanguage();


		/////////////////////////////////
		// ATTRIBUTES
		/////////////////////////////////

		const inputAttrs = computed(() => ({

			class: ['input-file', {'error':showError.value}, {'empty':isEmpty.value}],
		}));


		const fileName = computed(() => value.value?.split('?').shift().split('/').pop() ?? t(props.placeholder));
		const fileUrl = computed(() => value.value);
		const isEmpty = computed(() => !value.value);


		/////////////////////////////////
		// FILE UPLOADER
		/////////////////////////////////

		function onUploadSuccess(data,response) {

			var newValue = null;

			if(data.s3Multipart) {

				newValue = response.uploadURL;
			}
			else {

				// set absolute path
				newValue = (props.folder ?? '') + '/' + data.name;
				newValue = setStorageUrl(props.storage, newValue);

				// remove duplicated slashes but keep protocol
				newValue = newValue.replaceAll(/([^:]\/)\/+/g,'$1');
			}

			value.value = newValue;

			// update model
			updateInput();
			updateFileType();
		}


		/////////////////////////////////
		// FILE TYPES
		/////////////////////////////////

		const { getFileType } = useFile();

		var fileType = ref('file');

		function updateFileType() {

			fileType.value = getFileType(fileUrl.value);
		}

		updateFileType();


		/////////////////////////////////
		// DELETE
		/////////////////////////////////

		function deleteValue() {

			value.value = undefined;
			updateInput();
		}


		/////////////////////////////////
		// FILE MANAGER
		/////////////////////////////////

		const popupFileManager = useTemplateRef('popupFileManager');
		const manager = useTemplateRef('manager');

		function openFileManager() {

			popupFileManager.value.open();
		}

		function closeFileManager() {

			popupFileManager.value.close();
		}

		function confirmFileManager() {

			value.value = setStorageUrl(props.storage, manager.value.selectedFile);

			closeFileManager();

			// update model
			updateInput();
			updateFileType();
		}


	</script>


