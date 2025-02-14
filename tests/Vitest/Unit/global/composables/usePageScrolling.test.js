/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
    import { mockedGsap } from '@tests/Vitest/Helper/Mocks/GsapMock';

	// test composable
	import { usePageScrolling } from '@global/composables/usePageScrolling'



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SCROLL ANIM
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('scrollToTarget', () => {

		// arrange: browser
		window.innerHeight = 100;
		document.body.scrollHeight = 1000;

		// arrange: element
		const div = document.createElement('div');
		vi.spyOn(div, 'getBoundingClientRect');
		div.getBoundingClientRect.mockReturnValue({ top: 123 });

		// arrange: mock window
		const spyScroll = vi.spyOn(window, 'scrollTo');

		// act
		const { scrollToTarget } = usePageScrolling();
		scrollToTarget(div);

		// assert
		expect(spyScroll).toHaveBeenCalledTimes(1);

		// revert
		window.innerHeight = 0;
		document.body.scrollHeight = 0;
	});


	test('scrollToTarget with max scroll ', () => {

		// arrange: browser
		window.innerHeight = 100;
		document.body.scrollHeight = 1000;

		// arrange: element
		const div = document.createElement('div');
		vi.spyOn(div, 'getBoundingClientRect');
		div.getBoundingClientRect.mockReturnValue({ top: 9999 });

		// arrange: mock window
		const spyScroll = vi.spyOn(window, 'scrollTo');

		// act
		const { scrollToTarget } = usePageScrolling();
		scrollToTarget(div);

		// assert
		expect(spyScroll).toHaveBeenCalledTimes(1);

		// revert
		window.innerHeight = 0;
		document.body.scrollHeight = 0;
	});


	test('scrollToTarget with negative target position ', () => {

		// arrange: browser
		window.innerHeight = 100;
		document.body.scrollHeight = 1000;

		// arrange: element
		const div = document.createElement('div');
		vi.spyOn(div, 'getBoundingClientRect');
		div.getBoundingClientRect.mockReturnValue({ top: -1000 });

		// arrange: mock window
		const spyScroll = vi.spyOn(window, 'scrollTo');

		// act
		const { scrollToTarget } = usePageScrolling();
		scrollToTarget(div);

		// assert
		expect(spyScroll).toHaveBeenCalledTimes(0);

		// revert
		window.innerHeight = 0;
		document.body.scrollHeight = 0;
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	PREVENT SCROLLING
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('prevent scrolling default on up', () => {

		// arrange event
		const event = {
			srcElement: {
				closest: () => ({
					scrollTop: 100,
					scrollHeight: 100,
					clientHeight: 100
				})
			},
			deltaY: 1,
			preventDefault: () => {}
		};
		vi.spyOn(event, 'preventDefault');

		// act
		const { preventScrollingDefault } = usePageScrolling();
		preventScrollingDefault(event);

		// assert
		expect(event.preventDefault).toHaveBeenCalled();
	});


	test('prevent scrolling default on down', () => {

		// arrange event
		const event = {
			srcElement: {
				closest: () => ({
					scrollTop: -100,
					scrollHeight: 100,
					clientHeight: 100
				})
			},
			deltaY: -1,
			preventDefault: () => {}
		};
		vi.spyOn(event, 'preventDefault');

		// act
		const { preventScrollingDefault } = usePageScrolling();
		preventScrollingDefault(event);

		// assert
		expect(event.preventDefault).toHaveBeenCalled();
	});


	test('prevent scrolling default without simplebar', () => {

		// arrange event
		const event = {
			srcElement: {
				closest: () => null
			},
			preventDefault: () => {}
		};
		vi.spyOn(event, 'preventDefault');

		// act
		const { preventScrollingDefault } = usePageScrolling();
		preventScrollingDefault(event);

		// assert
		expect(event.preventDefault).toHaveBeenCalled();
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	DISABLE SCROLLING
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('disable page scrolling', async () => {

		// act
		const { disablePageScrolling } = usePageScrolling();
		disablePageScrolling();

		// assert
		expect(document.body.classList.contains('prevent-scrolling')).toBe(true);
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	ENABLE SCROLLING
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('enable page scrolling', async () => {

		// arrange
		document.body.classList.add('prevent-scrolling');

		// act
		const { enablePageScrolling } = usePageScrolling();
		enablePageScrolling();

		// assert
		expect(document.body.classList.contains('prevent-scrolling')).toBe(false);
	});



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


