<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<label v-if="label || info" :class="{'error':error}" :for="id">

			<!-- LANGUAGE -->
			<span class="form-label-lang" v-if="multilang">
				<svg-item :sprite="iconLang"/>
			</span>

			<!-- LABEL -->
			{{ label ? t(label) + (required?' *':'') : '' }}

			<!-- INFO -->
			<span class="form-label-info" v-if="info">
				{{ (label?'(':'') + t(info) + (label?')':'') }}
			</span>

		</label>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { computed, inject } from 'vue';
		import { useConfig } from '@global/composables/useConfig';
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			label: 			{ type: String },								// text for <label> element
			info: 			{ type: String },								// show a small info text after label
			id:				{ type: String, default: null }, 				// unique form id for this input element
			multilang: 		{ type: [Boolean, Object], default: false },	// same input for all languages
			error: 			{ default: null },								// form data to show error

			// html
			required: 		{ type: Boolean },								// html required attribute (show asterisk)

		});


		/////////////////////////////////
		// MULTILANG
		/////////////////////////////////

		const { webContext } = useConfig();
		const { t, defaultLang } = useLanguage();

		const modelLang = inject('modelLang', defaultLang);
		const iconLang = computed(() => webContext+'/language-'+modelLang.value);


	</script>


