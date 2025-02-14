<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="cols fragment-text-image" :data-id="fragment.id">

			<!-- TEXT -->
			<div class="col-50">
				<h2 v-if="fragment.content.title">{{ fragment.content.title }}</h2>
				<div v-if="fragment.content.copy" v-html="fragment.content.copy"></div>
			</div>

			<!-- IMAGE -->
			<div :class="['col-50',fragment.content.image_position]">

				<component
					:is="hasUrl? (hasExternalUrl?'a':'router-link'):'div'"
					:href="fragment.content.image_url"
					:target="hasExternalUrl?'_blank':null"
					:to="hasUrl && !hasExternalUrl ? link({path:fragment.content.image_url}) : null"
					:rel="hasExternalUrl?'noopener noreferrer':null"
				>
					<lazy-picture v-if="fragment.content.image" :file="fragment.content.image" :alt="fragment.content.image_alt"/>
				</component>

				<p v-if="fragment.content.image_subline" class="small fragment-image-label">{{ fragment.content.image_subline }}</p>

			</div>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { computed } from 'vue';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			fragment: { type: Object, required: true }
		});

		const hasUrl = computed(() => props.fragment.content.image_url ? true : false);
		const hasExternalUrl = computed(() => props.fragment.content.image_url?.includes('http'));


	</script>


