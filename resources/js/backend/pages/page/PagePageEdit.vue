<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<model-edit name="Unterseite" model-route="page" page v-slot="{folder}">

			<!-- HEADER NAVI -->
			<model-accordion :label="t('navi_accordion')">
				<input-radio :label="t('navi_visible')" id="navi-visible" :options="itemsBoolean" v-model="form" :error="errors" required/>
				<input-text v-if="form.navi_visible" :label="t('navi_label')" id="navi-label" v-model="form" :error="errors" :max-length="30" multilang/>
			</model-accordion>

			<!-- DETAIL PAGE -->
			<model-accordion :label="t('content_accordion')">
				<input-text :label="t('title')" :info="t('title.info')" id="title" v-model="form" :error="errors" :max-length="150" multilang/>
				<input-fragments :label="t('content')" id="content" v-model="form" :error="errors"/>
			</model-accordion>

		</model-edit>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { provide } from 'vue';
		import { usePage } from '@global/composables/usePage';
		import { useForm } from '@global/composables/useForm';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = usePage("Backend");


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		const { form, errors, itemsBoolean } = useForm();

		provide('form', form);
		provide('errors', errors);

	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
				"navi_accordion": "Haupt-Navi",
				"navi_visible": "Seite in der Haupt-Navi anzeigen?",
				"navi_label": "Bezeichner in der Haupt-Navi",

				"content_accordion": "Inhalte",
				"title": "Titel Detailseite",
				"title.info": "H1 Überschrift",
				"items": "Items",
				"content": "Inhalte Detailseite",
			},
			"en": {
				"navi_accordion": "Main navi",
				"navi_visible": "Show page in main navi?",
				"navi_label": "Label in main navi",

				"content_accordion": "Content",
				"title": "Title detail page",
				"title.info": "H1 heading",
				"items": "Items",
				"content": "Content detail page",
			}
		}
	</i18n>

