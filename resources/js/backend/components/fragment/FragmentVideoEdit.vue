<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>
		<input-file class="col-50" :label="t('Video-Datei Desktop')" info="Web-MP4 in 1920x1080px" id="video-desktop" v-model="value" :error="error" type="video" :folder="folder" :multilang="multilang"/>
		<input-file class="col-50" :label="t('Vorschaubild Desktop')" info="HQ in 1280x720px" id="video-poster-desktop" v-model="value" :error="error" type="image" :folder="folder" :multilang="multilang"/>
		<input-file class="col-50" :label="t('Video-Datei Mobile')" info="Web-MP4 in 960x540px" id="video-mobile" v-model="value" :error="error" type="video" :folder="folder" :multilang="multilang"/>
		<input-file class="col-50" :label="t('Vorschaubild Mobile')" info="HQ in 960x540px" id="video-poster-mobile" v-model="value" :error="error" type="image" :folder="folder" :multilang="multilang"/>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { inject, ref, watch } from 'vue';
		import { useInput } from '@global/composables/useInput';
		import { useLanguage } from '@global/composables/useLanguage';


		/*
		*	Usage:
		*	<input-text label="Click" id="username" v-model="myVar"/>
		*/


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			modelValue:		{ type: [String, Number, Object] },		// bind variable to v-model
			error: 			{ default: null },						// form data to show error

			id:				{ type: String, default: null }, 		// unique form id for this input element
			multilang: 		{ type: Boolean, default: false },		// same input for all languages

			// file uploader
			folder: 		{ type: String, default: undefined }, 	// sub folder in storage
		});

		const emit = defineEmits(['update:modelValue']);

		const { t } = useLanguage();
		const { propId } = useInput(props, emit);


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		const form = inject('form');
		const modelLang = inject('modelLang');


		/////////////////////////////////
		// VALUE
		/////////////////////////////////

		const value = ref({});

		function setValue() {

			const prop = propId + (props.multilang ? '_' + modelLang.value : '');
			if(!form.value[prop]) { form.value[prop] = {}; }
			value.value = form.value[prop];
		}

		watch(modelLang, () => setValue(), { immediate: true });


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
				"Video-Datei Desktop": "Video file desktop",
				"Vorschaubild Desktop": "Poster image desktop",
				"Video-Datei Mobile": "Video file mobile",
				"Vorschaubild Mobile": "Poster image mobile",
			}
		}
	</i18n>

