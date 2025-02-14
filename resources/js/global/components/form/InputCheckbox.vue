<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div :class="['form-row', rowId]">

			<input-label :label="$attrs.label" :info="$attrs.info" v-bind="{...labelAttrs, id:inputId+'-0'}"/>

			<!-- INPUT -->
			<div v-for="(opt,index) in options" :class="['input-checkbox',{'error':showError}]">
				<input
					type="checkbox"
					:name="inputId + (options.length>1?'[]':'')"
					:id="inputId+'-'+index"
					:value="getValue(opt)"
					v-model="value"
					@change="updateInput"
				/>
				<label :for="inputId+'-'+index" :class="{error: !$attrs.label && showError && options.length==1}">
					<slot :label="getLabel(opt)" :value="getValue(opt)">{{ getLabel(opt) }}</slot>
				</label>
				<div class="input-checkbox-icon" role="presentation">
					<svg-item :icon="webContext+'/input-checkbox'"/>
				</div>

			</div>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, watch } from 'vue';
		import { useInput } from '@global/composables/useInput';
		import { useLanguage } from '@global/composables/useLanguage';


		/*
		*	Usage:
		*	<input-checkbox label="Click" id="gender" v-model="myVar" :options="[ {'Label':123}, ... ]"/>
		*/


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			modelValue:		{ default: undefined },						// bind variable to v-model
			error: 			{ default: null },						// form data to show error
			options: 		{ type: Array, required: true }, 		// array of objects with label and value

			id:				{ type: String, default: null }, 		// unique form id for this input element
			lang: 			{ type: String, default: undefined },

			// html
			readonly: 		{ type: Boolean },						// html readonly attribute
			required: 		{ type: Boolean },						// html required attribute (show asterisk)
		});

		const emit = defineEmits(['update:modelValue']);

		const { modelIsObject, value, rowId, inputId, propId, showError, updateInput, labelAttrs } = useInput(props, emit);
		const { t } = useLanguage();


		/////////////////////////////////
		// OPTIONS
		/////////////////////////////////

		const getValue = opt => typeof opt == 'string' ? opt : Object.values(opt)[0];
		const getLabel = opt => typeof opt == 'string' ? t(opt) : t(Object.keys(opt)[0]);


		/////////////////////////////////
		// INPUT
		/////////////////////////////////

		const parseValue = () => {

			// force array value
			if(value.value != undefined) {
				value.value = Array.isArray(value.value) ? value.value : [value.value] ;
				if(props.options.length==1) { value.value = value.value[0]; }
			}
			else {
				value.value = [];
			}
		}

		parseValue();
		watch(() => props.modelValue, parseValue);



	</script>


