<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div :class="['form-row', rowId]">

			<input-label :label="$attrs.label" :info="$attrs.info" v-bind="{...labelAttrs, id:inputId+'-0'}"/>

			<!-- INPUT -->
			<div v-for="(opt,index) in options" :class="['input-radio',{'error':showError}]">
				<input
					type="radio"
					:name="inputId"
					:id="inputId+'-'+index"
					:value="getValue(opt)"
					v-model="value"
					@change="updateInput"
				/>
				<label :for="inputId+'-'+index" :class="{error: !$attrs.label && showError && options.length==1}">
					<slot :label="getLabel(opt)" :value="getValue(opt)">{{ t(getLabel(opt)) }}</slot>
				</label>
				<div class="input-radio-icon">
					<svg-item :icon="webContext+'/input-radio'"/>
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

		import { ref } from 'vue';
		import { useInput } from '@global/composables/useInput';


		/*
		*	Usage:
		*	<input-radio label="Click" id="gender" v-model="myVar" :options="[ {'Label':123}, ... ]"/>
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

		const { value, rowId, inputId, showError, updateInput, labelAttrs } = useInput(props, emit);


		/////////////////////////////////
		// OPTIONS
		/////////////////////////////////

		const getValue = opt => Object.values(opt)[0];
		const getLabel = opt => Object.keys(opt)[0];


		/////////////////////////////////
		// INPUT
		/////////////////////////////////

		// convert value to boolean
		if(value.value==="1") { value.value = true; }
		else if(value.value==="0") { value.value = false; }


	</script>


