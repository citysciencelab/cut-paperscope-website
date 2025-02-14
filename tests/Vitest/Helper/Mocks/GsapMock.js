/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INTERFACE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const mockedGsap = {

		to: vi.fn(),
		fromTo: vi.fn(),
		set: vi.fn(),
	};

	mockedGsap.to.mockImplementation((el, opt) => {
		if(opt.onUpdate) { opt.onUpdate(); }
		if(opt.onComplete) { opt.onComplete(); }
		return mockedGsap;
	});

	mockedGsap.fromTo.mockImplementation((el, optFrom, optTo) => {
		if(optTo.onUpdate) { optTo.onUpdate(); }
		if(optTo.onComplete) { optTo.onComplete(); }
		return mockedGsap;
	});

	mockedGsap.set.mockImplementation((el, opt) => mockedGsap);

	mockedGsap.timeline = vi.fn(() => ({
		paused: vi.fn(),
		fromTo: vi.fn().mockReturnThis(),
		reversed: vi.fn().mockReturnThis(),
		resume: vi.fn(),
		seek: vi.fn(),
		invalidate: vi.fn(),
	}));

	window.gsap = mockedGsap;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	export {
		mockedGsap
	};
