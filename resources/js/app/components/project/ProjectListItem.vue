<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="project-list-item">
			<router-link :to="link('project.edit',{slug:project.slug})" class="project-list-item-title">
				<p>{{ project.title }}</p>
			</router-link>
			<p class="project-list-item-slug">{{ project.slug }}</p>
			<p class="small project-list-item-date" v-if="isDesktop">{{ project.created_at }}</p>
			<p class="small project-list-item-date">{{ project.updated_at }}</p>
			<div class="project-list-item-buttons">
				<btn icon="btn-visualizer" target="_blank" to="visualizer" :params="{slug:project.slug}"/>
				<btn icon="btn-edit" to="project.edit" :params="{slug:project.slug}"/>
				<btn icon="btn-delete" @click="confirmDelete"/>
			</div>

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

		import { ref, useTemplateRef } from 'vue';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useBreakpoints } from '@global/composables/useBreakpoints';
		import { useApi } from '@global/composables/useApi';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = useLanguage();
		const { isDesktop } = useBreakpoints();
		const { apiPost } = useApi();

		const props = defineProps({
			project: {type: Object, required: true},
		});

		const emit = defineEmits(["deleted"]);


		/////////////////////////////////
		// DELETE
		/////////////////////////////////

		const deleteModal = useTemplateRef('deleteModal');

		function confirmDelete() {

			deleteModal.value.open({
				title: t("Projekt löschen"),
				copy: t("project.delete.copy"),
				alert: true,
				confirmLabel: t("Projekt löschen"),
				callback: () => deleteProject()
			});
		}

		function deleteProject() {

			apiPost("api.project.delete", {id:props.project.id}, data => {
				emit("deleted", props.project.id);
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
			},
			"en": {
			}
		}
	</i18n>
