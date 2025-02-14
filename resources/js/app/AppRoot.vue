<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<header-component v-if="user"/>

		<!--PAGES -->
		<router-view v-slot="{Component}" >
			<page-transition>
				<component :is="Component"/>
			</page-transition>
		</router-view>

		<!--<footer-component/>-->

		<!-- POPUPS -->
		<lightbox/>
		<cookie-consent/>
		<popup-error/>
		<preview-mode/>

		<!-- RENDERLESS -->
		<matomo/>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { onBeforeUnmount } from 'vue';
		import { useGlobalStore } from '@global/stores/GlobalStore';
		import { useContentStore } from '@app/stores/ContentStore';
		import { useLazyload } from '@global/composables/useLazyload';
		import { useUser } from '@global/composables/useUser';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const globalStore = useGlobalStore();
		const contentStore = useContentStore();
		const { updateLazyload } = useLazyload();
		const { user } = useUser();

		initResize();
		initLazyload();

		onBeforeUnmount(() => {
			window.removeEventListener("resize",onResize);
		});


		/////////////////////////////////
		// STORES
		/////////////////////////////////

		const storeGlobalError = e => globalStore.setError(e);
		defineExpose({storeGlobalError});
		window.storeGlobalError = storeGlobalError; 	// provide global access for interceptors


		/////////////////////////////////
		// RESIZE
		/////////////////////////////////

		var resizeTimer = null;

		function initResize() {

			// initial min height for mobile browsers
			setCssVar('--vhMin',window.innerHeight+'px');

			window.addEventListener("resize",onResize);
			onResize();
		}

		function onResize() {

			const vw = document.body.clientWidth;
			const vh = window.innerHeight;

			// update css vars
			setCssVar('--vw',vw+'px');
			setCssVar('--vh',vh+'px');

			// update store
			if(resizeTimer) { window.cancelAnimationFrame(resizeTimer); }
			resizeTimer = window.requestAnimationFrame(() => globalStore.setViewport(vw,vh));
		}

		function setCssVar(name,value) {

			document.documentElement.style.setProperty(name,value);
		}


		/////////////////////////////////
		// LAZYLOAD
		/////////////////////////////////

		function initLazyload() {

			document.readyState === 'complete' ? updateLazyload() : window.addEventListener("load",updateLazyload);
		}


	</script>


