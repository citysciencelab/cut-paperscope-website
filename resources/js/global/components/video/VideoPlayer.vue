<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div ref="root" :class="['video-player',{ background: options.background }]">

			<!-- VIDEO FILES -->
			<player-file
				v-if="isFile"
				ref="filePlayer"
				:source="source"
				:source-mobile="sourceMobile"
				:poster="poster"
				:poster-mobile="posterMobile"
				:subtitles="subtitles"
				:options="options"
				@loaded="onLoaded"
				@playing="onPlaying"
				@paused="onPaused"
				@ended="onEnded"
			/>

			<!-- STREAM -->
			<!-- <player-stream
				v-if="isStream"
				ref="streamPlayer"
				:source="source"
				:options="options"
			/> -->

		</div>


	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		/**
		* 	HOW TO USE:
		* 	<video-player source="..." source-mobile="..." options="..."/>
		*
		*	OPTIONS:
		*	Most options are passed to Plyr but some are special properties for this component
		*
		* 	options: {
		*		controls: 		true,			// show video controls
		*		muted: 			true,
		*		autoplay: 		true,
		*		loop: 			false,
		*		background: 	true,			// use this video player as a background video for other content elements. Ratio must be 16:9
		*	},
		*/

		import { ref, useTemplateRef, onMounted, onBeforeUnmount, watch } from 'vue';
		import { useBreakpoints } from '@global/composables/useBreakpoints';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			source: 		{ type: String, required: true },
			sourceMobile: 	{ type: String },
			poster: 		{ type: String },
			posterMobile: 	{ type: String },
			subtitles: 		{ type: String },
			options: 		{ type: Object, default() { return { background:false, controls:true, aspectRatio:'16/9' }; } },
		});

		const emit = defineEmits(['loaded','playing','paused','ended']);


		/////////////////////////////////
		// PLAYER
		/////////////////////////////////

		const isFile = ref(false);
		const isStream = ref(false);
		const root = useTemplateRef('root');
		const streamPlayer = ref(null);
		const filePlayer = useTemplateRef('filePlayer');

		onMounted(initPlayer)
		watch(()=>props.source, initPlayer);

		function initPlayer() {

			// is streaming file
			if(props.source.endsWith('.m3u8') || props.source.endsWith('.mpd')) {
				isStream.value = true;
			}
			// is protected stream with signed url
			else if(props.source.includes('.m3u8?expires=') || props.source.includes('.mpd?expires=')) {
				isStream.value = true;
			}
			// use default file player
			else {
				isFile.value = true;
			}

			// init events for background video
			if(props.options.background) {
				u(root.value).parent().addClass('video-player-background-container');
				window.removeEventListener('resize', onResize);
				window.addEventListener('resize', onResize);
				onResize();
			}
		}

		function get() {

			if(isStream.value) { return streamPlayer.value.player ?? undefined;	}
			else { return filePlayer.value.player ?? undefined; }
		}


		/////////////////////////////////
		// GETTER / SETTER
		/////////////////////////////////

		function onLoaded(e) 	{ emit('loaded',e); }
		function onPlaying(e) 	{ emit('playing',e); }
		function onPaused(e) 	{ emit('paused',e); }
		function onEnded(e) 	{ emit('ended',e); }

		function play() 		{ this.get()?.play(); }
		function pause() 		{ this.get()?.pause(); }
		function seekForward() 	{ this.get()?.forward(15); }
		function seekBackward() { this.get()?.rewind(15); }

		defineExpose({ get, play, pause, seekForward, seekBackward });


		/////////////////////////////////
		// BACKGROUND VIDEO
		/////////////////////////////////

		const { isMobile } = useBreakpoints();

		function onResize(e) {

			var size = u(root.value).parent().size();
			var w,h = 0;
			var videoRatio = isMobile.value ? 4/5 : 16/9;

			// set cover size for video
			if( (size.width/size.height) < videoRatio) {
				h = size.height;
				w = h * videoRatio;
			}
			else {
				w = size.width;
				h = w / videoRatio;
			}

			// skip if parent size not available
			if(w==0 && h==0) { return; }

			// set new video size
			u(root.value).attr({style:`width:${w}px;height:${h}px;`});
		}

		watch(isMobile, onResize);

		onBeforeUnmount(()=>{
			window.removeEventListener('resize', onResize);
		});


	</script>


