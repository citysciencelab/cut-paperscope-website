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
	import PageResetPassword from '@global/pages/auth/PageResetPassword.vue';
	import Btn from '@global/components/content/Btn.vue';



/*//////////////////////////////////////////////////////////////////////x/////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPONENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('renders page', async () => {

		// act
		const wrapper = mount(PageResetPassword, {
			shallow: true,
			global: {
				// stub async components
				stubs: {
					'FormErrors': true,
					'InputText': true,
				}
			}
		});

		// assert
		wrapper.get('#password-reset');
		wrapper.get('.btn-reset');
	});



/*//////////////////////////////////////////////////////////////////////x/////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FORM
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('submit form', async () => {

		// arrange router
		mockedRouter.updateRoute({
			'name':'password.reset',
			'path':'/password/reset',
			'query': {
				'token': 'token',
				'email': 'email',
			},
		});

		// arrange component
		const wrapper = mount(PageResetPassword, {
			shallow: true,
			global: {
				// stub async components
				stubs: {
					'FormErrors': true,
					'InputText': true,
					'Btn': Btn,
				}
			}
		});

		// act
		await wrapper.get('.btn-reset').trigger('click');
		await flushPromises();

		// assert
		expect(mockedRouter.getRoute().name).toBe('password.reset');
		expect(wrapper.get('h4').text()).toBe('success');
	});


	test('submit form with errors', async () => {

		// arrange router
		mockedRouter.updateRoute({
			'name':'password.reset',
			'path':'/password/reset',
			'query': {
				'token': 'token',
				'email': 'email',
			},
		});

		// arrange axios
		vi.spyOn(window.axios, 'post').mockRejectedValue({
			response: {
				data: {
					errors: {
						email: ['The email field is required.'],
					}
				}
			}
		});

		// arrange component
		const wrapper = mount(PageResetPassword, {
			shallow: true,
			global: {
				// stub async components
				stubs: {
					'FormErrors': true,
					'InputText': true,
					'Btn': Btn,
				}
			}
		});

		// act
		await wrapper.get('.btn-reset').trigger('click');
		await flushPromises();

		// assert
		expect(mockedRouter.getRoute().name).toBe('password.reset');
		expect(mockedForm.errors.value.email).toBeDefined();
	});



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


