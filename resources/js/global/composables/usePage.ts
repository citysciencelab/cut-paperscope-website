/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { ref, computed, onMounted, watch } from "vue";
	import { useRoute, useRouter } from 'vue-router'
	import { useHead } from '@unhead/vue'
	import { useI18n } from 'vue-i18n'

	// app
	import '@global/composables/types';
	import { useApi } from '@global/composables/useApi';
	import { useLazyload } from '@global/composables/useLazyload';
	import { useUser } from '@global/composables/useUser';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPOSABLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


export const usePage = (title?: string) => {

	/////////////////////////////////
	// INIT
	/////////////////////////////////

	const route			= useRoute();
	const router		= useRouter();

	const { t,locale }	= useI18n();
	const { apiGet }	= useApi();
	const { user }		= useUser();

	const { updateLazyload } = useLazyload();


	/////////////////////////////////
	// META PROPS
	/////////////////////////////////

	const metaPrefix 	= ref(window.config.app_name);
	const metaTitle		= ref(title);
	const pageTitle		= computed(() => metaPrefix.value + (metaTitle.value?' | '+t(metaTitle.value):'') );

	const head = useHead({
		title: () => pageTitle.value,
	});


	/////////////////////////////////
	// PAGE DETAIL
	/////////////////////////////////

	const slug		= route.params?.slug;
	const content	= ref({fragments:[]});

	/**
	 * load content of laravel page model from api. Data will be cached for 5 minutes.
	 */

	function loadPage() {

		apiGet('page', {slug}, (data: any) => {

			content.value = data;
			metaTitle.value = data.meta_title;

			updateLazyload();
		}, true)
		.catch(loadPageError);
	}

	function loadPageError(e: any) {

		if(e?.response?.status == 404) {
			router.replace({name:'error.404'});
		}
	}


	// load page data automatically if PageDetail.vue
	if(route.name == 'page.detail') {

		watch(()=>route.params.slug, loadPage, {immediate:true});
		watch(locale, loadPage);
	}


	/////////////////////////////////
	// AUTH
	/////////////////////////////////

	/**
	 * Redirect to home if user is logged in
	 */

	function redirectIfUser(): boolean {

		if(user.value) {
			router.push({name:'home'});
			return true;
		}

		return false;
	};


	/////////////////////////////////
	// FORM
	/////////////////////////////////

	/**
	 * Automatically prevent zooming to form inputs on mobile devices.
	 */

	function preventFormZoom() {

		const hasForm = window.u('.input-text, .data-list-navi-search').nodes.length > 0;

		// @ts-ignore
		head.patch({
			meta: [
				{
					'name': 'viewport',
					'content': 'width=device-width, initial-scale=1' + (hasForm?', user-scalable=no':'')
				}
			]
		})
	}


	/////////////////////////////////
	// EVENTS
	/////////////////////////////////

	onMounted(()=> {

		updateLazyload();

		// wait for page transition and loading time of async components
		setTimeout(preventFormZoom,750);
	});


	////////////////////////////////
	// EXPORT
	/////////////////////////////////

	return {
		t,
		metaTitle, pageTitle,
		slug, content,
		redirectIfUser,
		updateLazyload
	};



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */



};
