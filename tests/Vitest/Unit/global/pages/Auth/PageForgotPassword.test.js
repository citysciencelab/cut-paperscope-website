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

	// app
	import Btn from '@global/components/content/Btn.vue';

	// test component
	import PageForgotPassword from '@global/pages/auth/PageForgotPassword.vue';



/*//////////////////////////////////////////////////////////////////////x/////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPONENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('renders page', () => {

		// act
		const wrapper = mount(PageForgotPassword, {
			shallow: true,
			global: {
				stubs: {
					'FormErrors': true,
					'InputText': true,
				}
			}
		});

		// assert
		expect(wrapper.find('.content.auth')).toBeDefined();
		expect(wrapper.find('#email-forgot')).toBeDefined();
		expect(wrapper.find('.btn-forgot')).toBeDefined();
	});



/*//////////////////////////////////////////////////////////////////////x/////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FORM
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('submit form', async () => {

		// arrange router
		mockedRouter.updateRoute({'name':'password.forgot', 'path':'/password/forgot'});

		// arrange component
		const wrapper = mount(PageForgotPassword, {
			shallow: true,
			global: {
				stubs: {
					'FormErrors': true,
					'InputText': true,
					'Btn': Btn,
				}
			}
		});

		// act
		await wrapper.get('.btn-forgot').trigger('click');
		await flushPromises();

		// assert
		expect(mockedRouter.getRoute().name).toBe('password.forgot');
		expect(wrapper.get('h4').text()).toBe('success');
	});


	test('submit form with errors', async () => {

		// arrange router
		mockedRouter.updateRoute({'name':'password.forgot', 'path':'/password/forgot'});

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
		const wrapper = mount(PageForgotPassword, {
			shallow: true,
			global: {
				stubs: {
					'FormErrors': true,
					'InputText': true,
					'Btn': Btn,
				}
			}
		});

		// act
		await wrapper.get('.btn-forgot').trigger('click');
		await flushPromises();

		// assert
		expect(mockedRouter.getRoute().name).toBe('password.forgot');
		expect(mockedForm.errors.value.email).toBeDefined();
	});



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


