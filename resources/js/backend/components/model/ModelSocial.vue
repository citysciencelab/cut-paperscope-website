<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<model-accordion label="Meta / Social" :open="false">
			<input-text :label="t('title')" :info="t('title.info')" id="meta-title" v-model="form" :error="errors" :max-length="30" :multilang="multiLangEnabled"/>
			<input-text :label="t('description')" :info="t('description.info')" id="meta-description" v-model="form" :error="errors" :max-length="160" :multilang="multiLangEnabled"/>
			<input-text :label="t('sharing_title')" :info="t('sharing_title.info')" id="social-description" v-model="form" :error="errors" :max-length="297" :multilang="multiLangEnabled"/>
			<input-file :label="t('sharing_image')" :info="t('sharing_image.info')" id="social-image" v-model="form" :error="errors" type="image" :folder="folder" :multilang="multiLangEnabled" />
		</model-accordion>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, inject } from 'vue';
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			errors: 		{ type: Object, default: ()=>({}) },		// form data to show error
			folder: 		{ type: String, required: true }, 			// sub folder in storage

		});


		/////////////////////////////////
		// MODEL
		/////////////////////////////////

		const form = inject('form', ref({}));


		/////////////////////////////////
		// MODEL
		/////////////////////////////////

		const { t, multiLangEnabled } = useLanguage();


	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
				"title": "HTML-Seitentitel",
				"title.info": "für Browser-Tab, Bookmarks, Sharing-Titel",
				"description": "Meta-Description",
				"description.info": "für Google-Suchergebnisse",
				"sharing_title": "Text für Social-Sharing",
				"sharing_title.info": "Vorschautext in Postings",
				"sharing_image": "Bild für Social-Sharing",
				"sharing_image.info": "HQ in 1440x720px",
			},
			"en": {
				"title": "HTML Title",
				"title.info": "for browser tab, bookmarks, sharing title",
				"description": "Meta Description",
				"description.info": "for Google search results",
				"sharing_title": "Text for Social Sharing",
				"sharing_title.info": "preview text in postings",
				"sharing_image": "Image for Social Sharing",
				"sharing_image.info": "HQ in 1440x720px",
			}
		}
	</i18n>


