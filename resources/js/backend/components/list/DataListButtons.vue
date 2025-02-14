<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<td class="data-list-buttons">

			<!-- EDIT -->
			<component :is="exclude?'div':'router-link'" router-link :class="['data-list-btn','edit',{'exclude':exclude}]" :title="t('Bearbeiten')" v-if="editRoute" :to="link(editRoute, editParams)">
				<svg-item icon="backend/datalist-edit"/>
			</component>

			<!-- CUSTOM -->
			<button :class="['data-list-btn','custom',{'exclude':exclude}]" :title="t('Bearbeiten')" v-if="options.customEdit" @click="exclude?null:emit('edit',item.id)">
				<svg-item :icon="'backend/'+(options.customEditIcon??'datalist-edit')"/>
			</button>

			<!-- PREVIEW -->
			<a :href="getPreviewUrl()" target="_blank" class="data-list-btn preview" :title="t('Vorschau')" v-if="previewRoute">
				<svg-item icon="backend/datalist-preview"/>
			</a>

			<!-- SORT -->
			<button class="data-list-btn sort" :title="t('Sortieren')" v-if="sortRoute || options.sortLocal">
				<svg-item icon="backend/datalist-sort"/>
			</button>

			<!-- DELETE -->
			<button class="data-list-btn delete" :title="t('Löschen')" v-if="deleteRoute || options.deleteLocal" @click="confirmDelete">
				<svg-item icon="backend/datalist-delete"/>
			</button>

			<popup-modal ref="deleteModal"/>

		</td>


	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, inject, computed } from 'vue';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useApi } from '@global/composables/useApi';
		import { useConfig } from '@global/composables/useConfig';
		import { useUser } from '@global/composables/useUser';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = useLanguage();
		const { apiPost } = useApi();

		const props = defineProps({
			item: { type: Object, required: true },
			exclude: { type: Boolean, default: false }
		});

		const emit = defineEmits(['edit','deleted']);


		/////////////////////////////////
		// OPTIONS
		/////////////////////////////////

		const options = inject('options', {});

		const editRoute = computed(()=> options.routes?.edit);
		const editParams = computed(()=> ({id: props.item.id, ...options.routes?.editParams }) );
		const previewRoute = computed(()=> options.routes?.preview);
		const sortRoute = computed(()=> options.routes?.sort);
		const deleteRoute = computed(()=> options.routes?.delete);


		/////////////////////////////////
		// PREVIEW
		/////////////////////////////////

		const { baseUrl } = useConfig();
		const { user } = useUser();


		function getPreviewUrl() {

			let url = previewRoute.value;

			if(props.item.id) { url = url.replace('{id}', props.item.id); }
			if(props.item.slug) { url = url.replace('{slug}', props.item.slug); }

			return baseUrl + url + '?pv=' + user.value.id;
		}


		/////////////////////////////////
		// DELETE
		/////////////////////////////////

		const deleteModal = useTemplateRef('deleteModal');

		function confirmDelete() {

			deleteModal.value.open({
				title: t("Element löschen"),
				copy: t('delete.copy',{element: props.item.name}),
				alert: true,
				confirmLabel: t("Element löschen"),
				callback: callbackDelete
			});
		}

		function callbackDelete() {

			// local delete
			if(options.deleteLocal) {
				return emit('deleted', props.item.id);
			}

			apiPost(deleteRoute.value, {id: props.item.id}, () => emit('deleted', props.item.id));
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
				"delete.copy": "Möchtest du \"{element}\" wirklich löschen?",
			},
			"en": {
				"Element löschen": "Delete element",
				"delete.copy": "Do you really want to delete \"{element}\"?"
			}
		}
	</i18n>

