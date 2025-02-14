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
	import PageLogin from '@global/pages/auth/PageLogin.vue';
	import Btn from '@global/components/content/Btn.vue';



/*//////////////////////////////////////////////////////////////////////x/////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPONENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('renders page', () => {

		// act
		const wrapper = mount(PageLogin, {
			global: {
				stubs: {
					'FormErrors': true,
					'InputText': true,
					'SsoButtons': true,
				}
			}
		});

		// assert
		wrapper.get('#email');
		wrapper.get('#password');
		wrapper.get('.btn-login');
	});


	test('no page rendering on redirect', () => {

		// arrange: page composable
		mockedPage.setUser({'id':1});

		// act
		const wrapper = mount(PageLogin, {
			global: {
				stubs: {
					'FormErrors': true,
					'InputText': true,
					'SsoButtons': true,
				}
			}
		});

		// assert
		expect(wrapper.html()).not.toContain('Login');

		// revert
		mockedPage.setUser(null);
	});



/*//////////////////////////////////////////////////////////////////////x/////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FORM
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('submit form', async () => {

		// arrange router
		mockedRouter.updateRoute({'name':'login', 'path':'/login'});

		// arrange component
		const wrapper = mount(PageLogin, {
			global: {
				stubs: {
					'FormErrors': true,
					'InputText': true,
					'SsoButtons': true,
					'Btn': Btn,
				}
			}
		});

		// act
		await wrapper.get('.btn-login').trigger('click');

		// assert
		expect(mockedRouter.getRoute().name).toBe('home');
	});


	test('submit form with errors', async () => {

		// arrange router
		mockedRouter.updateRoute({'name':'login', 'path':'/login'});

		// arrange axios
		vi.spyOn(window.axios, 'post').mockRejectedValue({
			response: {
				status: 403,
				data: {
					errors: {
						email: ['The email field is required.'],
						password: ['The password field is required.'],
					},
					message: 'error message',
				}
			}
		});

		// arrange component
		const wrapper = mount(PageLogin, {
			global: {
				stubs: {
					'FormErrors': true,
					'InputText': true,
					'SsoButtons': true,
					'Btn': Btn,
				}
			}
		});

		// act
		await wrapper.get('.btn-login').trigger('click');
		await flushPromises();

		// assert
		expect(mockedRouter.getRoute().name).toBe('login');
		expect(mockedForm.errors.value[0]).toEqual('error message');
	});



/*//////////////////////////////////////////////////////////////////////x/////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SSO
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('sso failed login', () => {

		// arrange mocks
		mockedForm.reset();
		mockedRouter.updateRoute({'name':'login', 'path':'/login', 'query':{'error':'sso_mismatch'}});

		// act
		const wrapper = mount(PageLogin, {
			global: {
				stubs: {
					'FormErrors': true,
					'InputText': true,
					'SsoButtons': true,
					'Btn': Btn,
				}
			}
		});

		// assert
		expect(mockedForm.errors.value.sso).toBeDefined();
		expect(mockedForm.errors.value.email).toBeUndefined();
	});



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


