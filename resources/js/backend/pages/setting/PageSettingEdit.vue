<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<model-edit name="Einstellung" model-route="setting" v-slot="{folder}" :published="false" :slug="false">

			<model-accordion label="Name">
				<input-text class="col-50" :label="t('Kategorie')" id="category" v-model="form" :error="errors" :max-length="50" required/>
				<input-text class="col-50" :label="t('Identifier')" id="identifier" v-model="form" :error="errors" :max-length="50" required/>
				<input-text label="Reference" v-model="reference" readonly/>
			</model-accordion>

			<model-accordion label="Name">
				<input-select :options="itemsDataType" :label="t('Daten-Typ')" id="data-type" v-model="form" :error="errors" required :readonly="!!form.id"/>
				<input-text v-if="form.data_type=='string'" label="Content" id="content" v-model="form" :error="errors" multilang/>
				<input-text v-if="form.data_type=='number'" label="Content" id="content" type="number" v-model="form" :error="errors" multilang/>
				<input-radio v-if="form.data_type=='bool'" label="Content" id="content" :options="itemsBoolean" v-model="form" :error="errors" multilang/>
				<input-richtext v-if="form.data_type=='html'" label="Content" id="content" v-model="form" :error="errors" :folder="folder" multilang/>
				<input-file v-if="form.data_type=='file'" label="Content" id="content" v-model="form" :error="errors" :folder="folder" multilang/>
			</model-accordion>

		</model-edit>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, provide, watch } from 'vue';
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


		/////////////////////////////////
		// VALUE
		/////////////////////////////////

		const itemsDataType = ref([
			{'String': 'string'},
			{'Number': 'number'},
			{'Boolean': 'bool'},
			{'HTML': 'html'},
			{'File': 'file'},
		]);


		/////////////////////////////////
		// REFERENCE
		/////////////////////////////////

		const reference = ref('reference');

		watch(form, updateReference, { deep: true });

		function updateReference() {

			reference.value = convertToSnakeCase(form.value.category) + '.' + convertToSnakeCase(form.value.identifier);
		}


		function convertToSnakeCase(value) {

			if(!value) { return ""; }

			// convert to lowercase
			value = value.toLowerCase();

			// replace double spaces
			value = value.trim().replace(/\s+/g, ' ');

			// replace special chars with underscore
			value = value.replace(/[\.\-]/g, '_');

			// replace spaces with underscore
			value = value.replace(/ /g, '_');

			return value;
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
				"Kategorie": "Category",
				"Daten-Typ": "Data type",
			}
		}
	</i18n>


