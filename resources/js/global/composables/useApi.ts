/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { useRoute } from 'vue-router'
	import { route as ZiggyRoute } from '@resources/js/global/routes/Routes.js'

	// app
	import { ApiResponse, ApiError } from '@global/composables/types';
	import { useGlobalStore } from '@global/stores/GlobalStore.js'
	import { useLanguage } from '@global/composables/useLanguage';
	import { useConfig } from '@global/composables/useConfig';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPOSABLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


export const useApi = () => {

	const route = useRoute();
	const store = useGlobalStore();
	const { activeLang } = useLanguage();
	const { webContext } = useConfig();


	/////////////////////////////////
	// ROUTE
	/////////////////////////////////

	// axios request config
	const config = {
		headers: { 'X-Preview': route.query?.pv ?? null },
	};


	/**
	 * Create an api route url from a laravel route name (created with ziggy.js)
	 */

	function createRoute(name: string, params?: any): string {

		// add api prefix if missing
		if(!name.startsWith('api.')) { name = 'api.'+name; }

		return ZiggyRoute(name, params);
	}


	/////////////////////////////////
	// CACHE
	/////////////////////////////////

	/**
	 * Read an api response from the store cache if it's not older than 5 minutes
	 */

	function readCache(key: string): any {

		const timestamp = Date.now();
		const result = store.apiCache[key];

		// return only < 5 min
		return result && timestamp - result.timestamp < 300 * 1000 ? result.value : undefined;
	}


	/**
	 * Write an api response to the store cache
	 */

	function writeCache(key: string, value: any): any {

		store.setApiCache(key, value);
		return value;
	}


	/**
	 * Create a cache key from a route name and url params.
	 * Key is unique for each language and optional params.
	 */

	function createCacheKey(routeName: string, params: any): string {

		let cacheKey = activeLang.value + '_' + routeName;

		// add url params to cache key
		if(params) {
			Object.values(params).forEach(param => cacheKey += '_'+param);
		}

		return cacheKey;
	}


	/////////////////////////////////
	// GET REQUEST
	/////////////////////////////////

	/**
	 * Fetch an api response with HTTP Get. Second argument can be params object or a callback function.
	 * Use this function if you need to access the full axios response.
	 */

	async function apiGetResponse(routeName: string, params?: any, callback?: Function): Promise<ApiResponse|ApiError> {

		// modify params if second argument is callback
		if(typeof params == 'function') { callback = params; params = {}; }

		return window.axios.get(createRoute(routeName,params),config)
			.then((r: ApiResponse) => { callback?.(r); return r; })
			.catch(apiError);
	}


	/**
	 * Fetch an api response with HTTP Get. Second argument can be params object or a callback function.
	 * Instead of the full response, only the "data" property will be passed to the callback function.
	 * With optional parameter "useCache" you can enable response caching.
	 */

	async function apiGet(routeName: string, params?: any, callback?: Function|boolean, useCache = false): Promise<ApiResponse|ApiError> {

		// modify params if second argument is callback
		if(typeof params == 'function') {
			useCache = callback as boolean;
			callback = params;
			params = {};
		}

		if(useCache) {

			var cacheKey = createCacheKey(routeName,params);
			const cached = readCache(cacheKey);

			if(cached) {
				(callback as Function)?.(cached);
				const response: ApiResponse = {status: 200, data: {status: 'success', data: cached}};
				return Promise.resolve(response);
			}
		}

		return window.axios.get(createRoute(routeName,params),config)
			.then((r: ApiResponse) => {
				if(useCache) { writeCache(cacheKey,r.data?.data); };
				(callback as Function)?.(r.data?.data, getPaginator(r));
				return r;
			})
			.catch(apiError);
	}


	/**
	 * Fetch an api response with HTTP Get and use the current route slug as a parameter.
	 */

	async function apiGetSlug(routeName: string, callback?: Function, useCache: boolean = false): Promise<ApiResponse|ApiError> {

		const slug = route.params.slug;
		return apiGet(routeName, {slug}, callback, useCache);
	}


	/////////////////////////////////
	// POST REQUEST
	/////////////////////////////////

	/**
	 * Fetch an api response with HTTP Post.
	 * Use this function if you need to access the full axios response.
	 */

	async function apiPostResponse(routeName: string, data: any, callback?: Function): Promise<ApiResponse|ApiError> {

		return window.axios.post(createRoute(routeName),data,config)
			.then((r: ApiResponse) => { callback?.(r); return r; })
			.catch(apiError);
	}


	/**
	 * Fetch an api response with HTTP Post.
	 * Instead of the full response, only the "data" property will be passed to the callback function.
	 */

	async function apiPost(routeName: string, data: any, callback?: Function): Promise<ApiResponse|ApiError> {

		return window.axios.post(createRoute(routeName),data,config)
			.then((r: ApiResponse) => { callback?.(r.data?.data, getPaginator(r)); return r; })
			.catch(apiError);
	}


	/////////////////////////////////
	// PAGINATOR
	/////////////////////////////////

	/**
	 * If the response contains a paginator object, apiGet() and apiPost() will return the paginator as second callback parameter.
	 */

	function getPaginator(response: ApiResponse): {currentPage: number, pages: number} | null {

		if(!response.data?.paginator) { return null; }

		return {
			currentPage: response.data.currentPage,
			pages: response.data.pages,
		};
	}


	/////////////////////////////////
	// ERROR
	/////////////////////////////////

	function apiError(error: any): Promise<ApiError> {

		console.error(error);

		// unauthorized
		if(webContext == 'backend' && error.response?.status == 401) {
			window.location.reload();
		}
		// csrf token mismatch
		else if(error.response?.status == 419) {
			window.location.reload();
		}
		// maintenance mode
		else if(error.response?.status == 503) {
			window.location.reload();
		}

		const apiError: ApiError = new ApiError(
			error.response?.data?.message,
			error.response
		);

		return Promise.reject(apiError);
	};


	/////////////////////////////////
	// EXPORT
	/////////////////////////////////

	return {
		createRoute,
		readCache, writeCache, createCacheKey,
		apiGetResponse, apiGet, apiGetSlug,
		apiPostResponse, apiPost,
	};



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


};
