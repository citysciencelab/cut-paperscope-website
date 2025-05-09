/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { setActivePinia, createPinia } from 'pinia'
	setActivePinia(createPinia())

	// app
	import { useUserStore } from '@global/stores/UserStore';

	// test composable
	import { useUser } from '@global/composables/useUser';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	USER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('user model', () => {

		// arrange
		const store = useUserStore();
		store.setUser({email:'tester@hello-nasty.com'});

		// act
		const { user } = useUser();

		// assert
		expect(user.value.email).toEqual('tester@hello-nasty.com');
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SHOP
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('user has product', () => {

		// arrange
		const store = useUserStore();
		store.setUser({
			products:[ {id:'123-456'}, {id:'456-123'} ],
		});

		// act
		const { getUserProduct } = useUser();
		const product = getUserProduct('123-456');

		// assert
		expect(product.id).toEqual('123-456');
	});


	test('user does not have product', () => {

		// arrange
		const store = useUserStore();
		store.setUser({
			products:[],
		});

		// act
		const { getUserProduct } = useUser();
		const product = getUserProduct('123-456');

		// assert
		expect(product).toBeUndefined();
	});


	test('user has subscription', () => {

		// arrange
		const store = useUserStore();
		store.setUser({
			role: 'member',
		});

		// act
		const { userHasSubscription } = useUser();

		// assert
		expect(userHasSubscription.value).toBe(true);
	});


	test('user does not have subscription', () => {

		// arrange
		const store = useUserStore();
		store.setUser({
			role: 'guest',
		});

		// act
		const { userHasSubscription } = useUser();

		// assert
		expect(userHasSubscription.value).toBe(false);
	});




/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


