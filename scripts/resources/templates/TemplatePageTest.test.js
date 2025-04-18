/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { mount } from '@vue/test-utils';
	import { mockedPage } from '@tests/Vitest/Helper/Mocks/usePageMock';

	// test component
	import Page__NAME__ from '@global/pages/__PATH__Page__NAME__.vue';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CONTENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('renders page', async () => {

		// act
		const wrapper = mount(Page__NAME__);

		// assert
		expect(wrapper.text()).toContain('__NAME__');
	});



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


