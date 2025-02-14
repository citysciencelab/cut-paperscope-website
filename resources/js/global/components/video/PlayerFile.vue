<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<media-player
			:aspectRatio="options.aspectRatio"
			playsinline
			ref="playerComp"
			:src="sourceFile"
			:load="hasAutoplay?'eager':'visible'"
			posterLoad="visible"
			:autoplay="hasAutoplay?'autoplay':null"
			:muted="hasAutoplay?'muted':null"
			:loop="hasLoop?'loop':null"
			:onPlay="e => emit('playing',e)"
			:onEnded="e => emit('ended',e)"
			:onLoadedData="e => emit('loaded',e)"
			:onPause="e => emit('paused',e)"
		>
			<media-provider/>
			<div ref="captionsComp" class="vds-captions"></div>
			<media-video-layout v-if="hasControls"/>
			<media-poster v-if="posterFile" class="vds-poster" :src="posterFile"/>
		</media-player>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
////////////////////////////////////////////////////////////////////////////////////////////////////// ////////////////////////////////////////////////////////////////-->


	<script setup>


		import { ref, useTemplateRef, computed, onMounted, watch } from 'vue';
		import { useBreakpoints } from '@global/composables/useBreakpoints';

		import 'vidstack/player';
		import 'vidstack/player/layouts';
		import 'vidstack/player/ui';
		import 'vidstack/player/styles/default/theme.css'
		import 'vidstack/player/styles/default/captions.css'
		import 'vidstack/player/styles/default/layouts/video.css'
		import 'vidstack/player/styles/default/layouts/audio.css'

		import { CaptionsRenderer, parseResponse } from 'media-captions';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			source: 		{ type: String, required: true },
			sourceMobile: 	{ type: String },
			poster: 		{ type: String },
			posterMobile: 	{ type: String },
			subtitles: 		{ type: String },
			options: 		{ type: Object, required: true },
		});

		const emit = defineEmits(['loaded','playing','paused','ended']);

		const hasAutoplay = computed(() => props.options.autoplay == true);
		const hasLoop = computed(() => props.options.loop == true);
		const hasControls = computed(() => props.options.controls == true);


		/////////////////////////////////
		// SOURCE
		/////////////////////////////////

		const { isMobile } = useBreakpoints();

		const sourceFile = ref(null);
		const posterFile = ref(null);
		const playerComp = useTemplateRef('playerComp');

		function setSource() {

			sourceFile.value = isMobile.value ? (props.sourceMobile ?? props.source ) : props.source;
			posterFile.value = isMobile.value ? (props.posterMobile ?? props.poster ) : props.poster;

			updateSubtitles();
		}

		onMounted(setSource);
		watch(() => props.source, setSource);


		/////////////////////////////////
		// SUBTITLES
		/////////////////////////////////

		const captionsComp = useTemplateRef('captionsComp');
		var captionsRenderer = null;

		async function updateSubtitles()  {

			const video = u(playerComp.value).find('video').first();

			// reset state
			if(video) { video.removeEventListener('timeupdate', updateRenderer); }
			if(!props.subtitles) { return captionsRenderer?.destroy(); }

			if(!captionsRenderer) {
				captionsRenderer = new CaptionsRenderer(captionsComp.value);
			}

			// update captions data
			const captions = await parseResponse(fetch(props.subtitles));
			captionsRenderer.changeTrack(captions);

			// update events
			video.addEventListener('timeupdate', updateRenderer);
		}


		function updateRenderer() {

			const video = u(playerComp.value).find('video').first();
			captionsRenderer.currentTime = video.currentTime;
		}


	</script>


