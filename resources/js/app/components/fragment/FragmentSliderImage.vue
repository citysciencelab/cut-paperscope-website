<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div ref="root" class="fragment-slider-image" :data-id="fragment.id">

			<h2 v-if="fragment.content.title">{{ fragment.content.title }}</h2>

			<slider @change="onSliderChange">
				<div v-for="slide in fragment.content.items" :key="'slider-'+fragment.id+activeLang">
					<img :data-src="slide.image" class="lazy" :alt="slide.image_alt">
					<p v-if="slide.image_subline" class="small fragment-slider-image-subline">{{ slide.image_subline }}</p>
				</div>
			</slider>

			<div v-if="fragment.content.copy" v-html="fragment.content.copy"></div>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, watch } from 'vue';
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			fragment: { type: Object, required: true }
		});


		/////////////////////////////////
		// MULTILANG
		/////////////////////////////////

		const { t, activeLang } = useLanguage();


		/////////////////////////////////
		// SLIDER
		/////////////////////////////////

		const root = useTemplateRef('root');

		function onSliderChange(currentIndex) {

			// preload image in next slide
			setTimeout(()=>{

				var img = u(root.value).find('.slide-active + div:not(.slider-loop)').find('img');

				if(img.length) {

					// manual update of lazyload
					img.attr('src', img.attr('data-src'));
					img.addClass('lazy entered loaded');
					img.attr('data-ll-status', 'loaded');
				}
			},1000 + 50); // animation duration + 50ms
		}

	</script>


