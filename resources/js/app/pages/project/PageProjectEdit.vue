<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<!-- NAVI -->
		<project-navi>
			<form-errors :errors="errors"/>
			<btn :label="t('Projekt Info')" icon="btn-add" data-tab="info" @click="openTab"/>
			<btn :label="t('Paper erstellen')" icon="btn-add" data-tab="paper" @click="openTab"/>
			<btn :label="t('Objekte definieren')" icon="btn-objects" data-tab="objects" @click="openTab"/>
			<btn :label="t('Mapping bearbeiten')" icon="btn-edit" data-tab="mapping" @click="openTab" :disabled="!form.scene"/>
			<btn :label="t('Gäste verwalten')" icon="btn-guests" data-tab="guests" @click="openTab" disabled/>
			<btn :label="t('Visualizer öffnen')" icon="btn-visualizer" to="visualizer" target="_blank" :params="{slug:form.slug}" v-if="form.id"/>
			<btn :label="t('Beamer Projektion')" icon="btn-visualizer" to="beamer" target="_blank" :params="{slug:form.slug}" v-if="form.id"/>
			<btn :label="t('Projekt speichern')" ref="submitBtn" icon="btn-save" class="cta project-navi-save" @click="submit" :disabled="!form.start_longitude" blocking/>
			<btn :label="t('Projekt löschen')" icon="btn-delete" class="delete" v-if="form.id" @click="confirmDelete"/>
		</project-navi>

		<!-- TABS -->
		<section class="content" v-if="!isLoading">
			<project-info class="project-edit-tab"/>
			<project-paper class="project-edit-tab" ref="project-paper" @vue:mounted="openFirstTab"/>
			<project-objects class="project-edit-tab"/>
			<project-mapping class="project-edit-tab"/>
			<project-guests class="project-edit-tab"/>
		</section>

		<!-- LOADING -->
		<section class="content" v-else style="height:100px">
			<loading-spinner/>
		</section>

		<!-- DELETE -->
		<popup-modal ref="deleteModal"/>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, onMounted, provide, nextTick, useTemplateRef } from 'vue';
		import { useRoute, useRouter } from 'vue-router';
		import { usePage } from '@global/composables/usePage';
		import { useForm } from '@global/composables/useForm';
		import { useApi } from '@global/composables/useApi';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = usePage("Projekt");
		const router = useRouter();
		const route = useRoute();
		const { form, errors, submitBtn, submitForm } = useForm();
		const { apiGetSlug, apiPost } = useApi();

		provide('form', form);


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		const isLoading = ref(true);

		if (route.params.slug) {
			apiGetSlug("api.project", async (data) => {
				form.value = data
				isLoading.value = false;
				await nextTick();
			});
		}
		else {
			isLoading.value = false;
		}

		function submit() {

			submitForm("api.project.save", data => {
				form.value = data;
				//router.push({name:'home'});
			});
		}


		/////////////////////////////////
		// TABS
		/////////////////////////////////

		const activeTab = ref(null);
		const paperComp = useTemplateRef('project-paper');

		function openTab(value, target) {

			// active style
			u('.project-navi .btn').removeClass('active');
			u(target).addClass('active');

			// get new tab
			const tab = u(target).addClass('active').data('tab');
			if(activeTab.value == tab) { return; }
			activeTab.value = tab;

			var anim = gsap.timeline();
			anim.to('.project-edit-tab', {opacity:0, duration:0.2, display:'none'});
			anim.to('.project-' + tab, {opacity:1.0, duration:0.4, display:'block'});

			// callbacks
			anim.call(() => {
				if(tab == 'paper') { paperComp.value.focusRectangle(); }
			}, null, 0.25);

			// add to url as query
			router.push({query:{tab:tab}});
		}

		function openFirstTab() {

			const tab = route.query.tab || 'info';
			const target = u('.project-navi').find(`[data-tab="${tab}"]`).first();
			if(target) { target.click(); }
		}


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

			isLoading.value = true;

			apiPost("api.project.delete", {id:form.value.id}, data => {
				router.push({name:'home'});
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
				"Projekt Info": "Project info",
				"Paper erstellen": "Create Paper",
				"Objekte definieren": "Define Objects",
				"Mapping bearbeiten": "Edit Mapping",
				"Gäste verwalten": "Manage Guests",
				"Visualizer öffnen": "Open Visualizer",
				"Beamer Projektion": "Beamer Projection",
				"Projekt speichern": "Save Project",
				"Projekt löschen": "Delete Project",
			}
		}
	</i18n>
