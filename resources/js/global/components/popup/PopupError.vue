<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div v-if="hasError" class="popup-error" @click="close">
			<svg-item class="popup-error-close" :icon="webContext+'/close'"/>
			<p>{{ t("message") + errorCode }}</p>
		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { computed, watch } from 'vue';
		import { useRoute } from 'vue-router';
		import { useGlobalStore } from '@global/stores/GlobalStore';
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// STORE
		/////////////////////////////////

		const globalStore = useGlobalStore();
		const { t } = useLanguage();

		const hasError = computed(() => globalStore.error != null);
		const errorCode = computed(() => globalStore.error?.status);


		/////////////////////////////////
		// CLOSE
		/////////////////////////////////

		const route = useRoute();
		watch(() => route.name, () => close());

		function close() {

			globalStore.setError(null);
		}


	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
				"message": "Es ist ein unerwarteter Fehler aufgetreten. Code ",
			},
			"en": {
				"message": "An unexpected error occurred. Code ",
			}
		}
	</i18n>
