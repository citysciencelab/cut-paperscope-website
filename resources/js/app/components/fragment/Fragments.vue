<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<component
			v-for="fragment in items"
			:is="'fragment-'+fragment.template"
			:class="['fragment',{preview:isPreview && !fragment.public}]"
			:fragment="fragment"
			:key="fragment.id"
		/>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { computed, onMounted, nextTick } from 'vue';
		import { useRoute } from 'vue-router'
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const route = useRoute();
		const { activeLang } = useLanguage();

		const props = defineProps({
			items: {type: Array, required: true },
		});

		const isPreview = computed(() => !!route['qu'+'er'+''+'y'].pv);


		/////////////////////////////////
		// MEDIA
		/////////////////////////////////

		onMounted(() => embedMedia());

		async function embedMedia() {

			u('figure.media oembed').each(i=> {

				var url = u(i).attr('url');
				if (url.includes('twitter.com')) { embedTwitter(i,url); }
			});

			await nextTick();
			window.twttr?.widgets.load();
		}


		function embedTwitter(i,url) {

			// replace oembed element with twitter embed
			var out = '<blockquote class="twitter-tweet" data-lang="'+activeLang.value+'"><a href="'+url+'"></a></blockquote>';
			u(i).replace(out);
		}


	</script>


