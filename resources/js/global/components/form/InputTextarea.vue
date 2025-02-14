<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div :class="['form-row', rowId]">

			<input-label :label="$attrs.label" :info="$attrs.info" v-bind="labelAttrs"/>

			<!-- MAXLENGTH -->
			<span class="input-maxlength" v-if="maxLength>0">{{ maxLength - (value?.length ?? 0) }}</span>

			<!-- INPUT -->
			<textarea
				:name="id"
				:id="inputId"
				v-model="value"

				v-bind="inputAttrs"
				@focus="removeError"
				@input="updateInput"
				@keyup.enter="emit('enter')"
			></textarea>

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
			readonly: 		{ type: Boolean },						// html readonly attribute
			autofocus: 		{ type: Boolean },						// html autofocus attribute
			maxLength: 		{ type: Number, default: -1 },			// html maxlength attribute
			required: 		{ type: Boolean },						// html required attribute (show asterisk)
		});

		const emit = defineEmits(['update:modelValue', 'enter']);

		const { value, rowId, inputId, showError, updateInput, removeError, labelAttrs } = useInput(props, emit);
		const { t } = useLanguage();


		/////////////////////////////////
		// ATTRIBUTES
		/////////////////////////////////

		const inputAttrs = computed(() => ({

			class:			['input-textarea',{'error':showError.value}],

			placeholder: 	props.placeholder ? t(props.placeholder) + (props.required ? ' *' : '') : null,
			readonly: 		props.readonly,
			autofocus: 		props.autofocus,
			maxlength: 		props.maxLength>0 ? props.maxLength : null,
		}));


	</script>


