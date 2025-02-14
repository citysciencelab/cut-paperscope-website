<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div :class="['form-row', rowId]">

			<!-- INPUT -->
			<input-label :label="$attrs.label" :info="$attrs.info" v-bind="labelAttrs"/>
			<data-list v-if="hasParent && options" ref="list" :options="options" :cols="cols"/>
			<btn v-if="hasParent && params" class="small secondary" label="Hinzufügen" :to="`backend.${fragment}.edit`" :params="params" :query="{order:highestOrder}"/>

			<!-- EMPTY -->
			<p v-if="!hasParent" class="input disabled input-fragments-empty">
				{{ t('items.empty') }}
			</p>

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
		import { useLanguage } from '@global/composables/useLanguage';
		import { useRoute } from 'vue-router';


		/*
		*	Usage:
		*	<input-text label="Click" id="username" v-model="myVar"/>
		*/


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			modelValue:		{ type: [String, Number, Object] },		// bind variable to v-model
			error: 			{ default: null },						// form data to show error
			fragment: 		{ type: String, default: 'fragment' },	// fragment name

			id:				{ type: String, default: null }, 		// unique form id for this input element
			placeholder: 	{ type: String },						// show a placeholder text input element
			multilang: 		{ type: Boolean, default: false },		// same input for all languages

			// html
			readonly: 		{ type: Boolean },						// html readonly attribute
			required: 		{ type: Boolean },						// html required attribute (show asterisk)
		});

		const emit = defineEmits(['update:modelValue', 'enter']);

		const { isMultiLang, rowId, labelAttrs } = useInput(props, emit);


		/////////////////////////////////
		// DATA
		/////////////////////////////////

		const route = useRoute();
		const hasParent = computed(()=> route.params.id ? true : false );
		const list = useTemplateRef('list');
		const items = computed(()=> list.value?.items);
		const highestOrder = computed(()=> items.value?.length ? items.value.toSorted((a,b)=>a.order<b.order)[0].order+1 : 1);


		/////////////////////////////////
		// DATALIST
		/////////////////////////////////

		var params = null;
		var options = null;

		const cols = [
			{label: 'Template', property: 'template', type: "string"},
			{label: 'Start', property: 'published_start', type: 'string'},
			{label: 'Ende', property: 'published_end', type: 'string'},
		];


		function setOptions() {

			params = {
				parentType: route.name.split('.')[1],
				parent: route.params.id,
			};

			options = {
				routes: {
					load: `api.backend.${props.fragment}.list.child`,
					loadParams: { parent: route.params.id },
					content: 'backend.fragment.edit',
					contentParams: params,
					edit: 'backend.fragment.edit',
					editParams: params,
					sort: `api.backend.${props.fragment}.sort`,
					delete: `api.backend.${props.fragment}.delete`,
				},
			};
		}

		watch(hasParent, ()=> setOptions(), {immediate: true});


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
				"items.empty": "Elemente können erst hinzugefügt werden, wenn das Objekt gespeichert wurde.",
			},
			"en": {
				"items.empty": "Elements can only be added once the object has been saved.",
			}
		}
	</i18n>
