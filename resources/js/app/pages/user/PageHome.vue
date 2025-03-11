<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<project-navi>
			<btn label="Neues Projekt" to="project.edit" icon="btn-add" class="cta"/>
			<btn label="Downloads" to="page.detail" :params="{slug:'downloads'}" icon="btn-download"/>
		</project-navi>

		<section v-if="verified" class="content">
			<h3>{{ t('verified') }}</h3>
		</section>

		<!-- PROJECTS -->
		<section class="content">
			<p class="empty" v-if="!isLoading && !projects.length">
				{{ t("Noch keine Projekte erstellt.") }}
			</p>

			<div class="project-list-item header" v-else-if="projects.length">
				<p class="project-list-item-title">Name</p>
				<p class="project-list-item-slug">ID</p>
				<p class="project-list-item-date" v-if="isDesktop">Erstellt</p>
				<p class="project-list-item-date">Aktualisiert</p>
				<div class="project-list-item-buttons"></div>
			</div>

			<project-list-item v-for="p in projects" :project="p" :key="p.id" @deleted="loadProjects"/>
		</section>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref } from 'vue';
		import { usePage } from '@global/composables/usePage';
		import { useRoute } from 'vue-router';
		import { useRouter } from 'vue-router';
		import { useApi } from '@global/composables/useApi';
		import { useBreakpoints } from '@global/composables/useBreakpoints';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const route = useRoute();
		const router = useRouter();
		const { t } = usePage("Home");
		const { apiGet } = useApi();
		const { isDesktop } = useBreakpoints();


		/////////////////////////////////
		// VERIFIED
		/////////////////////////////////

		const verified = ref(route.query.verified);
		if(verified.value) { router.replace({ query: {verified:undefined} }); }


		/////////////////////////////////
		// PROJECT LIST
		/////////////////////////////////

		const projects = ref([]);
		const isLoading = ref(true);

		function loadProjects() {

			isLoading.value = true;
			projects.value = [];

			apiGet('api.project.list', data => {
				projects.value = data;
				isLoading.value = false;
			});
		}

		loadProjects();


	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
				"verified": "Dein Account wurde erfolgreich verifiziert.",
			},
			"en": {
				"verified": "Your account has been successfully verified.",
				"Projekt öffnen": "Open project",
				"Noch keine Projekte erstellt.": "No projects created yet.",
			}
		}
	</i18n>
