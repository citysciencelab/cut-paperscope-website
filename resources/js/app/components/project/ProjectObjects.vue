<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="project-objects">

			<!-- LIST -->
			<div class="mapping-item" v-for="(m,i) in form.mapping" v-if="form.mapping?.length">
				<input-select :options="getFreeSources(m)" placeholder="" v-model="form.mapping[i].source"/>
				<svg-item class="mapping-item-spacer" sprite="app/mapping-separator"/>
				<input-select :options="getFreeColors(m)" placeholder="" v-model="form.mapping[i].color"/>
				<svg-item class="mapping-item-spacer" sprite="app/mapping-separator"/>
				<input-select :options="itemsTarget" v-model="form.mapping[i].target"/>
				<btn icon="btn-edit" @click="openEdit(m)"/>
				<btn icon="btn-delete" @click="confirmDelete(m)"/>
			</div>

			<!-- EMPTY-->
			<p class="empty" v-else>
				{{ t("Noch keine Objekte definiert") }}
			</p>

			<!-- BUTTONS -->
			<div class="form-row-buttons" v-if="hasFreeObjects">
				<btn :label="t('Objekt hinzufügen')" icon="btn-add" @click="addObject"/>
			</div>

			<!-- EDIT -->
			<popup ref="editPopup" lightbox>
				<div v-if="editMapping.target == 'shape-2d'" class="cols">
					<input-text class="col-50" :label="t('Farbe')" v-model="editMapping.props.fill"/>
					<input-text class="col-50" :label="t('Farbe Outline')" v-model="editMapping.props.stroke"/>
				</div>
				<div v-if="editMapping.target == 'shape-3d'" class="cols">
					<input-text class="col-50" :label="t('Farbe')" v-model="editMapping.props.fill"/>
					<input-text class="col-50" :label="t('Gebäudehöhe')" :info="t('Meter')" v-model="editMapping.props.height"/>
				</div>
				<div v-if="editMapping.target == 'model'" class="cols">
					<input-user-upload label="Datei-Upload" id="file" info="*.gltf oder *.glb" type="3d" v-model="editMapping.props.file" :folder="uploadFolder"/>
					<input-text class="col-50" type="number" :label="t('Skalierung')" v-model="editMapping.props.scale"/>
					<input-text class="col-50" type="number" :label="t('Rotation')" :info="t('Grad')" v-model="editMapping.props.rotation"/>
				</div>

				<!-- ATTRIBUTES -->
				<div class="fragment-separator"></div>
				<div class="cols" v-if="editMapping.attributes?.length">
					<label class="col-50">Attribut</label>
					<label class="col-50">Wert</label>
				</div>
				<div v-for="(a,i) in editMapping.attributes" class="cols project-objects-attribute">
					<input-text class="col-50" v-model="editMapping.attributes[i].key"/>
					<input-text class="col-50" v-model="editMapping.attributes[i].value"/>
					<btn icon="btn-delete" @click="deleteAttribute(i)"/>
				</div>
				<btn :label="t('Attribut hinzufügen')" icon="btn-add" @click="addAttribute"/>
			 </popup>

			<!-- DELETE -->
			<popup-modal ref="deleteModal"/>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, computed, useTemplateRef, inject } from 'vue';
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const form = inject('form');
		const { t } = useLanguage();

		/////////////////////////////////
		// INIT
		/////////////////////////////////

		if(!form.value.mapping) { form.value.mapping = []; }

		const formValues = computed(() => form.value.mapping.map(m => m.source+'-'+m.color));


		/////////////////////////////////
		// SOURCE
		/////////////////////////////////

		const itemsSource = [
			{'Quadrat': 'rectangle'},
			{'Dreieck': 'triangle'},
			{'Kreis': 'circle'},
			{'Organisch': 'organic'},
			{'Kreuz': 'cross'},
		];

		const itemsColor = [
			{'Alle': 'all'},
			{'Schwarz': 'black'},
			// {'Rot': 'red'},
			{'Blau': 'blue'},
			{'Grün': 'green'},
			{'Gelb': 'yellow'},
		];

		const getFreeSources = (mapping,index) => {

			return itemsSource.filter(s => {
				const value = Object.values(s)[0];
				if(mapping.source == value) { return true; }
				const count = formValues.value.filter(v => v.includes(value)).length;
				return count < itemsColor.length;
			});
		};

		const getFreeColors = (mapping,index) => {

			return itemsColor.filter(c => {
				const value = Object.values(c)[0];
				const hasValue = formValues.value.find(v => v == mapping.source+'-'+value);
				if(!hasValue) { return true; }
				if(hasValue == mapping.source+'-'+mapping.color) { return true; }
			});
		};


		/////////////////////////////////
		// TARGET
		/////////////////////////////////

		const itemsTarget = [
			{'Form 2D':'shape-2d'},
			{'Gebäude':'shape-3d'},
			{'Grünfläche':'greenspace'},
			{'3D Modell':'model'},
		];


		/////////////////////////////////
		// OBJECT
		/////////////////////////////////

		function addObject() {

			// get first available source
			const source = getFreeOjbect();
			if(!source) { return; }

			// add object
			form.value.mapping.push({
				source: source.split('-')[0],
				color: source.split('-')[1],
				target: 'shape-2d',
				props: {
					fill: '#ff0000',
					stroke: '#7F7F7F',
					height: 20,
					file: undefined,
					scale: 1.0,
					rotation: 0
				},
				attributes: [],
			});
		}

		function deleteObject(mapping) {

			const index = form.value.mapping.findIndex(m => m.source == mapping.source && m.color == mapping.color);
			form.value.mapping.splice(index, 1);
		}

		const hasFreeObjects = computed(() => {

			return form.value.mapping.length < itemsSource.length * itemsColor.length;
		});

		function getFreeOjbect() {

			var formValues = form.value.mapping.map(m => m.source+'-'+m.color);
			var response = "";

			// add source
			for(var i = 0; i < itemsSource.length; i++) {
				const source = Object.values(itemsSource[i])[0];
				const count = formValues.filter(s => s.includes(source)).length;
				if(count < itemsColor.length) { response = source; break; }
			}

			// add color
			for(var i=0; i<itemsColor.length; i++) {
				const color = Object.values(itemsColor[i])[0];
				const hasValue = formValues.find(s => s == response+'-'+color);
				if(!hasValue) { return response + '-' + color; }
			}

			return null;
		}


		/////////////////////////////////
		// EDIT
		/////////////////////////////////

		const editPopup = useTemplateRef('editPopup');
		const editMapping = ref(null);
		const uploadFolder = ref('');

		function openEdit(mapping) {

			editMapping.value = mapping;
			if(!editMapping.value.attribute) { editMapping.value.attribute = ''; }
			uploadFolder.value = `projects/${mapping.source}-${mapping.color}/`;

			editPopup.value.open();
		}

		function addAttribute() {

			if(!editMapping.value.attributes) { editMapping.value.attributes = []; }

			editMapping.value.attributes.push({
				key: '',
				value: ''
			});
		}

		function deleteAttribute(index) {

			editMapping.value.attributes.splice(index, 1);
		}


		/////////////////////////////////
		// DELETE
		/////////////////////////////////

		const deleteModal = useTemplateRef('deleteModal');

		function confirmDelete(mapping) {

			deleteModal.value.open({
				title: t("Objekt löschen"),
				copy: t("delete.copy"),
				alert: true,
				confirmLabel: t("Objekt löschen"),
				callback: () => deleteObject(mapping)
			});
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
				"delete.copy": "Möchten Sie dieses Objekt wirklich löschen?"
			},
			"en": {
				"Noch keine Objekte definiert": "No objects defined yet",
				"Farbe": "Color",
				"Farbe Outline": "Outline Color",
				"Gebäudehöhe": "Building Height",
				"Meter": "Meter",
				"Rotation": "Rotation",
				"Grad": "Degree",
				"Attribut": "Attribute",
				"Objekt hinzufügen": "Add Object",
				"Objekt löschen": "Delete Object",
				"delete.copy": "Do you really want to delete this object?"
			}
		}
	</i18n>
