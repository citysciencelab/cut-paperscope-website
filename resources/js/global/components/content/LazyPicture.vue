<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<picture class="lazy-picture" ref="root" v-if="target">

			<!-- WEBP -->
			<source v-if="isTablet && target.tablet?.webp" :data-srcset="target.tablet.webp" type="image/webp">
			<source v-else-if="isMobile && target.mobile?.webp" :data-srcset="target.mobile.webp" type="image/webp">
			<source v-else-if="target.desktop?.webp" :data-srcset="target.desktop.webp" type="image/webp">

			<!-- DEFAULT -->
			<source v-if="isTablet && target.tablet" :data-srcset="target.tablet.img" :type="getMimeType(target.tablet.img)">
			<source v-else-if="isMobile && target.mobile" :data-srcset="target.mobile.img" :type="getMimeType(target.mobile.img)">
			<source v-else :data-srcset="target.desktop.img" :type="getMimeType(target.desktop.img)">

			<!-- PRELOAD IMAGE -->
			<img class="lazy" :width="width" :height="height" v-if="target.preload" :src="target.preload" :alt="altText">
			<img class="lazy" :width="width" :height="height" v-else-if="isDesktop && target.desktop?.preload" :src="target.desktop.preload" :alt="altText">
			<img class="lazy" :width="width" :height="height" v-else-if="isTablet && target.tablet?.preload" :src="target.tablet.preload" :alt="altText">
			<img class="lazy" :width="width" :height="height" v-else-if="isMobile && target.mobile?.preload" :src="target.mobile.preload" :alt="altText">
			<img class="lazy" :width="width" :height="height" v-else src="" :alt="altText">
			<svg-item class="lazy-picture-preloader" v-if="width && height" :inline="webContext + '/loading-api'"/>

			<slot></slot>

		</picture>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, onMounted, useTemplateRef, computed, watch } from 'vue';
		import { useConfig } from '@/global/composables/useConfig';
		import { useBreakpoints } from '@/global/composables/useBreakpoints';
		import { useLanguage } from '@/global/composables/useLanguage';
		import { useLazyload } from '@/global/composables/useLazyload';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			item: 		{ type: Object, default() {return null;} },
			file: 		{ type: String, default: null},
			width: 		{ type: Number, default: null},
			height: 	{ type: Number, default: null},
			fileTablet: { type: Boolean, default: false},
			alt: 		{ type: String, default: 'Vorschaubild'},
		});

		const target = ref(null);
		const { baseUrl } = useConfig();
		const { isDesktop, isTablet, isMobile } = useBreakpoints();
		const { t } = useLanguage();
		const { resetLazyload, updateLazyload } = useLazyload();


		/////////////////////////////////
		//  ITEM
		/////////////////////////////////

		const root = useTemplateRef('root');
		const altText = computed(() => t(target.value.alt ?? target.value.title ?? 'Vorschaubild') );

		onMounted(() => {

			!props.item && props.file ? createItem(props.file) : target.value = props.item;
		});

		watch(() => props.file, () => createItem(props.file));


		function createItem(img) {

			// get file extension from string
			const ext = getFileExtension(img);

			// add absolute path to local file
			if(!img.startsWith('http')) { img = baseUrl.value + img; }

			var createWebp = ext != '.gif' && !img.includes('_upload/') ? true : false;
			if(!img.includes('-hr.')) { createWebp = false; }

			// create desktop file
			target.value = {
				alt: props.alt,
				desktop: {
					img: img,
					webp: createWebp ? img.replace(ext,'.webp') : undefined,
				},
			};

			// create mobile file
			if(img.includes('-desktop')) {
				const mobile = img.replace('-desktop','-mobile');
				target.value.mobile = {
					img: mobile,
					webp: createWebp ? mobile.replace(ext,'.webp') : undefined,
				};
			}

			// create preload file
			if(img.includes('-hr.')) {
				const preload = img.replace('-hr.','-lr.');
				target.value.preload = isMobile.value ? preload.replace('-desktop','-mobile') : preload;
			}

			// create additional tablet file
			if(props.fileTablet) {
				target.value.tablet = {
					img: img.replace('-desktop.','-tablet.'),
					webp: ext != '.gif' ? img.replace('-desktop.'+ext,'-tablet.webp') : undefined,
				};
			}

			// reset image lazy loading
			const image = u(root.value).find('img').first();
			if(image) {
				image.removeAttribute('data-ll-status');
				image.setAttribute('class','lazy');
			}
		}


		/////////////////////////////////
		//  RESET
		/////////////////////////////////

		watch(isTablet,()=>{

			resetLazyload(u(root.value).find('img').first());
			updateLazyload();
		});


		/////////////////////////////////
		//  HELPER
		/////////////////////////////////

		function getFileExtension(target) {

			let ext = target.split('?')[0];
			return '.' + ext.split('.').pop();
		}

		function getMimeType(target) {

			const ext = getFileExtension(target);

			if(ext == '.jpg') 		{ return 'image/jpeg'; }
			else if(ext == '.png') 	{ return 'image/png'; }
			else if(ext == '.gif') 	{ return 'image/gif'; }
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
			}
		}
	</i18n>

