/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { mount } from '@vue/test-utils';
	import { mockedRouter } from '@tests/Vitest/Helper/Mocks/useRouterMock';
	import { mockedGsap } from '@tests/Vitest/Helper/Mocks/GsapMock';

	// test component
	import PageTransition from '@global/pages/PageTransition.vue';
	import BlankComponent from '@tests/Vitest/Helper/BlankComponent.vue';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPONENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('renders component', async () => {

		// act
		const wrapper = mount(PageTransition, {
			slots: { default: BlankComponent }
		});

		// assert
		expect(wrapper.attributes('id')).toBe('page-index');
		expect(wrapper.text()).toContain('Blank Component');
	});


	test('renders component without route name', async () => {

		// arrange
		mockedRouter.updateRoute({path:'/default', meta:{}});

		// act
		const wrapper = mount(PageTransition, {
			slots: {
				default: BlankComponent,
			}
		});

		// assert
		expect(wrapper.attributes('id')).toBe('page-default');
		expect(wrapper.text()).toContain('Blank Component');
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	ANIMATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('animation enter', async () => {

		// arrange
		const wrapper = mount(PageTransition, {
			slots: { default: BlankComponent }
		});

		// act
		const done = vi.fn();
		wrapper.vm.enter(true, done);

		// assert
		expect(done).toHaveBeenCalled();
	});


	test('animation leave', async () => {

		// arrange
		const wrapper = mount(PageTransition, {
			slots: { default: BlankComponent }
		});

		// act
		const done = vi.fn();
		wrapper.vm.leave(true, done);

		// assert
		expect(done).toHaveBeenCalled();
	});


	test('animation afterLeave', async () => {

		// arrange
		vi.useFakeTimers()
		const wrapper = mount(PageTransition, {
			slots: { default: BlankComponent }
		});

		// arrange: gsap
		const spy = vi.spyOn(mockedGsap, 'to')

		// act
		wrapper.vm.afterLeave(true);
		vi.runAllTimers()

		// assert
		expect(spy).toHaveBeenCalled();
	});



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


