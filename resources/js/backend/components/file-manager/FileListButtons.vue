<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<td class="file-list-buttons">

			<!-- RENAME -->
			<div class="file-list-btn rename" v-if="isFolder && storage!='s3'" @click="popupRename.open">
				<svg-item icon="backend/datalist-edit"/>
			</div>

			<!-- DELETE -->
			<div class="file-list-btn delete" v-if="isFolder" @click="confirmDelete">
				<svg-item icon="backend/datalist-delete"/>
			</div>

			<!-- POPUP RENAME -->
			<popup class="modal" ref="popupRename">
				<input-text label="Name des Ordners" info="wird automatisch in URL-Format konvertiert" id="newfolder" v-model="newFolderName"/>
				<template #buttons>
					<btn label="Abbrechen" @click="popupRename.close" class="small secondary"/>
					<btn label="Bestätigen" @click="rename" :class="['small',{'disabled':!newFolderName.length}]"/>
				</template>
			</popup>

			<!-- POPUP DELETE -->
			<popup-modal ref="deleteModal"/>

		</td>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, computed } from 'vue';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useApi } from '@global/composables/useApi';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = useLanguage();

		const props = defineProps({
			item: { type: Object, required: true },
			storage: { type: String, required: true },
		});

		const emit = defineEmits(['updated']);
		const { apiPostResponse } = useApi();


		/////////////////////////////////
		// FILE
		/////////////////////////////////

		const isFile = computed(()=> props.item.type=='file');
		const isFolder = computed(()=> props.item.type=='dir');


		/////////////////////////////////
		// RENAME
		/////////////////////////////////

		const popupRename = useTemplateRef('popupRename');
		const newFolderName = ref('');

		function rename() {

			if(!newFolderName.value || !newFolderName.value.length) { return; }

			const data = {
				path: props.item.path.replace(props.item.basename,''),
				folder: props.item.basename,
				newFolder: newFolderName.value,
				storage: props.storage,
			};

			apiPostResponse('backend.file-manager.folder.rename', data, response => {
				newFolderName.value = '';
				emit('updated');
			});

			popupRename.value.close();
		}


		/////////////////////////////////
		// DELETE
		/////////////////////////////////

		const deleteModal = useTemplateRef('deleteModal');

		function confirmDelete() {

			deleteModal.value.open({
				title: t("Ordner löschen"),
				copy: t('delete.copy',{folder: props.item.basename}),
				alert: true,
				confirmLabel: t("Ordner löschen"),
				callback: callbackDelete
			});
		}

		function callbackDelete() {

			const data = {
				path: props.item.path,
				storage: props.storage,
			};

			apiPostResponse('backend.file-manager.folder.delete', data, response => emit('updated'));
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
				"delete.copy": "Möchtest du den Ordner \"{folder}\" wirklich löschen? Der gesamte Inhalt wird ebenfalls gelöscht."
			},
			"en": {
				"Ordner löschen": "Delete folder",
				"delete.copy": "Do you really want to delete the \"{folder}\" folder? The entire contents will also be deleted."
			}
		}
	</i18n>

