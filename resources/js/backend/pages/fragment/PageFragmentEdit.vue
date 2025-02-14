<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<model-edit name="Inhalt" model-route="fragment" :slug="false" v-slot="{folder}">

			<model-accordion label="Inhalt">

				<input-select v-if="!form.id" label="Template" id="template" v-model="form" :error="errors" :options="itemsTemplate" @update:model-value="onTemplateChange" required/>
				<input-text v-else label="Template" id="template" v-model="form" :error="errors" readonly required/>

				<!-- TEMPLATES -->
				<component :is="'fragment-'+form.template+'-edit'" id="content" v-model="form" :error="errors" :folder="folder" :multilang="multiLangEnabled"/>

			</model-accordion>

		</model-edit>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, provide } from 'vue';
		import { useRoute } from 'vue-router';
		import { usePage } from '@global/composables/usePage';
		import { useForm } from '@global/composables/useForm';
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = usePage("Backend");
		const { langs, multiLangEnabled } = useLanguage();
		const route = useRoute();


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		const { form, errors } = useForm();

		provide('form', form);
		provide('errors', errors);


		/////////////////////////////////
		// TEMPLATE
		/////////////////////////////////

		const itemsTemplate = [
			{'Text':'text'},
			{'Text und Bild':'text-image'},
			{'Bild':'image'},
			{'Bild-Slider':'slider-image'},
			{'Video':'video'},
			{'Accordion':'accordion'},
			{'Trenner':'separator'},
			{'Eigene Komponente':'custom-component'},
			//{'Inhaltsverzeichnis':'toc'},
		];


		function onTemplateChange() {

			// clear old content
			if(multiLangEnabled.value) {
				langs.forEach(lang => form.value['content_'+lang] = {});
			}
			else {
				form.value.content = {};
			}
		}


	</script>


