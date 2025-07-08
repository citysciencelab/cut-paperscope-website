/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	import { ref, watch } from 'vue';
	import { useI18n } from 'vue-i18n'
	import { defineStore } from 'pinia'
	import { useConfig } from '@global/composables/useConfig';
	import { useApi } from '@global/composables/useApi';
	import { useUmp } from '@app/composables/useUmp';
	import { useRoute } from 'vue-router';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	STORE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	export const useContentStore = defineStore('content',() => {

		const { locale } = useI18n();
		const { baseUrl } = useConfig();
		const { apiGet } = useApi();
		const route = useRoute();
		const { umpAuthUrl, umpRequest } = useUmp();


		/////////////////////////////////
		// DATA
		/////////////////////////////////

		const settings = ref([]);
		const pages = ref(window.pages);


		/////////////////////////////////
		// LOAD
		/////////////////////////////////

		function load() {

			// set all props from api as store props
			apiGet('content', data => {
				settings.value = data.settings;
				pages.value = data.pages;
			})
			.catch(e => console.log(e));
		}

		watch(locale, load, {immediate: true});


		/////////////////////////////////
		// URBAN MODEL PLATFORM AUTH
		/////////////////////////////////

		const umpBearerToken = ref(localStorage.getItem('ump_bearer_token') || null);

		// complete ump auth flow
		if(route.query.code) {

			// reconstruct original redirect_uri from the current URL
            const redirectUri = new URL(location.href);
            redirectUri.searchParams.delete('code');
            redirectUri.searchParams.delete('state');
            redirectUri.searchParams.delete('session_state');
            redirectUri.searchParams.delete('iss');

			// token request params
			const params = new URLSearchParams();
			params.append('grant_type', 'authorization_code');
			params.append('client_id', 'paperscope-client');
			params.append('code', route.query.code);
			params.append('redirect_uri', redirectUri.toString());

			const headers = {
				'Content-Type': 'application/x-www-form-urlencoded',
			};

			umpRequest.post(umpAuthUrl+'token', params, {headers}).then(response => {

				// complete auth
				localStorage.setItem('ump_bearer_token', response.data.access_token);
				umpBearerToken.value = response.data.access_token;
			})
			.catch(error => {
				console.error('Login failed:', error);
			});
		}


		/////////////////////////////////
		// EXPORT
		/////////////////////////////////

		return {
			settings, pages,
			load,
			umpBearerToken
		};
	})
