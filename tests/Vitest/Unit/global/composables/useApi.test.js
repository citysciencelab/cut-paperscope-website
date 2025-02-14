/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { setActivePinia, createPinia } from 'pinia'
	import { mockedRouter } from '@tests/Vitest/Helper/Mocks/useRouterMock';
	import { mockedLanguage } from '@tests/Vitest/Helper/Mocks/useLanguageMock';
	import { mockedI18n } from '@tests/Vitest/Helper/Mocks/useI18nMock';
	setActivePinia(createPinia())

	// test composable
	import { useApi } from '@global/composables/useApi'



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CACHE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('creating cache keys', async () => {

		// arrange
		const data = [
			{input: ['route', null], output: 'de_route'},
			{input: ['route', {prop:'propval'}], output: 'de_route_propval'},
		];

		// act
		const { createCacheKey } = useApi();

		// assert
		data.forEach(({input, output}) => {
			expect(createCacheKey(input[0],input[1])).toBe(output);
		});
	});


	test('correct apiGet with cache writing', async () => {

		// arrange
		var result = null;
		const { apiGet, readCache } = useApi();

		// assert: empty cache
		var cache = readCache('de_api.content');
		expect(cache).toBe(undefined);

		// act
		await apiGet('api.content', data => result = data, true);

		// assert
		expect(result.route).toContain('api/content');

		// assert: existing cache
		cache = readCache('de_api.content');
		expect(cache).not.toBe(undefined);
	});


	test('correct apiGet with cache reading', async () => {

		// arrange
		var result = null;
		const { apiGet, writeCache } = useApi();

		// arrange: fill cache
		writeCache('de_api.content', 'cache-value');

		// act
		await apiGet('api.content', data => result = data, true);

		// assert
		expect(result).toBe('cache-value');
	});


	test('correct apiGet with expired cache', async () => {

		// arrange
		var result = null;
		var now = Date.now();
		const { apiGet, writeCache } = useApi();

		// arrange: fill cache
		vi.setSystemTime(now - 500 * 1000)
		writeCache('de_api.content','cache-value');

		// act
		vi.setSystemTime(now)
		await apiGet('api.content', data => result = data, true);

		// assert
		expect(result).not.toBe('cache-value');
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	GET REQUEST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('correct apiGet', async () => {

		// act
		var result = null;
		const { apiGet } = useApi();
		await apiGet('api.content', data => result = data);

		// assert
		expect(result.route).toContain('api/content');
		expect(result.config.headers['X-Preview']).toBe(null);
	});


	test('correct apiGet with preview', async () => {

		// arrange
		mockedRouter.updateRoute({name:'index', query:{pv:1}, meta:{}});

		// act
		var result = null;
		const { apiGet } = useApi();
		await apiGet('api.content', data => result = data);

		// assert
		expect(result.route).toContain('api/content');
		expect(result.config.headers['X-Preview']).toBe(1);
	});


	test('correct apiGet with missing "api." in route', async () => {

		// act
		var result = null;
		const { apiGet } = useApi();
		await apiGet('content', data => result = data);

		// assert
		expect(result.route).toContain('api/content');
	});


	test('correct apiGetResponse', async () => {

		// act
		var result = null;
		const { apiGetResponse } = useApi();
		await apiGetResponse('api.content', r => result = r);

		// assert
		expect(result.data.data.route).toContain('api/content');
	});


	test('correct apiGetSlug', async () => {

		// arrange
		mockedRouter.updateRoute({name:'index', params:{slug:'test-slug'}});

		// act
		var result = null;
		const { apiGetSlug } = useApi();
		await apiGetSlug('api.content', r => result = r);

		// assert
		expect(result.route).toContain('?slug=test-slug');
	});


	test('invalid route apiGet', async () => {

		// act
		var result = null;
		const { apiGet } = useApi();

		try {
			await apiGet('api.invalid', data => result = data);
		}
		catch(e) {
			result = e;
		}

		// assert
		expect(result.message).toBe("Ziggy error: route 'api.invalid' is not in the route list.");
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	POST REQUEST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('correct apiPost', async () => {

		// act
		var result = null;
		const { apiPost } = useApi();
		await apiPost('api.content', {id:1}, data => result = data);

		// assert
		expect(result.route).toContain('api/content');
		expect(result.params.id).toBe(1);
	});


	test('correct apiPostResponse', async () => {

		// act
		var result = null;
		const { apiPostResponse } = useApi();
		await apiPostResponse('api.content', {id:1}, r => result = r);

		// assert
		expect(result.data.data.route).toContain('api/content');
		expect(result.data.data.params.id).toBe(1);
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	RESPONSE ERROR
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('apiGet with error', async () => {

		// arrange: set context
		var context = window.config.webContext;
		window.config.webContext = 'backend';

		// arrange: mock axios error
		const spyAxios = vi.spyOn(window.axios, 'get')
		spyAxios.mockImplementationOnce(() => Promise.reject({ response: { status: 401 } }));

		// arrange: spy redirect
		const spyRedirect = vi.spyOn(window.location, 'reload');

		// act
		var result = null;
		const { apiGet } = useApi();
		await apiGet('api.content', data => result = data).catch(e => result = e);

		// assert
		expect(spyRedirect).toHaveBeenCalledTimes(1);
		expect(result.response.status).toBe(401);

		// reset
		window.config.webContext = context;
	});


	test('reload page if 419 error', async () => {

		// arrange: mock axios error
		const spyAxios = vi.spyOn(window.axios, 'get')
		spyAxios.mockImplementationOnce(() => Promise.reject({ response: { status: 419 } }));

		// arrange: spy redirect
		const spyRedirect = vi.spyOn(window.location, 'reload');

		// act
		const { apiGet } = useApi();
		await apiGet('api.content', data => result = data).catch(e => e)

		// assert
		expect(spyRedirect).toHaveBeenCalledTimes(1);
	});


	test('reload page if 503 error', async () => {

		// arrange: mock axios error
		const spyAxios = vi.spyOn(window.axios, 'get')
		spyAxios.mockImplementationOnce(() => Promise.reject({ response: { status: 503 } }));

		// arrange: spy redirect
		const spyRedirect = vi.spyOn(window.location, 'reload');

		// act
		const { apiGet } = useApi();
		await apiGet('api.content', data => result = data).catch(e => e);

		// assert
		expect(spyRedirect).toHaveBeenCalledTimes(1);
	});



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


