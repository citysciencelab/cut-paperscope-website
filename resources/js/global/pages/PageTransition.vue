<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<transition class="page" :id="pageId" mode="in-out" @enter="enter" @leave="leave" @after-leave="afterLeave" :css="false">

			<main role="main" :key="route?.name+(route.params?.slug??'')">
				<slot/>
			</main>

		</transition>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, computed } from "vue";
		import { useRoute, useRouter } from 'vue-router'
		import { useConfig } from '@global/composables/useConfig';

		const route = useRoute();
		const router = useRouter();
		const { webContext } = useConfig();


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const pageId = computed(() => 'page-' + (route.name?.replace(/\./g,'-') ?? 'default') );


		/////////////////////////////////
		// ROUTER TRANSITIONS
		/////////////////////////////////

		const transitionName = ref('fade');			// type of transition defined in route: fade|slide|none


		router.beforeEach((to, from) => {

			// set transition of new route
			transitionName.value = to.meta.transition || from.meta.transition || 'fade';
		});


		/////////////////////////////////
		// EVENTS
		/////////////////////////////////

		const duration = 0.5;

		function enter(el, done) {

			// hide new route first before leaving anim
			gsap.set(el, {display: 'none'});
			done();
		}


		function leave(el, done) {

			// anim old route
			gsap.to(el, {
				duration,
				opacity: 0,
				ease: "power2.in",
				onComplete: done,
			})

			// anim footer
			if(webContext == 'backend') {
				gsap.to('footer',{duration, opacity: 0, ease: "power2.in"});
			}
		}


		function afterLeave(el) {

			gsap.set(u('.page').first(), { display:'block', opacity:0});

			// wait for new data to be loaded
			const timeoutDuration = window.savedPosition?.top > 0 ? 300 : 100

			setTimeout(()=>{

				window.scrollTo(window.savedPosition ?? {top:0,left:0})

				gsap.to(u('.page').first(), {
					duration: duration + 0.25,
					display: 'block',
					opacity: 1,
					ease: "power2.out",
					clearProps: 'all',
				})

				// anim footer
				if(webContext == 'backend') {
					gsap.to('footer',{duration, opacity: 1, ease: "power2.out", clearProps: 'all'});
				}

			}, timeoutDuration);
		}


	</script>


