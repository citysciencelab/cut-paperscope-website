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
				<!-- <btn label="Login" @click="openLoginPopup"/> -->
				<div v-if="isLoading" class="col-100" style="height:100px"><loading-spinner/></div>
				<simulation-process v-else class="col-50" v-for="process in umpSimulations" :process="process" :project="project"/>

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
		import { useApi } from '@global/composables/useApi';
		import { useUmp } from '@resources/js/app/composables/useUmp';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { apiGetResponse } = useApi();
		const { getProcesses } = useUmp();

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

		getProcesses().then(r => {

			umpSimulations.value = r;
			isLoading.value = false;

		}).catch(e => console.error(e));

		function openLoginPopup() {

			// const url = new URL('https://auth.comodeling.city/realms/UrbanModelPlatform/protocol/openid-connect/auth');
			// url.searchParams.set('response_type', 'code');
			// url.searchParams.set('client_id', 'ump-client');
			// url.searchParams.set('state', 'b1d5c0f0ed394bfe3891bb503720e4ea6fcb7b0b991d94eab25d96efb1d1a720251fd62c469c9d28cfddb9');
			// url.searchParams.set('scope', 'openid');
			// url.searchParams.set('redirect_uri', 'https://scenarioexplorer.comodeling.city/portal/simulation/');
			// url.searchParams.set('code_challenge', 'pFtWwqalsUNewWAi-JaPNifnsMEPIlJFw8DgDsZ5Euc');
			// url.searchParams.set('code_challenge_method', 'S256');

			// window.open(url.toString(), '_blank', 'width=800,height=600');
		}


	</script>


