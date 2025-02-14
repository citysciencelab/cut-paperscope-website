<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="file-manager">

			<!-- NAVI -->
			<div class="file-manager-navi" v-if="!fileInput">
				<input-select class="file-manager-navi-storage" id="model-lang" label="Storage" v-model="activeStorage" :options="itemsStorage" :placeholder="null"/>
			</div>

			<!-- BREADCRUMB -->
			<div class="file-manager-breadcrumb">
				<p class="small file-manager-breadcrumb-item" v-for="(item,index) in breadcrumb" @click="onBreadcrumbClick(index)">/{{ index == 0 ? 'root' : item }}</p>
			</div>

			<!-- LIST -->
			<table ref="root" class="file-list">

				<colgroup>
					<col/>
					<col/>
					<col/>
				</colgroup>

				<thead>
					<tr>
						<th class="file-list-head">Symbol</th>
						<th colspan="2" class="file-list-head">Name</th>
						<th class="file-list-head">Bearbeiten</th>
					</tr>
				</thead>

				<tbody>

					<!-- LOADING -->
					<tr class="file-list-item loading" v-show="isLoading">
						<td colspan="3">
							<svg-item inline="backend/loading-datalist"/>
						</td>
					</tr>

					<!-- UP -->
					<tr class="file-list-item" v-show="!isLoading && breadcrumb.length>1" @click="onBreadcrumbClick(breadcrumb.length-2)">
						<td class="file-list-item-icon"><svg-item icon="backend/file-up"/></td>
						<td class="file-list-item-label" colspan="3"> <p class="file-list-item-label">..</p></td>
					</tr>

					<file-list-item
						v-show="!isLoading && folderData.length"
						v-for="item in folderData"
						:key="item.id"
						:item="item"
						:storage="storage"
						:fileInput="fileInput"
						@click="onItemClick"
						@updated="loadFolder"
						@selected="onSelectedFile"
					/>

				</tbody>

			</table>

			<!-- BUTTONS -->
			<div class="form-row-buttons" v-if="!fileInput">
				<btn :label="t('Neuer Ordner')" @click="popupNewFolder?.open()" class="small secondary"/>
				<file-uploader label="Datei hochladen" multiple-files :folder="folderPath" :storage="activeStorage" @upload-success="loadFolder"/>
			</div>

			<!-- POPUP NEW -->
			<popup class="modal" ref="popupNewFolder">
				<input-text label="Name des Ordners" info="wird automatisch in URL-Format konvertiert" id="newfolder" v-model="newFolderName"/>
				<template #buttons>
					<btn label="Abbrechen" @click="popupNewFolder.close" class="small secondary"/>
					<btn label="Bestätigen" @click="callbackNewFolder" :class="['small',{'disabled':!newFolderName.length}]"/>
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

		import { ref, useTemplateRef, computed, watch, onMounted } from 'vue';
		import { useApi } from '@global/composables/useApi';
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = useLanguage();

		const props = defineProps({
			storage: { type: String, default: window.config.storage_default },
			fileInput: { type: Boolean, default: false }, // use this component as input for InputFile
		});


		/////////////////////////////////
		// STORAGE
		/////////////////////////////////

		const activeStorage = ref(props.storage);

		const itemsStorage = [
			{'Lokaler Speicher': 'public'},
			{'Amazon S3': 's3'},
		];


		/////////////////////////////////
		// BREADCRUMB
		/////////////////////////////////

		const breadcrumb = ref([""]);
		const folderPath = computed(() => breadcrumb.value?.join('/') ?? '/');

		function onBreadcrumbClick(index) {

			// remove all items after index
			breadcrumb.value.splice(index+1);
			loadFolder();
		}


		/////////////////////////////////
		// FOLDER
		/////////////////////////////////

		const { apiPost, apiPostResponse } = useApi();
		const folderData = ref([]);
		const isLoading = ref(false);
		const root = useTemplateRef('root');

		function loadFolder() {

			// save current list height
			if(folderData.value?.length > 0) {
				const tr = u(root.value).find('.file-list-item:not(.loading)');
				const h = tr.outerHeight() + (tr.length) * 2;		// height and subtract border-spacing
				gsap.set(u(root.value).find('.file-list-item.loading').first(), { height: h });
			}
			else {
				gsap.set(u(root.value).find('.file-list-item.loading').first(), { height: 'auto' });
			}

			isLoading.value = true;
			folderData.value = [];
			selectedFile.value = null;
			newFolderName.value = '';

			const data = {
				folder: folderPath.value,
				storage: activeStorage.value,
			};

			apiPost('api.backend.file-manager.list',data, data => folderData.value = data)
			.catch(error => console.log(error))
			.finally(() => isLoading.value = false);
		}

		onMounted(loadFolder);
		watch(activeStorage, () => { breadcrumb.value = [""]; loadFolder();} );


		/////////////////////////////////
		// NEW FOLDER
		/////////////////////////////////

		const popupNewFolder = useTemplateRef('popupNewFolder');
		const newFolderName = ref('');

		function callbackNewFolder() {

			if(!newFolderName.value || !newFolderName.value.length) { return; }

			const data = {
				path: folderPath.value,
				folder: newFolderName.value,
				storage: activeStorage.value,
			};

			apiPostResponse('backend.file-manager.folder.create', data, loadFolder);

			popupNewFolder.value.close();
		}


		/////////////////////////////////
		// ITEM
		/////////////////////////////////

		function onItemClick(item) {

			// open folder
			if (item.type == 'dir') {
				breadcrumb.value.push(item.basename);
				loadFolder();
			}
		}

		/////////////////////////////////
		// SELECTED
		/////////////////////////////////

		const selectedFile = ref(null);

		function onSelectedFile(item) {

			selectedFile.value = item.path;
		}

		defineExpose({ selectedFile });


	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
			},
			"en": {
				"Neuer Ordner": "New folder",
				"Name des Ordners": "Name of folder",
			}
		}
	</i18n>
