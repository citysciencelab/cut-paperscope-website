/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { computed, ref } from "vue";
	import { useI18n } from 'vue-i18n'
	import { useRoute, useRouter } from 'vue-router';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPOSABLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


export const useLanguage = () => {


	/////////////////////////////////
	// INIT
	/////////////////////////////////

	const { t, locale }	= useI18n();
	const router		= useRouter();
	const route			= useRoute();


	/////////////////////////////////
	// LANGUAGE
	/////////////////////////////////

	const defaultLang		= window.config.fallback_locale;
	const langs				= window.config.available_locales;
	const activeLang		= computed(() => locale.value);
	const multiLangEnabled	= computed(() => langs.length > 1);


	function setLanguage(newLang: string) {

		// skip if already active language
		if(activeLang.value == newLang) { return; }

		// update locale for api calls
		window.config.active_locale = newLang;
		window.axios.defaults.headers.common['Accept-Language'] = newLang + '-' + newLang;

		// update i18n locale
		locale.value = newLang;

		// update current route with new language
		document.querySelector('html').setAttribute('lang', newLang);
		router.replace({ name: route.name, query: route.query, params:{ lang: newLang == window.config.fallback_locale ? '' : newLang } });
	};


	/////////////////////////////////
	// EXPORT
	/////////////////////////////////

	return {
		t,
		defaultLang, langs, activeLang, multiLangEnabled,
		setLanguage
	};



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */



};
