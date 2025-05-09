/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { mount, config } from '@vue/test-utils';
	import { mockedPage } from '@tests/Vitest/Helper/Mocks/usePageMock';

	// test component
	import PageError from '@global/pages/PageError.vue';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CONTENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('renders page', () => {

		// act
		const wrapper = mount(PageError);

		// assert
		expect(wrapper.text()).toContain('Fehler 404');
	});


	test('rediret to app index',() => {

		// act
		const wrapper = mount(PageError);

		// assert
		expect(wrapper.find('button').attributes()).toHaveProperty('to','index');
	});


	test('rediret to backend index',() => {

		// arrange
		config.global.mocks.webContext = 'backend';

		// act
		const wrapper = mount(PageError);

		// assert
		expect(wrapper.find('button').attributes()).toHaveProperty('to','backend.index');

		// revert
		config.global.mocks.webContext = 'app';
	});



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


