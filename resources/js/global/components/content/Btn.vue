<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<!-- INTERNAL LINK -->
		<router-link
			v-bind="$attrs" v-if="to || path"
			:to="to?link(to,params,query):{path}"
			:class="['btn',{'icon':icon && !label},{'icon-label':icon && label},{'disabled':disabled}]"
		>
			<svg-item v-if="icon" :icon="iconPath"/>
			{{ label ? t(label) : "&hairsp;" }}
		</router-link>

		<!-- EXTERNAL LINK -->
		<a
			v-else-if="href"
			v-bind="$attrs"
			:href="href"
			target="_blank"
			rel="noopener noreferrer"
			@click="disabled && $event.preventDefault()"
			:class="['btn',{'icon':icon && !label},{'icon-label':icon && label},{'disabled':disabled}]"
			:aria-label="t('link.external')"
		>
			<svg-item v-if="icon" :icon="iconPath"/>
			{{ label ? t(label) : "&hairsp;" }}
		</a>

		<!-- NO LINK -->
		<button
			ref="root"
			v-else
			v-bind="$attrs"
			@click="onClick"
			:class="['btn',{'active':isActive},{'icon':icon && !label},{'icon-label':icon && label},{'blocking':blocking && isLoading},{'disabled':disabled}]"
		>
			<svg-item v-if="blocking" :inline="webContext+'/loading-api'" class="loading-icon"/>
			<svg-item v-if="icon" :icon="iconPath"/>
			<span>
				{{ label ? t(label) : "&hairsp;" }}
			</span>
		</button>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, computed } from 'vue';

		import { useConfig } from '@global/composables/useConfig';

		/*
		*	Usage:
		*	<btn label="Click" to="index" :params="{id:1}"/>
		*/


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			to:			{ type:String },						// route name to navigate to
			params:		{ type:Object, default: undefined },	// route params
			query: 		{ type:Object, default: undefined },	// route query
			path:		{ type:String },						// path to navigate instead of route name
			href:		{ type:String, default: undefined },	// external link to navigate to
			label:		{ type:String, default: undefined }, 	// button label
			icon:		{ type:String, default: undefined },	// show an icon
			toggle: 	{ type:Boolean, default: false },		// toggle functionality if button
			disabled:	{ type:Boolean, default: false },		// disable button
			blocking:	{ type:Boolean, default: false },		// show loading indicator and disable click event
		});

		const emit = defineEmits(['click']);


		/////////////////////////////////
		// ICON
		/////////////////////////////////

		const { webContext } = useConfig();

		const iconPath = computed(() => `${webContext}/${props.icon}`);


		/////////////////////////////////
		// CLICK
		/////////////////////////////////

		const isActive = ref(false);
		const root = useTemplateRef('root');

		function onClick(event) {

			if(props.disabled) { return; }

			// remove focus
			root.value.blur();

			// blocking feature
			if(props.blocking && isLoading.value) { return; }
			else if(props.blocking) { isLoading.value = true; }

			// toggle feature
			if(props.toggle) { isActive.value = !isActive.value; }

			emit('click', isActive.value, root.value);
		};


		function setToggle(value) {

			isActive.value = value;
		}


		/////////////////////////////////
		// BLOCKING
		/////////////////////////////////

		const isLoading = ref(false);

		function setLoading(value) {

			isLoading.value = value;
		}

		defineExpose({setToggle,setLoading});


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

