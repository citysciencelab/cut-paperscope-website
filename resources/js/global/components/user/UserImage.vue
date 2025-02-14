<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<component :is="linkProfile ? 'router-link' : 'div'" :to="linkProfile ? {name:'user'} : null" class="user-image">
			<img ref="root" :class="{'lazy':imageMR}" :data-src="imageMR" :src="imageLR ?? imageFallback" :alt="t('Profilbild')"/>
		</component>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, computed, onMounted, watch } from 'vue';
		import { useUser } from '@global/composables/useUser';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useLazyload } from '@global/composables/useLazyload';
		import { useConfig } from '@global/composables/useConfig';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			target: { type: Object, default: null },
			linkProfile: { type: Boolean, default: false },
		});


		const { user } = useUser();
		const { t } = useLanguage();
		const { updateLazyload, resetLazyload } = useLazyload();
		const { baseUrl } = useConfig();

		const root = useTemplateRef('root');
		const imageHR = computed(() => props.target ? props.target.image : user.value?.image);
		const imageMR = computed(() => imageHR.value ? imageHR.value.replace('-hr.jpg', '-mr.jpg') : null);
		const imageLR = computed(() => imageHR.value ? imageHR.value.replace('-hr.jpg', '-lr.jpg') : null);
		const imageFallback = baseUrl + "img/global/preloader/user-image.png";

		onMounted(() => updateLazyload());
		watch(() => props.target?.image, () => resetLazyload(root.value) );

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
				"Profilbild": "Profile picture"
			}
		}
	</i18n>

