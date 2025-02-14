<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<!-- TEXT -->
		<input-text label="Titel" id="title" info="H2 Überschrift" v-model="value" :error="error" :multilang="multilang"/>
		<input-richtext label="Text" id="copy" v-model="value" :error="error" :folder="folder" :multilang="multilang"/>

		<!-- SLIDER LIST -->
		<data-list v-model="value.items" :options="options" @edit="edit" @deleted="onDeleted"/>
		<btn class="small secondary" label="Hinzufügen" @click="add"/>

		<!-- POPUP -->
		<popup ref="popupEdit">

			<div class="cols">
				<input-text class="col-50" label="Name im Backend" id="name" v-model="editItem" required/>
				<input-file class="col-50" label="Bild-Datei" info="HQ in 1600px Breite" id="image" v-model="editItem" type="image" :folder="folder" :multilang="multilang" required/>
				<input-text class="col-50" label="Bild-Unterschrift" id="image-subline" v-model="editItem" :multilang="multilang"/>
				<input-text class="col-50" label="Alt-Text für Bild" info="Beschreibung Bildinhalt" id="image-alt" v-model="editItem" :multilang="multilang"/>
			</div>

			<template #buttons>
				<btn label="Abbrechen" @click="popupEdit.close" class="small secondary"/>
				<btn label="Bestätigen" @click="confirm" :class="['small',{'disabled':!canSaveEdit}]"/>
			</template>
		</popup>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { computed, inject, ref, useTemplateRef, watch } from 'vue';
		import { useInput } from '@global/composables/useInput';
		import { useLanguage } from '@global/composables/useLanguage';


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

			id:				{ type: String, default: null }, 		// unique form id for this input element
			multilang: 		{ type: Boolean, default: false },		// same input for all languages

			// file uploader
			folder: 		{ type: String, default: undefined }, 	// sub folder in storage
		});

		const emit = defineEmits(['update:modelValue']);

		const { t, langs, defaultLang } = useLanguage();
		const { propId } = useInput(props, emit);


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		const form = inject('form');
		const modelLang = inject('modelLang');


		/////////////////////////////////
		// SLIDER LIST
		/////////////////////////////////

		const options = {
			sortLocal: true,
			deleteLocal: true,
			customEdit: true,
			imageProp: props.multilang ? 'image_'+defaultLang : 'image',
		}


		/////////////////////////////////
		// VALUE
		/////////////////////////////////

		const value = ref({});
		function setValue() {

			const prop = propId + (props.multilang ? '_' + modelLang.value : '');
			if(!form.value[prop]) { form.value[prop] = {}; }
			value.value = form.value[prop];
			if(!value.value.items) { value.value.items = []; }
		}

		watch(modelLang, () => setValue(), { immediate: true });


		/////////////////////////////////
		// ADD
		/////////////////////////////////

		function add() {

			// create new item
			editItem.value = {
				id: Date.now(),
				name: 'Slide ' + (value.value.items.length + 1)
			};

			// add item to list
			if(props.multilang) {
				langs.forEach(l => {
					const p = propId+'_'+l;
					if(!form.value[p]) { form.value[p] = { items: [] }; }
					else if(!form.value[p].items) { form.value[p].items = []; }
					form.value[p].items.push({...editItem.value});
				});
			}
			else {
				value.value.items.push({...editItem.value});
			}

			popupEdit.value.open();
		}


		/////////////////////////////////
		// EDIT
		/////////////////////////////////

		const editItem = ref(null);
		const canSaveEdit = computed(() => editItem.value?.name && (editItem.value?.image || editItem.value?.['image_'+modelLang.value]));

		function edit(id) {

			editItem.value = {...value.value.items.find(i => i.id == id)};
			popupEdit.value.open();
		}


		/////////////////////////////////
		// DELETE
		/////////////////////////////////

		function onDeleted(id) {

			// delete item
			if(props.multilang) {
				langs.forEach(l => {
					form.value[propId+'_'+l].items = form.value[propId+'_'+l].items.filter(i => i.id != id);
				});
			}
			else {
				value.value.items = value.value.items.filter(i => i.id != id);
			}
		}


		/////////////////////////////////
		// POPUP
		/////////////////////////////////

		const popupEdit = useTemplateRef('popupEdit');

		function confirm() {

			if(!canSaveEdit.value) { return; }

			// save name in all langs
			if(props.multilang) {
				langs.forEach(l => {
					const index = props.modelValue['content_'+l].items.findIndex(item => item.id === editItem.value.id);
					props.modelValue['content_'+l].items[index].name = editItem.value.name;
				});
			}

			// save item
			const index = value.value.items.findIndex(item => item.id === editItem.value.id);
			value.value.items[index] = editItem.value;

			popupEdit.value.close();
		}


	</script>


