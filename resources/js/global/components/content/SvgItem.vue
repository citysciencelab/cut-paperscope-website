<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<!-- INLINE DATA -->
		<inline-svg v-if="inline" class="svg-inline" :src="pathInline" v-bind="$attrs" @loaded="onLoaded"/>

		<!-- EXTERNAL FILE -->
		<img v-else-if="url" class="svg-sprite" :src="pathUrl" v-bind="$attrs" alt="Icon" role="presentation"/>

		<!-- ICON SPRITE -->
		<svg v-else-if="icon" class="svg-icon" v-bind="$attrs">
			<use :xlink:href="pathIcon" />
		</svg>

		<!--- SPRITE -->
		<svg v-else class="svg-sprite" v-bind="$attrs">
			<use :xlink:href="pathSprite" />
		</svg>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, computed } from 'vue';
		import { useConfig } from '@global/composables/useConfig';
		import InlineSvg from 'vue-inline-svg';


		/*
		*	Usage:
		*	<svg-item icon="app/close"/>
		*/


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			icon: 		{type: String },			// use a symbol from svg icons sprite.
			sprite: 	{type: String },			// use a symbol from svg sprite.
			url: 		{type: String },			// use an external svg file.
			inline: 	{type: String },			// use a single svg file for inline usage.
		});


		const emit = defineEmits(['loaded']);
		const { baseUrl, hash, isLocal } = useConfig();


		/////////////////////////////////
		// PATHS
		/////////////////////////////////

		const baseSvg		= baseUrl + 'svg/';
		const hashSvg		= computed(() => isLocal ? '' : '?id=' + hash);
		const pathInline 	= computed(() => baseSvg + props.inline + '.svg' + hashSvg.value);
		const pathUrl 		= computed(() => props.url + hashSvg.value);
		const pathIcon 		= computed(() => baseSvg + props.icon?.replace('/','/sprite-icons.svg' + hashSvg.value + '#'));
		const pathSprite 	= computed(() => baseSvg + props.sprite?.replace('/','/sprite.svg' + hashSvg.value + '#'));


		/////////////////////////////////
		// INLINE SVG
		/////////////////////////////////

		const loaded = ref(false);
		function onLoaded() { loaded.value = true; emit('loaded'); };


	</script>


