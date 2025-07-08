<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="simulation">

			<div class="cols">

				<h3>PaperScope</h3>
				<simulation-process class="col-50" v-for="process in psSimulations" :process="process" :project="project"/>

				<h3>Urban Model Platform</h3>

				<!-- LOGIN -->
				<div v-if="!umpBearerToken">
					<p>{{ t("login.copy") }}</p>
					<btn label="Login" class="small" @click="loginUmp"/>
				</div>

				<!-- MODELS -->
				<div v-else-if="isLoading" class="col-100" style="height:100px"><loading-spinner/></div>
				<simulation-process v-else class="col-50" v-for="process in umpSimulations" :process="process" :project="project"/>

				<div class="form-row-buttons" v-if="umpBearerToken" style="text-align:right;">
					<btn label="Logout UMP" class="small secondary" style="margin:0;" @click="logout"/>
				</div>

			</div>
		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref } from 'vue';
		import { storeToRefs } from 'pinia'

		import { useConfig } from '@global/composables/useConfig';
		import { useApi } from '@global/composables/useApi';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useUmp } from '@resources/js/app/composables/useUmp';
		import { useContentStore } from '@app/stores/ContentStore';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { baseUrl } = useConfig();
		const { apiGetResponse } = useApi();
		const { t } = useLanguage();
		const { umpAuthUrl, getProcesses } = useUmp();
		const { umpBearerToken } = storeToRefs(useContentStore());

		const props = defineProps({
			project: {type: Object, required: true},
		});


		/////////////////////////////////
		// PAPERSCOPE SIMULATIONS
		/////////////////////////////////

		const psSimulations = ref([]);

		apiGetResponse('api.ogc.process.list', data => psSimulations.value = data.data);


		/////////////////////////////////
		// URBAN MODEL PLATFORM
		/////////////////////////////////

		const isLoading = ref(true);
		const umpSimulations = ref([]);

		function load() {

			if(umpBearerToken.value) {

				getProcesses().then(r => {
					umpSimulations.value = r;
					isLoading.value = false;

				}).catch(e => console.error(e));
			}
			else {
				isLoading.value = false;
			}
		}

		load();


		/////////////////////////////////
		// UMP AUTH
		/////////////////////////////////

		function loginUmp() {

			const url = new URL(umpAuthUrl+'auth');
			url.searchParams.set('response_type', 'code');
			url.searchParams.set('client_id', 'paperscope-client');
			url.searchParams.set('scope', 'openid');
			url.searchParams.set('redirect_uri', location.href);
			location.href = url.toString();
		}

		function logout() {

			localStorage.removeItem('ump_bearer_token');
			umpBearerToken.value = null;
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
				"login.copy": "Um die Modelle der Urban Model Platform zu nutzen, melden Sie sich bitte an.",
			},
			"en": {
				"login.copy": "To use the models of the Urban Model Platform, please log in.",
			}
		}
	</i18n>

