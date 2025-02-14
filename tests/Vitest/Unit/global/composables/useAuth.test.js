/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { mockedRouter } from '@tests/Vitest/Helper/Mocks/useRouterMock';
	import { setActivePinia, createPinia } from 'pinia'
	setActivePinia(createPinia())

	// app
	import { useUserStore } from '@global/stores/UserStore';

	// test composable
	import { useAuth } from '@global/composables/useAuth'



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	LOGIN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('login', async () => {

		// act
		const { login } = useAuth();
		const response = await login({email:'tester@hello-nasty.com',password:'password'});

		// assert response
		expect(response.data.status).toBe('success');
		expect(response.data.data.route).toBe('auth/login');

		// assert redirect
		expect(mockedRouter.getRoute().name).toBe('home');
	});


	test('login with custom redirect', async () => {

		// arrange
		mockedRouter.updateRoute({name:'index', path:'/index', query:{redirect:'/custom'}, meta:{}});

		// act
		const { login } = useAuth();
		await login({email:'tester@hello-nasty.com',password:'password'});

		// assert redirect
		expect(mockedRouter.getRoute().path).toBe('/custom');
	});


	test('login with stripe checkout redirerct', async () => {

		// arrange
		mockedRouter.updateRoute({name:'index', path:'/index', query:{redirect:'/stripe/checkout'}, meta:{}});

		// arrange: redirect
		const spy = vi.spyOn(window.location, 'href', 'set');

		// act
		const { login } = useAuth();
		await login({email:'tester@hello-nasty.com',password:'password'});

		// assert
		expect(spy).toHaveBeenCalledTimes(1);
	});



	test('login with invalid redirect', async () => {

		// arrange
		mockedRouter.updateRoute({name:'index', path:'/index', query:{redirect:'https://www.hello-nasty.com'}, meta:{}});

		// act
		const { login } = useAuth();
		await login({email:'tester@hello-nasty.com',password:'password'});

		// assert redirect
		expect(mockedRouter.getRoute().name).toBe('home');
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	LOGOUT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('logout', async () => {

		// arrange
		vi.useFakeTimers();
		const store = useUserStore();
		store.setUser({id:123});

		// arrange: context
		let context = window.config.webContext;
		window.config.webContext = 'app';

		// act
		const { logout } = useAuth();
		const response = await logout();
		vi.runAllTimers();

		// assert response
		expect(response.data.status).toBe('success');
		expect(response.data.data.route).toBe('auth/logout');

		// assert redirect
		expect(mockedRouter.getRoute().name).toBe('login');
		expect(store.user).toBe(null);

		// restore
		window.config.webContext = context;
		vi.useRealTimers();
	});


	test('logout in backend', async () => {

		// arrange
		vi.useFakeTimers();
		const store = useUserStore();
		store.setUser({id:123});

		// arrange: context
		let context = window.config.webContext;
		window.config.webContext = 'backend';

		// act
		const { logout } = useAuth();
		const response = await logout(true);
		vi.runAllTimers();

		// assert response
		expect(response.data.status).toBe('success');
		expect(response.data.data.route).toBe('auth/logout');

		// assert redirect
		expect(mockedRouter.getRoute().name).toBe('login');
		expect(store.user).toBe(null);

		// restore
		window.config.webContext = context;
		vi.useRealTimers();
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	REGISTER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('register', async () => {

		// arrange
		const store = useUserStore();
		store.setUser(null);

		// act
		const { register } = useAuth();
		const response = await register({email:'tester@hello-nasty.com',password:'password'});

		// assert response
		expect(response.data.status).toBe('success');
		expect(response.data.data.route).toBe('auth/register');

		// assert redirect
		expect(mockedRouter.getRoute().name).toBe('home');

		// assert user
		expect(store.user).not.toBe(null);
	});


	test('resend verify', async () => {

		// act
		const { resendVerify } = useAuth();
		const response = await resendVerify({email:'tester@hello-nasty.com'});

		// assert response
		expect(response.data.status).toBe('success');
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	USER GET
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('get user', async () => {

		// act
		const { getUser } = useAuth();
		const userData = await getUser();

		// assert user object instead of response
		expect(userData.route).toBe('api/user');
	});


	test('get user error', async () => {

		// arrange: mock axios error
		const spyAxios = vi.spyOn(window.axios, 'get')
		spyAxios.mockImplementationOnce(() => Promise.reject({ response: { status: 401 } }));

		// act
		var result = null;
		const { getUser } = useAuth();
		await getUser().catch(e => result = e);

		// assert
		expect(spyAxios).toHaveBeenCalledTimes(1);
		expect(result.response.status).toBe(401);
	});


	test('get user with wrong response', async () => {

		// arrange: mock axios error
		const spyAxios = vi.spyOn(window.axios, 'get')
		spyAxios.mockImplementationOnce(() => Promise.resolve({ response: { status: 200 } }));

		// act
		const { getUser } = useAuth();
		const result = await getUser();

		// assert
		expect(spyAxios).toHaveBeenCalledTimes(1);
		expect(result.response.status).toBe(200);
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	USER DELETE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('delete user', async () => {

		// arrange
		const store = useUserStore();
		store.setUser({id:123});

		// act
		const { deleteUser } = useAuth();
		const response = await deleteUser(store.user.id);

		// assert
		expect(store.user).toBe(null);
		expect(response.data.data.route).toBe('api/user/delete');
	});


	test('delete user in backend', async () => {

		// arrange: set context
		var context = window.config.webContext;
		window.config.webContext = 'backend';

		// arrange: user
		const store = useUserStore();
		store.setUser({id:123});

		// act
		const { deleteUser } = useAuth();
		const response = await deleteUser(store.user.id, true);

		// assert: user will be deleted after page transistion
		expect(store.user).not.toBe(null);

		// reset
		window.config.webContext = context;
	});



	test('delete user error', async () => {

		// arrange: mock axios error
		const spyAxios = vi.spyOn(window.axios, 'post')
		spyAxios.mockImplementationOnce(() => Promise.reject({ response: { status: 401 } }));

		// arrange: user
		const store = useUserStore();
		store.setUser({id:123});

		// act
		var result = null;
		const { deleteUser } = useAuth();
		await deleteUser(store.user.id).catch(e => result = e);

		// assert
		expect(spyAxios).toHaveBeenCalledTimes(1);
		expect(result.response.status).toBe(401);
	});


	test('delete user with wrong response', async () => {

		// arrange: mock axios error
		const spyAxios = vi.spyOn(window.axios, 'post')
		spyAxios.mockImplementationOnce(() => Promise.resolve({ response: { status: 200 } }));

		// arrange: user
		const store = useUserStore();
		store.setUser({id:123});

		// act
		const { deleteUser } = useAuth();
		const result = await deleteUser(store.user.id);

		// assert
		expect(spyAxios).toHaveBeenCalledTimes(1);
		expect(result.response.status).toBe(200);
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	PASSWORD
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('forgot password', async () => {

		// act
		const { forgotPassword } = useAuth();
		const response = await forgotPassword({email:'tester@hello-nasty.com',password:'password'});

		// assert response
		expect(response.data.status).toBe('success');
		expect(response.data.data.route).toBe('auth/forgot-password');
	});


	test('reset password', async () => {

		// act
		const { resetPassword } = useAuth();
		const response = await resetPassword({email:'tester@hello-nasty.com',password:'password'});

		// assert response
		expect(response.data.status).toBe('success');
		expect(response.data.data.route).toBe('auth/reset-password');
	});


	test('update password', async () => {

		// act
		const { updatePassword } = useAuth();
		const response = await updatePassword({email:'tester@hello-nasty.com',password:'password'});

		// assert response
		expect(response.data.status).toBe('success');
		expect(response.data.data.route).toBe('auth/user/password');
	});




/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


