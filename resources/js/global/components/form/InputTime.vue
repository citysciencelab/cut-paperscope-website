<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div :class="['form-row', rowId]">

			<input-label :label="$attrs.label" :info="$attrs.info" v-bind="labelAttrs"/>

			<!-- INPUT -->
			<input
				v-if="!isMultiLang"
				:name="id"
				:id="inputId"
				v-model="value"

				v-bind="inputAttrs"
				@focus="removeError"
				@input="formatInput"
			/>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { computed } from 'vue';
		import { useInput } from '@global/composables/useInput';
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			modelValue:		{ type: [String, Number, Object] },		// bind variable to v-model
			error: 			{ default: null },						// form data to show error

			id:				{ type: String, default: null }, 		// unique form id for this input element
			placeholder: 	{ type: String },						// show a placeholder text input element
			multilang: 		{ type: Boolean, default: false },		// same input for all languages

			// html
			type: 			{ type: String, default: 'text' },		// html input type: text, password, email, number, ...
			readonly: 		{ type: Boolean },						// html readonly attribute
			required: 		{ type: Boolean },						// html required attribute (show asterisk)
		});

		const emit = defineEmits(['update:modelValue', 'enter']);

		const { isMultiLang, value, rowId, inputId, propId, showError, updateInput, removeError, labelAttrs } = useInput(props, emit);
		const { t } = useLanguage();


		/////////////////////////////////
		// ATTRIBUTES
		/////////////////////////////////

		const inputAttrs = computed(() => ({

			type: 			props.type,
			class:			['input-time',{'error':showError.value}],

			placeholder: 	props.placeholder ? t(props.placeholder) + (props.required ? ' *' : '') : null,
			readonly: 		props.readonly,
			maxlength: 		5,
		}));


		/////////////////////////////////
		// FORMAT
		/////////////////////////////////

		function formatInput(e) {

			value.value = validateFormat(value.value);
			updateInput(e);
		}


		function validateFormat(currentValue) {

			var val = currentValue.replace(/[^\d:]/g, ''); // Remove all characters except numbers, colon and space
			val = val.replace('::', ':'); // Remove double colon

			if (!val.includes(':') && val.length >= currentValue.length) {
				if (val.length >= 1) {
					if (val.substring(0, 1) > 2) {
						val = '0' + val.substring(0, 1) + ':' + val.substring(1); // Add leading zero and colon after first character
					}else if (val.length >= 2) {
						val = (val.substring(0, 2) > 23 ? 23 : val.substring(0, 2)) + ':' + val.substring(2); // Add colon after first two characters
					}
				}
			}

			// limit minutes to 59
			if(val.length >= 5 && val.substring(3, 5) > 59) val = val.substring(0, 3) + '59';

			return val;
		}


	</script>


