<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<popup ref="root" :auto-open="true" v-bind="$attrs">

			<div class="cols">
				<!-- IMAGE -->
				<div class="col-50">
					<div class="file-list-preview-image">
						<img v-if="itemType=='image'" :src="itemUrl" :alt="t('Vorschaubild')"/>
						<svg-item v-else class="file-list-preview-icon" :icon="'backend/file-'+itemType"/>
					</div>
				</div>
				<!-- INFO -->
				<div class="col-50">
					<h4>{{ item.basename }}</h4>
					<p>
						Dateigröße: {{ formatFileSize(item.size) }}<br>
						Zuletzt geändert: {{ timestampToDate(item.last_modified) }}<br>
					</p>
				</div>
			</div>

			<!-- BUTTONS -->
			<template #buttons>
				<btn :label="t('Datei löschen')" class="small secondary" @click="confirmDelete"/>
				<btn :label="t('URL kopieren')" class="small secondary" ref="copyButton" @click="copyClipboard" blocking/>
				<btn :label="t('Datei öffnen')" class="small secondary" :href="itemUrl"/>
			</template>

		</popup>

		<!-- POPUP DELETE -->
		<popup-modal ref="deleteModal"/>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, computed } from 'vue';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useFile } from '@global/composables/useFile';
		import { useApi } from '@global/composables/useApi';
		import { useDate } from '@global/composables/useDate';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = useLanguage();

		const props = defineProps({
			item: { type: Object, required: true },
			storage: { type: String, required: true },
		});

		const emit = defineEmits(['updated']);
		const root = useTemplateRef('root');


		/////////////////////////////////
		// FILE
		/////////////////////////////////

		const { getFileType, formatFileSize } = useFile();
		const { timestampToDate } = useDate();

		const itemType = getFileType(props.item);
		const itemUrl = window.config['storage_url_'+props.storage] + encodeURI(props.item.path);


		/////////////////////////////////
		// DELETE
		/////////////////////////////////

		const deleteModal = ref(null);
		const { apiPostResponse } = useApi();

		function confirmDelete() {

			deleteModal.value.open({
				title: t("Datei löschen"),
				copy: t('delete.copy',{file: props.item.basename}),
				alert: true,
				confirmLabel: t("Datei löschen"),
				callback: callbackDelete
			});
		}

		function callbackDelete() {

			const data = { ...props.item, storage: props.storage };
			apiPostResponse('backend.file-manager.delete', data, response => {
				emit('updated');
				root.value.close();
			});
		}


		/////////////////////////////////
		// CLIPBOARD
		/////////////////////////////////

		const copyButton = ref(null);

		function copyClipboard() {

			navigator.clipboard.writeText(itemUrl);
			setTimeout(() => copyButton.value.setLoading(false), 500);
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
				"delete.copy": "Möchtest du die Datei \"{file}\" wirklich löschen?",
			},
			"en": {
				"Dateigröße": "File size",
				"Zuletzt geändert": "Last modified",
				"Datei löschen": "Delete file",
				"URL kopieren": "Copy url",
				"Datei öffnen": "Open file",
				"delete.copy": "Do you really want to delete the file \"{file}\"?",
			}
		}
	</i18n>

