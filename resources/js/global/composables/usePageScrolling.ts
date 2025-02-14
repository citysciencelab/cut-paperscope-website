/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { ref, nextTick } from 'vue';

	// app
	import '@global/composables/types';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPOSABLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


export const usePageScrolling = () => {


	/////////////////////////////////
	// SCROLL ANIM
	/////////////////////////////////

	/**
	 * Move the page to a specific target
	 */

	function scrollToTarget(target: HTMLElement, offset = 0, duration = 1.0) {

		const rect = target.getBoundingClientRect();

		// calculate target scroll position
		var targetPos = rect.top + window.scrollY + offset;
		var maxScroll = document.body.scrollHeight - window.innerHeight;
		targetPos = maxScroll < targetPos ? maxScroll : targetPos;

		if(targetPos < 0) { targetPos = 0; }
		if(targetPos === window.scrollY) { return; }

		// tween
		var anim = {y:window.scrollY};
		window.gsap.to(anim, { y:targetPos, duration, ease: "power2.inOut", onUpdate:() => window.scrollTo(0, anim.y), });
	};


	/////////////////////////////////
	// SCROLL EVENT
	/////////////////////////////////

	const wheelOpt 		= ref(null);
	const wheelEvent 	= ref(null);
	const currentScroll = ref(null);

	const preventScrollingDefault = (e) => {

		if(e.srcElement.closest(".simplebar-content")) {

			var scroller = e.srcElement.closest('.simplebar-content-wrapper');
				var scrollPercentage = 100 * scroller.scrollTop / (scroller.scrollHeight-scroller.clientHeight);

				if(scrollPercentage<=0 && e.deltaY<0) { e.preventDefault(); }
				else if(scrollPercentage>=100 && e.deltaY>0) { e.preventDefault(); }
		}
		else {
			e.preventDefault();
		}
	};


	/////////////////////////////////
	// DISABLE
	/////////////////////////////////

	const disablePageScrolling = () => {

		/* v8 ignore start */
		// modern Chrome requires { passive: false } when adding event
		var supportsPassive = false;

		/* istanbul ignore next */
		try {
			window.addEventListener("test", null, Object.defineProperty({}, 'passive', {
				get: function () { supportsPassive = true; return true; }
			}));
		} catch(e) {}

		wheelOpt.value = supportsPassive ? { passive: false } : false;
		wheelEvent.value = 'onwheel' in document.createElement('div') ? 'wheel' : 'mousewheel';
		/* v8 ignore stop */

		// set events
		window.addEventListener('DOMMouseScroll', preventScrollingDefault, false); // older FF
		window.addEventListener(wheelEvent.value, preventScrollingDefault, wheelOpt.value); // modern desktop
		window.addEventListener('touchmove', preventScrollingDefault, wheelOpt.value); // mobile

		// set scrollbar width for css to prevent layout shift
		const documentWidth = document.documentElement.clientWidth;
		const windowWidth = window.innerWidth;
		const scrollBarWidth = windowWidth - documentWidth;
		document.documentElement.style.setProperty("--scrollbarWidth",scrollBarWidth+'px');

		// disable scrolling for iOS 15+
		if(!document.body.classList.contains("prevent-scrolling")) {
			currentScroll.value = window.scrollY;
			document.body.classList.add("prevent-scrolling");
		}
	};


	/////////////////////////////////
	// ENABLE
	/////////////////////////////////

	const enablePageScrolling = () => {

		window.removeEventListener('DOMMouseScroll', preventScrollingDefault, false); // older FF
		window.removeEventListener(wheelEvent.value, preventScrollingDefault, wheelOpt.value); // modern desktop
		window.removeEventListener('touchmove', preventScrollingDefault, wheelOpt.value); // mobile

		// remove scrollbar width
		document.documentElement.style.removeProperty("--scrollbarWidth");

		// enable scrolling for iOS 15+
		if(document.body.classList.contains("prevent-scrolling")) {
			document.body.classList.remove("prevent-scrolling");
			nextTick(() => window.scrollTo(0, currentScroll.value));
		}
	};

	/////////////////////////////////
	// EXPORT
	/////////////////////////////////

	return {
		scrollToTarget,
		preventScrollingDefault,
		enablePageScrolling, disablePageScrolling,
	};



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */



};
