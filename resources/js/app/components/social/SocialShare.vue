<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="social-share">

			<h4 class="social-share-title">{{ t("Seite teilen") }}:</h4>

			<social-share-item label="LinkedIn" :href="linkLinkedIn"/>
			<social-share-item label="X" :href="linkX"/>
			<social-share-item label="Facebook" :href="linkFacebook"/>
			<social-share-item label="Xing" :href="linkXing"/>
			<social-share-item label="Pinterest" :href="linkPinterest"/>
			<social-share-item label="WhatsApp" :href="linkWhatsApp"/>
			<social-share-item label="Mail" :href="linkMail"/>

			<button class="social-share-item" :aria-label="t('Link kopieren')" @click="copyToClipboard">
				<svg-item v-if="blocking" :inline="webContext+'/loading-api'" class="loading-icon"/>
				<svg-item v-else :icon="webContext+'/social-copy'"/>
			</button>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, computed } from 'vue';
		import { useRoute } from 'vue-router';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			item: 			{ type:Object, default: undefined },
			sharingText: 	{ type:String, default: '' },
			hashTags: 		{ type:String, default: '' },
		});

		const route = useRoute();


		/////////////////////////////////
		// URL
		/////////////////////////////////

		const url = computed(() => window.config.base_url + route.path.replace(/^(\/de?)/,"").substring(1));


		/////////////////////////////////
		// SOCIAL LINKS
		/////////////////////////////////

		const linkLinkedIn = computed(() => 'https://www.linkedin.com/shareArticle?mini=true&url='+url.value);
		const linkX = computed(() => 'https://twitter.com/intent/tweet?url='+url.value+'&text='+encodeText(props.sharingText)+'&hashtags='+encodeText(props.hashTags.replace('#','')) );
		const linkFacebook = computed(() => 'https://www.facebook.com/sharer/sharer.php?u='+url.value+'&quote='+encodeText(props.sharingText));
		const linkXing = computed(() => 'https://www.xing.com/spi/shares/new?url='+url.value);
		const linkPinterest = computed(() => 'https://pinterest.com/pin/create/button/?url='+url.value);
		const linkWhatsApp = computed(() => 'whatsapp://send?text='+url.value);
		const linkMail = computed(() => 'mailto:?body='+encodeText((props.sharingText?props.sharingText+'\n':'')+url.value).replaceAll('%5Cn','%0A'));

		const encodeText = content => encodeURIComponent(content);


		/////////////////////////////////
		// CLIPBOARD
		/////////////////////////////////

		const blocking = ref(false);

		const copyToClipboard = () => {

			blocking.value = true;
			navigator.clipboard.writeText(url.value);
			setTimeout(()=>{ blocking.value = false; }, 600);
		};

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
				"Seite teilen": "Share page",
				"Link kopieren": "copy link",
			}
		}
	</i18n>

