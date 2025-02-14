<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="language-select">

			<div ref="root" class="language-select-content">
				<button
					v-if="!dropdown"
					class="header-navi-item language-select-item active"
					@click="toggleDropdown"
				>
					{{ activeLang }}
				</button>
				<button
					:class="['header-navi-item', 'language-select-item',{'active':item==activeLang}]"
					v-for="item in availableLangs"
					@click="updateLang(item)"
					:aria-label="t('Sprache ändern in ') + t('language.'+item)"
				>
					{{ item }}
				</button>
			</div>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, computed, onMounted } from 'vue';
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { langs, activeLang, setLanguage } = useLanguage();
		const availableLangs = computed(() => langs.filter(lang => props.dropdown ? true: lang !== activeLang.value));

		const props = defineProps({
			dropdown: {type: Boolean, default: false},
		});


		/////////////////////////////////
		// LANGUAGE
		/////////////////////////////////

		function updateLang(lang) {

			setLanguage(lang);

			// force close
			anim.reversed(true).resume();
			isOpen.value = false;
		}


		/////////////////////////////////
		// ANIMATION
		/////////////////////////////////

		var anim = null;
		const root = useTemplateRef('root');
		const isOpen = ref(false);

		onMounted(initAnimation);


		function initAnimation() {

			anim = gsap.timeline({ paused:true });
			anim.to(root.value, { duration:0.3, height:'auto', ease:'power2' });
		}


		function toggleDropdown() {

			anim.reversed(isOpen.value).resume();
			isOpen.value = !isOpen.value;
		}


	</script>


