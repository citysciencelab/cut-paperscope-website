/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { mount, flushPromises } from '@vue/test-utils';
	import { mockedRouter } from '@tests/Vitest/Helper/Mocks/useRouterMock';
	import { mockedPage } from '@tests/Vitest/Helper/Mocks/usePageMock';
	import { mockedForm } from '@tests/Vitest/Helper/Mocks/useFormMock';

	// test component
	import PageRegister from '@global/pages/auth/PageRegister.vue';
	import Btn from '@global/components/content/Btn.vue';



/*//////////////////////////////////////////////////////////////////////x/////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPONENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('renders page', async () => {

		// act
		const wrapper = mount(PageRegister, {
			shallow: true,
			global: {
				// stub async components
				stubs: {
					'FormErrors': true,
					'InputText': true,
					'InputSelect': true,
					'InputCheckbox': true,
					'SsoButtons': true,
				}
			}
		});

		// assert
		wrapper.get('#email-register');
		wrapper.get('#password-register');
		wrapper.get('.btn-register');
	});



/*//////////////////////////////////////////////////////////////////////x/////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FORM
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('submit form', async () => {

		// arrange router
		mockedRouter.updateRoute({'name':'register', 'path':'/register'});

		// arrange component
		const wrapper = mount(PageRegister, {
			shallow: true,
			global: {
				// stub async components
				stubs: {
					'FormErrors': true,
					'InputText': true,
					'InputSelect': true,
					'InputCheckbox': true,
					'SsoButtons': true,
					'Btn': Btn,
				}
			}
		});

		// act
		await wrapper.get('.btn-register').trigger('click');

		// assert
		expect(mockedRouter.getRoute().name).toBe('home');
	});


	test('submit form with errors', async () => {

		// arrange router
		mockedRouter.updateRoute({'name':'register', 'path':'/register'});

		// arrange axios
		vi.spyOn(window.axios, 'post').mockRejectedValue({
			response: {
				data: {
					errors: {
						email: ['The email field is required.'],
						password: ['The password field is required.'],
					}
				}
			}
		});

		// arrange component
		const wrapper = mount(PageRegister, {
			shallow: true,
			global: {
				// stub async components
				stubs: {
					'FormErrors': true,
					'InputText': true,
					'InputSelect': true,
					'InputCheckbox': true,
					'SsoButtons': true,
					'Btn': Btn,
				}
			}
		});

		// act
		await wrapper.get('.btn-register').trigger('click');
		await flushPromises();

		// assert
		expect(mockedRouter.getRoute().name).toBe('register');
		expect(mockedForm.errors.value.email).toBeDefined();
	});



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


