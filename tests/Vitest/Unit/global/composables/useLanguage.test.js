/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { nextTick } from 'vue';
	import { mockedI18n } from '@tests/Vitest/Helper/Mocks/useI18nMock';
	import { mockedRouter } from '@tests/Vitest/Helper/Mocks/useRouterMock';

	// test composable
	import { useLanguage } from '@global/composables/useLanguage'



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	PROPS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('correct props', () => {

		// act
		const { defaultLang, langs, activeLang, multiLangEnabled } = useLanguage();

		// assert consts
		expect(defaultLang).toBe('de');
		expect(langs).toStrictEqual(window.config.available_locales);

		// assert computed props
		expect(activeLang.value).toBe('de');
		expect(multiLangEnabled.value).toBe(true);
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	LANGUAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('correct setLanguage', async () => {

		// arrange
		const windowLang = window.config.fallback_locale;
		const axios = window.axios;
		window.axios.defaults = {
			headers: {
				common: { 'Accept-Language': windowLang+'-'+windowLang, }
			}
		}

		// act
		const { activeLang, setLanguage } = useLanguage();
		setLanguage('en');
		await nextTick();

		// assert: new lang
		expect(activeLang.value).toBe('en');
		expect(window.axios.defaults.headers.common['Accept-Language']).toBe('en-en');

		// restore
		window.config.active_locale = windowLang;
		window.axios = axios;
	});


	test('setLanguage with same language', async () => {

		// act
		const { activeLang, setLanguage } = useLanguage();
		setLanguage("de");
		await nextTick();

		// assert: new lang
		expect(activeLang.value).toBe("de");
	});

