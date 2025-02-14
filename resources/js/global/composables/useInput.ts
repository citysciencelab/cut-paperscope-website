/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { ref, computed, inject, watch, getCurrentInstance } from 'vue';

	// app
	import { useLanguage } from '@global/composables/useLanguage';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPOSABLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


export const useInput = (props: any, emit: Function) => {


	/////////////////////////////////
	// IDS
	/////////////////////////////////

	const uid = getCurrentInstance().uid;

	// unique ids for instance
	const rowId 	= props.id ? `row-${props.id}` : `row-${uid}`;
	const inputId 	= props.id ? `input-${props.id}` : `input-${uid}`;
	const propId 	= props.id?.replaceAll('-','_') ?? `val_${uid}`;


	/////////////////////////////////
	// MULTILANG
	/////////////////////////////////

	const { activeLang } = useLanguage();

	const modelLang = inject('modelLang', activeLang);
	const isMultiLang = computed(() => props.multilang && modelIsObject.value);


	/////////////////////////////////
	// VALUE
	/////////////////////////////////

	const value = ref(null);
	const modelIsObject = computed(() => typeof props.modelValue === 'object' && !Array.isArray(props.modelValue));


	/**
	 * When the parent model value (v-model) changes, update the internal input value
	 */

	function setValue() {

		if(isMultiLang.value) {
			value.value = props.modelValue?.[propId+'_'+modelLang.value] ?? null;
		}
		else {
			value.value = modelIsObject.value ? (props.modelValue[propId] ?? null) : props.modelValue;
		}
	}


	/**
	 * When the internal input value changes, update the parent model value (v-model)
	 */

	function updateInput() {

		var newValue = props.modelValue;

		if(isMultiLang.value) { newValue[propId+'_'+modelLang.value] = value.value; }
		else if(modelIsObject.value) { newValue[propId] = value.value; }
		else { newValue = value.value; }

		emit('update:modelValue', newValue);
	}

	watch(() => props.modelValue, setValue, {deep:true, immediate:true});
	watch(() => modelLang.value, setValue);


	/////////////////////////////////
	// ERROR
	/////////////////////////////////

	const error = ref(props.error);

	// props.error mostly saves the error messages from api responses as an object
	const errorIsObject = computed(() => error.value && typeof error.value == 'object' && !Array.isArray(error.value));

	// Find an error in the error object for the current language
	const showError = computed(()=>{

		const prop = isMultiLang.value ? propId+'_'+modelLang.value : propId;
		return errorIsObject.value ? !!error.value[prop] : !!error.value
	});


	function removeError() {

		error.value = false;
	}


	watch(() => props.error, newError => error.value = newError);


	/////////////////////////////////
	// LABEL
	/////////////////////////////////

	// props passed to the <label> element
	const labelAttrs = computed(() => ({
		id: 		inputId,
		multilang: 	isMultiLang.value,
		error: 		showError.value,
		required: 	props.required,
	}));


	/////////////////////////////////
	// EXPORT
	/////////////////////////////////

	return {
		rowId, inputId, propId,
		value, updateInput, modelIsObject,
		isMultiLang,
		showError, removeError,
		labelAttrs,
	};



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */



};
