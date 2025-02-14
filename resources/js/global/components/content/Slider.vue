<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div ref="root" class="swiper slider">

			<!-- SLIDES -->
			<div class="swiper-wrapper slider-container" ref="container">
				<slot></slot>
			</div>

			<!-- NAVIGATION -->
			<button
				v-if="opts.useNav && !options.prevButton"
				ref="prevButton"
				class="slider-button prev"
				:aria-label="t('button.prev')"
			>
				<svg-item :icon="webContext+'/slider-button'"/>
			</button>
			<button
				v-if="opts.useNav && !options.nextButton"
				ref="nextButton"
				class="slider-button next"
				:aria-label="t('button.next')"
			>
				<svg-item :icon="webContext+'/slider-button'"/>
			</button>

			<!-- PAGINATION -->
			<div class="swiper-pagination" aria-hidden="true"></div>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		/**
		*
		*	USAGE:
		*	<slider :options="sliderOptions"> ... </slider>
		*
		*	SLIDER OPTIONS
		*	useDrag: 			boolean				true
		*	useLoop: 			boolean 			false
		*	useNav:				boolean|element		true
		*	usePagination:		boolean				true
		*	nextButton: 		element				undefined
		*	prevButton: 		element				undefined
		*/

		import { ref, useTemplateRef, onMounted } from 'vue';
		import { useLanguage } from '@global/composables/useLanguage';

		import Swiper from 'swiper';
		import { Navigation, Pagination } from 'swiper/modules';
		import 'swiper/css';
		import 'swiper/css/navigation';
		import 'swiper/css/pagination';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			options: { type: Object, default: () => ({}) }
		});

		const opts = {
			useDrag: true,
			useLoop: true,
			useNav: true,
			usePagination: true,
			nextButton: undefined,
			prevButton: undefined,
			...props.options
		};


		const { t } = useLanguage();


		/////////////////////////////////
		// SWIPER
		/////////////////////////////////

		var swiper = null;
		const root = useTemplateRef('root');
		const prevButton = useTemplateRef('prevButton');
		const nextButton = useTemplateRef('nextButton');

		function initSwiper() {

			const pagination = u(root.value).find('.swiper-pagination').first();
			u('.slider-container > *').addClass('swiper-slide');

			let config =  {
				modules: [Navigation, Pagination],
				loop: opts.useLoop,
				speed: 800,
				allowTouchMove: opts.useDrag,
			};

			if(opts.useNav) {
				config.navigation = {
					nextEl: nextButton.value,
					prevEl: prevButton.value,
				}
			}

			if(opts.usePagination) {
				config.pagination = {
					el: pagination,
					clickable: true,
					bulletElement: 'button',
				};
			}

			swiper = new Swiper(root.value,config);
		}

		onMounted(initSwiper);


	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
				"button.next": "Nächstes Element anzeigen",
				"button.prev": "Vorheriges Element anzeigen",
			},
			"en": {
				"button.next": "Go to next element",
				"button.prev": "Go to previous element",
			}
		}
	</i18n>

