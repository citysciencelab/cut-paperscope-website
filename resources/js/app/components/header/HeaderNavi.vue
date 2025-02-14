<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<nav :class="['header-navi',{dropdown:isDropdown},{open:isOpenDropdown}]">

			<!-- ACCESSIBILITY SKIP -->
			<a href="#page-index" v-if="!isDropdown" class="header-navi-item accessibility-skip" @click="skipAccessibility">
				{{ t('Menü überspringen') }}
			</a>

			<!-- NAVI BUTTON -->
			<button
				ref="naviButton"
				v-if="isDropdown"
				class="header-navi-button"
				@click="toggleDropdown"
				:aria-label="t('Hauptnavigation öffnen')"
				:aria-expanded="!isDropdown ? null : isOpenDropdown"
				:aria-controls="isDropdown ? 'header-navi-container' : null"
			>
				<svg-item inline="app/header-navi-button"/>
			</button>

			<!-- CONTENT -->
			<div id="header-navi-container" class="header-navi-content">
				<component :is="isDropdown ? 'scroller' : 'div'">

					<!--
					<header-navi-item to="index" label="Startseite"/>
					<header-navi-item v-for="page in pages" :key="page.id" to="page.detail" :params="{slug:page.slug}" :label="page.navi_label??''"/>
					<header-navi-item to="visualizer" label="Visualizer"/>
					-->

					<!-- AUTH -->
					<!--
					<header-navi-item v-if="user" to="home" label="Projekte"/>
					<header-navi-item v-if="user" label="Logout" @click="logout"/>
					<header-navi-item v-if="!user" to="login" label="Login"/>
					-->
					<!-- <header-navi-item v-if="!user" to="register" label="Registrieren"/> -->

					<language-select :dropdown="isDropdown"/>

				</component>

				<!-- ACCESSIBILITY CLOSE -->
				<button v-if="isOpenDropdown" class="header-navi-item accessibility-close" @click="closeAccessibility">
					{{ t('Menü schließen') }}
				</button>
			</div>

		</nav>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, onMounted, watch } from 'vue';
		import { useRoute } from 'vue-router'
		import { storeToRefs } from 'pinia'

		import { useBreakpoints } from '@global/composables/useBreakpoints';
		import { usePageScrolling } from '@global/composables/usePageScrolling';
		import { useAuth } from '@global/composables/useAuth';
		import { useGlobalStore } from '@global/stores/GlobalStore';
		import { useContentStore } from '@app/stores/ContentStore';
		import { useUser } from '@global/composables/useUser';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const route = useRoute();
		const { mobileBreakpoint } = useBreakpoints();
		const { enablePageScrolling, disablePageScrolling } = usePageScrolling();
		const { logout } = useAuth();
		const { vh } = storeToRefs(useGlobalStore());
		const { pages } = storeToRefs(useContentStore());
		const { user } = useUser();

		const props = defineProps({
			autoDropdown: {type: Boolean, default: true}, 		// switch to dropdown automatically if not enough space available or use mobile breakpoint
		});

		const emit = defineEmits(['update']);


		/////////////////////////////////
		// DROPDOWN
		/////////////////////////////////

		const isDropdown = ref(false);
		const isOpenDropdown = ref(false);


		onMounted(()=>{

			window.addEventListener("resize",onResize);
			onResize();
			skipAccessibility();
		});


		function toggleDropdown() {

			// update status
			if(!isDropdown.value) { return; }
			isOpenDropdown.value = !isOpenDropdown.value;
			emit('update',isOpenDropdown.value);

			animation.reversed(!isOpenDropdown.value).resume();

			if(isOpenDropdown.value) {
				disablePageScrolling();
				document.addEventListener('keydown', closeOnEsc);
			}
			else {
				setTimeout(() => enablePageScrolling(), 500);
				document.removeEventListener('keydown', closeOnEsc);
			};
		}


		function closeDropdown(onRouteChange = false) {

			if(!isDropdown.value) { return; }

			animation.reversed(true).resume();

			if(isOpenDropdown.value && onRouteChange) {
				gsap.set(u('.page').first(),{display:'none'});
				gsap.set(u('footer').first(),{opacity:0});
			}

			// enable scrolling after old route is hidden
			setTimeout(() => enablePageScrolling(), 500);
			document.removeEventListener('keydown', closeOnEsc);

			// update status
			isOpenDropdown.value = false;
			emit('update',false);
		}


		/////////////////////////////////
		// ANIMATION
		/////////////////////////////////

		var animation = null;

		function initDropdownAnimation() {

			animation = gsap.timeline({paused:true});
			animation.call(() => u('.header-navi-content').toggleClass('animating',!animation.reversed()));
			animation.fromTo('.header-navi-item',{opacity:0}, {duration:0.2, opacity:1, ease:'power2'}, 0.1);
			animation.fromTo('.header-navi-content', { height:0, paddingBottom:0, display:'none' }, { duration:0.3, height:getNaviHeight, paddingBottom:getNaviPadding, display:'flex', ease:'power2' }, 0);
			animation.set('.page',{opacity:0},0.25);
			animation.call(() => u('.header-navi-content').toggleClass('animating',animation.reversed()),null,0.25);
		}


		const getNaviHeight = () => 'calc('+vh.value+'px + 50px - var(--headerHeight))';
		const getNaviPadding = () => 'calc(50px + var(--headerHeight))';


		function killDropdownAnimation() {

			// manually close dropdown
			isOpenDropdown.value = false;
			animation?.pause(0).kill();
			enablePageScrolling();

			u('.header-navi-content, .header-navi-item').attr('style','');
		}


		/////////////////////////////////
		// AUTO DROPDOWN
		/////////////////////////////////

		var autoBreakpoint = null;

		function getAutoBreakpoint() {

			// only update calculation if not in dropdown mode
			if(isDropdown.value) { return autoBreakpoint; }

			// initial navi width
			autoBreakpoint = u('.header-navi').outerWidth();

			// add padding of header
			const style = getComputedStyle(u('header').first());
			autoBreakpoint += parseInt(style.paddingRight);

			// add logo width and left position
			const logo = u('.header-logo').first();
			autoBreakpoint += logo ? u(logo).outerWidth() : 0;
			autoBreakpoint += logo ? logo.getBoundingClientRect().left : 0;

			// add space between logo and navi
			autoBreakpoint += 30;

			return autoBreakpoint;
		}


		/////////////////////////////////
		// RESIZE
		/////////////////////////////////

		var resizeTimer = null;

		function onResize() {

			// debounced event
			if(resizeTimer) { window.cancelAnimationFrame(resizeTimer); }
			resizeTimer = window.requestAnimationFrame(() => {

				const ww = window.innerWidth;
				const breakpoint = props.autoDropdown ? getAutoBreakpoint() : mobileBreakpoint;

				// update dropdown status
				if( ww < breakpoint && isDropdown.value == false ) { isDropdown.value = true; }
				else if(ww > breakpoint && isDropdown.value) { closeDropdown(); isDropdown.value = false; }
			});
		}


		/////////////////////////////////
		// WATCH
		/////////////////////////////////

		watch(isDropdown, value => value ? initDropdownAnimation() : killDropdownAnimation());
		watch(() => route.name+(route.params.slug??''), () => closeDropdown(true));
		watch(vh, value => animation?.invalidate());
		watch(pages, value => onResize());


		/////////////////////////////////
		// ACCESSIBILITY
		/////////////////////////////////

		const naviButton = useTemplateRef('naviButton');

		function closeOnEsc(e) {

			if(e.key == 'Escape') {
				closeDropdown();
				u('.header-navi-button').first().focus()
			}
		}

		function skipAccessibility(e) {

			const id = u('main').first().id;
			u('.accessibility-skip').attr('href','#'+id);
		}

		function closeAccessibility() {

			closeDropdown();
			naviButton.value.focus();
		}


	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
			},
			"en": {
				"Hauptnavigation öffnen": "Open main navi",
				"Menü überspringen": "Skip navi",
				"Menü schließen": "Close navi"
			}
		}
	</i18n>

