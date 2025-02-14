/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { useRouter, useRoute } from 'vue-router'

	// app
	import { ApiResponse } from '@global/composables/types';
	import { useUserStore } from '@global/stores/UserStore';
	import { useConfig } from '@global/composables/useConfig';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPOSABLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


export const useAuth = () => {

	const store = useUserStore();
	const router = useRouter();
	const route = useRoute();
	const { baseUrl, basePath, webContext } = useConfig();


	/////////////////////////////////
	// CONFIG
	/////////////////////////////////

	const config = {
		baseURL: baseUrl,
	};


	/////////////////////////////////
	// CSRF
	/////////////////////////////////

	/**
	 * Every auth request needs a valid csrf cookie
	 */

	async function csrf(): Promise<void> {

		return await window.axios.get('auth/csrf-cookie', config);
	}


	/////////////////////////////////
	// LOGIN
	/////////////////////////////////

	async function login(data: any): Promise<ApiResponse> {

		await csrf();

		return await window.axios.post('auth/login', data, config).then((response:ApiResponse) => {

			if(response.data.status == 'success') {
				store.setUser(response.data.data);
				redirectToHome();
			}

			return response;
		})
		.catch(authError);
	}


	function redirectToHome() {

		// query includes redirect after failed request on protected route
		const redirect: string = route.query?.redirect as string;

		// reload if stripe checkout url
		if(redirect?.includes('stripe/checkout')) {
			window.location.href = baseUrl + redirect.replace(basePath, '');
			return;
		}

		const home = webContext == 'app' ? 'home' : 'backend.index';

		// redirect only to internal routes
		router.push(redirect && !redirect.startsWith('http') ? {path:redirect} : {name:home});
	}


	/////////////////////////////////
	// LOGOUT
	/////////////////////////////////

	async function logout(): Promise<ApiResponse> {

		// app: reset local user and route
		if(webContext == 'app') {
			store.setUser(null);
			window.config.app_user = null;
			router.push({name:'login'});
		}

		// delete user session
		return await window.axios.post('auth/logout',{}, config).catch(authError).finally(() => {
			if(webContext == 'backend') {
				store.setUser(null);
				window.config.app_user = null;
				router.push({name:'login'});
			 }
		})
	}


	/////////////////////////////////
	// REGISTER
	/////////////////////////////////

	async function register(data: any): Promise<ApiResponse> {

		await csrf();

		return await window.axios.post('auth/register',data,config).then((response: ApiResponse) => {

			if(response.data.status == 'success') {
				store.setUser(response.data.data);
				redirectToHome();
			}

			return response;
		})
		.catch(authError);
	}


	async function resendVerify(data: any): Promise<ApiResponse> {

		return await window.axios.post('auth/email/verification-notification',data,config);
  	}


	/////////////////////////////////
	// USER
	/////////////////////////////////

	async function getUser() {

		return await window.axios.get('api/user',config).then((response: ApiResponse) => {

			if(response.data?.status == 'success') {
				store.setUser(response.data.data);
				return response.data.data;
			}

			return response;
		})
		.catch(authError);
	}


	async function deleteUser(userId: string) {

		const data = { id:userId };

		return await window.axios.post('api/user/delete',data,config).then((response: ApiResponse) => {

			if(response.data?.status == 'success') {
				if(webContext == 'app') {
					store.setUser(null);
					window.config.app_user = null;
					router.push({name: 'index'});
				}
				else {
					router.push({name:'backend.user'});
				}
			}

			return response;
		})
		.catch(authError);
	}


	/////////////////////////////////
	// PASSWORD
	/////////////////////////////////

	async function forgotPassword(data: any): Promise<ApiResponse> {

		await csrf();
		return await window.axios.post('auth/forgot-password',data,config);
	}

	async function resetPassword(data: any) {

		await csrf();
		return await window.axios.post('auth/reset-password',data,config);
	}

	async function updatePassword(data: any) {

		await csrf();
		return await window.axios.post('auth/user/password',data,config);
	}


	/////////////////////////////////
	// ERROR
	/////////////////////////////////

	function authError(error: Error): Promise<Error> {

		return Promise.reject(error);
	};


	/////////////////////////////////
	// EXPORT
	/////////////////////////////////

	return {
		login, logout, redirectToHome,
		register, resendVerify,
		getUser, deleteUser,
		forgotPassword, resetPassword, updatePassword
	};



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


};
