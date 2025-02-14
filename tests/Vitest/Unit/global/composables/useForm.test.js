/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { setActivePinia, createPinia } from 'pinia'
	import { mockedRouter } from '@tests/Vitest/Helper/Mocks/useRouterMock';
	import { mockedI18n } from '@tests/Vitest/Helper/Mocks/useI18nMock';
	setActivePinia(createPinia())

	// test composable
	import { useForm } from '@global/composables/useForm'



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SUBMIT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('correct submit', async () => {

		// arrange
		const {form, submitBtn, submitForm} = useForm();
		form.value.test = 'test-value';

		// arrange: mock button
		const mockLoadingState = vi.fn();
		submitBtn.value = { setLoading: mockLoadingState };

		// act
		var response = null;
		await submitForm('user.save',r => response = r);

		// assert
		expect(response.params.test).toBe('test-value');
		expect(response.route).toContain('api/user/save');
		expect(mockLoadingState).toHaveBeenCalledTimes(1);
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	ERROR
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('error on submit with error data', async () => {

		// arrange
		vi.useFakeTimers();
		const {form, errors, submitForm} = useForm();
		form.value.test = 'test-value';

		// arrange: mock axios error
		const spyAxios = vi.spyOn(window.axios, 'post')
		spyAxios.mockImplementationOnce(() => Promise.reject({
			response: {
				status: 401,
				data: { errors: [{test:'error'}] }
			}
		}));

		// arrange: spies
		const spyScroll = vi.spyOn(window, 'scrollTo');
		const spyUmbrella = vi.spyOn(window, 'u');
		spyUmbrella.mockImplementation(() => ({
			first: () => new HTMLDivElement(),
			outerHeight: () => 100,
		}));

		// act
		await submitForm('user.save',r => r);
		vi.runAllTimers();

		// assert
		expect(spyScroll).toHaveBeenCalledTimes(1);
		expect(errors.value[0].test).toBe('error');

		// restore
		vi.useRealTimers();
		spyUmbrella.mockRestore();
	});


	test('error on submit without dom element for error messages', async () => {

		// arrange
		vi.useFakeTimers();
		const {form, errors, submitForm} = useForm();
		form.value.test = 'test-value';

		// arrange: mock axios error
		const spyAxios = vi.spyOn(window.axios, 'post')
		spyAxios.mockImplementationOnce(() => Promise.reject({
			response: {
				status: 401,
				data: { errors: [{test:'error'}] }
			}
		}));

		// arrange: spies
		const spyScroll = vi.spyOn(window, 'scrollTo');
		const spyUmbrella = vi.spyOn(window, 'u');
		spyUmbrella.mockImplementationOnce(() => ({
			first: () => null,
		}));

		// act
		await submitForm('user.save',r => r);
		vi.runAllTimers();

		// assert
		expect(spyScroll).toHaveBeenCalledTimes(0);
		expect(errors.value[0].test).toBe('error');

		// restore
		vi.useRealTimers();
	});


	test('unknown error on submit', async () => {

		// arrange
		const {form, errors, submitForm} = useForm();
		form.value.test = 'test-value';

		// arrange: mock axios error
		const spyAxios = vi.spyOn(window.axios, 'post')
		spyAxios.mockImplementationOnce(() => Promise.reject({
			response: { status: 401 }
		}));

		// arrange: spy console
		const spyConsole = vi.spyOn(console, 'log');

		// act
		await submitForm('user.save',r => r);

		// assert
		expect(spyConsole).toHaveBeenCalledTimes(1);
		expect(errors.value).toEqual(null);
	});

