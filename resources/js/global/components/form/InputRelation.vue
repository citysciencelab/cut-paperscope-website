<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div :class="['form-row', rowId]">

			<!-- INPUT -->
			<input-label :label="$attrs.label" :info="$attrs.info" v-bind="labelAttrs"/>
			<data-list ref="list" v-model="value" @sorted="updateInput" @deleted="updateInput" :options="optionsRelation" :cols="cols"/>
			<btn class="small secondary" label="Hinzufügen" v-if="!isMaxlength" @click="openItems"/>

			<!-- POPUP -->
			<popup ref="popupItems">

				<data-list v-model="items" :exclude="value??[]" :options="optionsItem" :cols="cols" @edit="addItem"/>

				<template #buttons>
					<p v-if="maxLength!=0" class="input-relation-info">
						{{
							t('items.max'+(isMobile?'.mobile':''),{count: (value?.length ?? 0), max: maxLength})
						}}
					</p>
					<btn label="Bestätigen" @click="confirm" class="small"/>
				</template>
			</popup>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, computed, inject, watch } from 'vue';
		import { useInput } from '@global/composables/useInput';
		import { useBreakpoints } from '@global/composables/useBreakpoints';
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			modelValue:		{ type: [String, Number, Object] },		// bind variable to v-model
			error: 			{ default: null },						// form data to show error

			relation: 		{ type: String, required: true },		// model name of relation
			maxLength: 		{ type: Number, default: 0 },			// maximum number of relation items

			options: 		{ type: Object, default() { return {}; } },	// options for datalist
			cols: 			{ type: Array, default() { return []; } }, 	// columns for datalist

			id:				{ type: String, default: null }, 		// unique form id for this input element
			placeholder: 	{ type: String },						// show a placeholder text input element
			multilang: 		{ type: Boolean, default: false },		// same input for all languages

			// html
			readonly: 		{ type: Boolean },						// html readonly attribute
			required: 		{ type: Boolean },						// html required attribute (show asterisk)
		});

		const emit = defineEmits(['update:modelValue', 'enter']);
		const { isMobile } = useBreakpoints();


		/////////////////////////////////
		// DATALIST
		/////////////////////////////////

		const list = useTemplateRef('list');
		const isMaxlength = computed(()=> props.maxLength!=0 && value.value && value.value.length >= props.maxLength);

		const optionsRelation = Object.assign({}, props.options, {
			sortLocal: true,
			deleteLocal: true,
		});

		const optionsItem = Object.assign({}, props.options, {
			routes: {
				load: `api.backend.${props.relation}.list`,
			},
			customEdit: true,
			customEditIcon: 'datalist-add',
		});


		/////////////////////////////////
		// VALUE
		/////////////////////////////////

		const { value, updateInput, rowId, labelAttrs } = useInput(props, emit);

		watch(value,()=>{
			if(value.value && !Array.isArray(value.value)) {
				value.value = [value.value];
				updateInput();
			}
		},{immediate:true});


		/////////////////////////////////
		// ITEM
		/////////////////////////////////

		const items = ref([]);
		const popupItems = useTemplateRef('popupItems');

		function openItems() {

			popupItems.value.open();
		}

		function addItem(id) {

			if(isMaxlength.value) { return; }

			const target = items.value.find(i => i.id == id);

			// update input
			if(!value.value) { value.value = []; }
			value.value.push(target);
			updateInput();
		}

		function confirm() {

			popupItems.value.close();
		}


		/////////////////////////////////
		// MULTILANG
		/////////////////////////////////

		const { t, defaultLang, langs } = useLanguage();

		const modelLang = inject('modelLang', defaultLang);
		const availableLangs = computed(() => isMultiLang.value ? langs : ['val']);

	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
				"items.max": "{count} von {max} Elementen ausgewählt",
				"items.max.mobile": "{count} von {max}",
			},
			"en": {
				"items.max": "{count} of {max} elements selected",
				"items.max.mobile": "{count} of {max}",
			}
		}
	</i18n>
