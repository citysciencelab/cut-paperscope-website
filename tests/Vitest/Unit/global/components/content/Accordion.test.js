/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { mount } from '@vue/test-utils';
	import { mockedGsap } from '@tests/Vitest/Helper/Mocks/GsapMock';

	// test component
	import Accordion from '@global/components/content/Accordion.vue';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CONTENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const contentSlot = {
		default: '<div class="slot-content">slot content</div>',
	}


	test('renders title', async () => {

		// act
		const wrapper = mount(Accordion, {
			props: { title:'slot title' },
		});

		// assert
		expect(wrapper.get('h4').html()).toContain('slot title');
	});


	test('renders content', async () => {

		// act
		const wrapper = mount(Accordion, {
			props: { title:'slot title' },
			slots: contentSlot
		});

		// assert
		expect(wrapper.get('.accordion-content').html()).toContain('slot content');
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CONTENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */
/*
	describe('Accordion.vue', () => {

		test('renders title prop', () => {
			const title = 'Test Accordion';
			const wrapper = mount(Accordion, {
				props: { title }
			});

			expect(wrapper.find('h4').text()).toBe(title);
		});

		test('toggles open state when header is clicked', async () => {
			const wrapper = mount(Accordion);
			const header = wrapper.find('.accordion-header');

			// Initially closed
			expect(wrapper.classes()).not.toContain('open');
			expect(wrapper.vm.isOpen).toBe(false);

			// Click to open
			await header.trigger('click');
			expect(wrapper.classes()).toContain('open');
			expect(wrapper.vm.isOpen).toBe(true);

			// Click to close
			await header.trigger('click');
			expect(wrapper.classes()).not.toContain('open');
			expect(wrapper.vm.isOpen).toBe(false);
		});

		test('renders slot content when open', async () => {
			const wrapper = mount(AccordionWithContent);

			// Initially closed, so slot content should not be visible (so is in the dom but hidden with aria-hidden="true")
			expect(wrapper.find('.accordion-content').attributes('aria-hidden')).toBe('true');

			// Open the accordion
			await wrapper.find('.accordion-header').trigger('click');

			// Now the slot content should be visible
			expect(wrapper.find('.accordion-content').attributes('aria-hidden')).toBe('false');
		});

		test('sets aria attributes correctly', async () => {
			const wrapper = mount(AccordionWithContent);
			const header = wrapper.find('.accordion-header');

			// Initially closed
			expect(header.find('.accordion-header-icon').attributes('aria-expanded')).toBe('false');
			expect(wrapper.find('.accordion-content').attributes('aria-hidden')).toBe('true');

			// Open the accordion
			await header.trigger('click');

			// Check aria attributes after opening
			expect(header.find('.accordion-header-icon').attributes('aria-expanded')).toBe('true');
			expect(wrapper.find('.accordion-content').attributes('aria-hidden')).toBe('false');
		});

		test('animates on toggle', async () => {
			const wrapper = mount(Accordion);
			const header = wrapper.find('.accordion-header');

			// Mock the GSAP animation
			const content = wrapper.find('.accordion-content');

			// Click to open
			await header.trigger('click');
			expect(mockedGsapAdditions.timeline).toHaveBeenCalledWith(expect.any(Object));

			// Click to close
			await header.trigger('click');
			expect(mockedGsapAdditions.timeline).toHaveBeenCalledWith(expect.any(Object));
		});
	});
*/
