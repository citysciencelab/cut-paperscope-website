<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<model-accordion label="Basis">
			<input-text label="Name im Backend" id="name" v-model="form" :error="errors" :max-length="50" required/>
			<input-text v-if="slug" :label="t('URL')" :info="t('automatisch wenn keine Angabe')" id="slug" v-model="form" :error="errors" :max-length="100" required/>
			<div v-if="published" class="col-33">
				<input-date-time :label="t('Start')" :info="t('Veröffentlichung')" id="published-start" v-model="form" :error="errors" required/>
			</div>
			<div v-if="published" class="col-33">
				<input-date-time :label="t('Ende')" :info="t('Laufzeit')" id="published-end" v-model="form" :error="errors"/>
			</div>
			<div class="col-33"></div>
			<input-radio :label="t('Sichtbarkeit')" id="public" :options="itemsPublic" v-model="form" :error="errors" required/>
		</model-accordion>

</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, inject } from 'vue';
		import { useForm } from '@global/composables/useForm';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			errors: 		{ type: Object, default: ()=>({}) },		// form data to show error
			modelRoute: 	{ type: String, required: true }, 			// name of the model route. Example: 'page', 'item', 'user'

			published:		{ type: Boolean, default: true },			// model requires published attributes
			slug: 			{ type: Boolean, default: true },			// model requires a slug
		});


		/////////////////////////////////
		// MODEL
		/////////////////////////////////

		const { itemsPublic } = useForm();

		const form = inject('form', ref({}));
		const errors = inject('errors', ref({}));

		if(!form.value.public) { form.value.public = false; }


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
				"Basis": "Basic",
				"automatisch wenn keine Angabe": "generated if not specified",
				"Veröffentlichung": "publish at",
				"Ende": "End",
				"Laufzeit": "duration",
				"Sichtbarkeit": "Visibility",
			}
		}
	</i18n>



