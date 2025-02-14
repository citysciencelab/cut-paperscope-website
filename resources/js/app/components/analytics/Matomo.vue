<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template></template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, watch } from 'vue';
		import { useConfig } from '@global/composables/useConfig';
		import { useRoute } from 'vue-router';
		import Cookies from 'js-cookie';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { baseUrl, isLocal } = useConfig();

		const initialized = ref(false);
		const hasAnalyticsCookie = ref(!!Cookies.get('confirmed_cookie_analytics'));

		if(hasAnalyticsCookie.value) { init(); } else { window._paq = []; }

		function init() {

			const matomoURL = import.meta.env.VITE_MATOMO_URL;

			// skip if in local environment and dev server
			if(isLocal || !matomoURL) {window._paq = []; return; }
			else if(baseUrl.includes('dev.hello-nasty.com')) {window._paq = []; return; }

			var _paq = window._paq = window._paq || [];
			_paq.push(['trackPageView']);
			_paq.push(['enableLinkTracking']);

			(function() {
				var u="//" + matomoURL.split('://').pop() + "/";
				_paq.push(['setTrackerUrl', u+'matomo.php']);
				_paq.push(['setSiteId', '1']);
				var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
				g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
			})();

			initialized.value = true;
		}


		/////////////////////////////////
		// ROUTE
		/////////////////////////////////

		const route = useRoute();

		watch(() => route.fullPath, (to,from) => {

			if(!initialized.value) { return; }

			// wait for data on new page to be loaded
			setTimeout(()=>{
				if(from) { _paq.push(['setReferrerUrl', from]); }
				_paq.push(['setCustomUrl', to]);
				_paq.push(['setDocumentTitle', document]);
				_paq.push(['deleteCustomVariables', 'page']);
				_paq.push(['trackPageView']);
			}, 1000);
		});


	</script>


