<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<!-- WITH LINK -->
		<router-link v-bind="$attrs" v-if="to || path" :to="to?link(to,params):{path}" :class="['footer-navi-item',{active:isActive}]">
			{{ t(label) }}
		</router-link>

		<!-- EXTERNAL LINK -->
		<a v-else-if="href" v-bind="$attrs" :class="['footer-navi-item',{active:isActive}]" :href="href" target="_blank" rel="noopener noreferrer">
			{{ t(label) }}
		</a>

		<!-- NO LINK -->
		<div v-else v-bind="$attrs" :class="['footer-navi-item',{active:isActive}]" @click="$emit('click')">
			{{ t(label) }}
		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { computed } from 'vue';
		import { useRoute } from 'vue-router'


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			to:			{ type:String },						// route name to navigate to
			params:		{ type:Object, default: undefined },	// route params
			path:		{ type:String },						// path to navigate instead of route name
			href:		{ type:String, default: undefined },	// external link to navigate to
			label:		{ type:String, required: true }, 		// button label		});
		});


		const emit = defineEmits(['click']);
		const route = useRoute();


		/////////////////////////////////
		// ACTIVE
		/////////////////////////////////

		const isActive = computed(() => route?.name.startsWith(props.to) && route.params?.slug == props.params?.slug );


	</script>


