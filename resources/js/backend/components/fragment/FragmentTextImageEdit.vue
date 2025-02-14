<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<!-- TEXT -->
		<input-text label="Titel" id="title" info="H2 Überschrift" v-model="value" :error="error" :multilang="multilang"/>
		<input-richtext label="Text" id="copy" v-model="value" :error="error" :folder="folder" :multilang="multilang"/>

		<!-- IMAGE -->
		<input-file class="col-50" label="Bild-Datei" info="HQ in 1000px Breite" id="image" v-model="value" :error="error" type="image" :folder="folder" :multilang="multilang"/>
		<input-select class="col-50" id="image-position" label="Position Bild" v-model="value" :options="itemsPosition"/>
		<input-text class="col-50" label="Bild-Unterschrift" id="image-subline" v-model="value" :error="error" :multilang="multilang"/>
		<input-text class="col-50" label="Alt-Text für Bild" info="Beschreibung Bildinhalt" id="image-alt" v-model="value" :error="error" :multilang="multilang"/>
		<input-text class="col-100" label="URL für Link" id="image-url" info="URL inklusive https://" v-model="value" :error="error" :multilang="multilang"/>

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

		const itemsPosition = [
			{'Links':'left'},
			{'Rechts':'right'},
		];


		/////////////////////////////////
		// VALUE
		/////////////////////////////////

		const value = ref({});

		function setValue() {

			const prop = propId + (props.multilang ? '_' + modelLang.value : '');
			if(!form.value[prop]) { form.value[prop] = {}; }

			// form defaults
			if(!form.value[prop].image_position) { form.value[prop].image_position = 'right'; }

			value.value = form.value[prop];
		}

		watch(modelLang, () => setValue(), { immediate: true });


	</script>


