<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<tr ref="root" :class="['file-list-item',item.type]">

			<!-- ICON -->
			<td class="file-list-item-icon" @click="onClick">
				<svg-item :icon="'backend/'+icon"/>
			</td>

			<!-- LABEL -->
			<td :colspan="isFolder?2:1" @click="onClick">
				<p class="file-list-item-label">{{ item.basename }}</p>
			</td>

			<!-- FILE ATTRIBUTES -->
			<td v-if="isFile" colspan="2" class="file-list-item-attributes">
				<p class="small">{{ formatFileSize(item.size) }}</p>
			</td>

			<file-list-buttons v-if="isFolder && !fileInput" :item="item" :storage="storage" @updated="emit('updated')"/>
			<file-list-preview v-if="isPreviewOpen" :item="item" :storage="storage" @close="isPreviewOpen=false" @updated="emit('updated')"/>

		</tr>

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


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = useLanguage();

		const props = defineProps({
			item:		{ type: Object, required: true },
			storage:	{ type: String, required: true },
			fileInput:	{ type: Boolean, default: false },	// use this component as input for InputFile
		});

		const emit = defineEmits(['click', 'updated', 'selected']);


		/////////////////////////////////
		// ICON
		/////////////////////////////////

		const icon = computed(()=> isFolder.value ? 'file-folder' : 'file-'+getFileType(props.item) );


		/////////////////////////////////
		// FILE
		/////////////////////////////////

		const root = useTemplateRef('root');
		const isFile = computed(()=> props.item.type=='file');
		const isFolder = computed(()=> props.item.type=='dir');
		const isPreviewOpen = ref(false);

		const { getFileType, formatFileSize } = useFile();


		function onClick() {

			// open folder via file manager
			if(isFolder.value) { return emit('click',props.item); }

			// select file in mode "fileInput"
			if(props.fileInput) {
				u('.file-list .selected').removeClass('selected');
				u(root.value).addClass('selected');
				return emit('selected',props.item);
			}

			isPreviewOpen.value = true;
		}


	</script>


