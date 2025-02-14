<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<Teleport v-if="isOpen" to="body">

			<div v-bind="$attrs" ref="root" :class="['popup',{'no-close':noClose}]" role="dialog" :aria-label="t('Popup mit Detail-Funktionen')">

				<button
					v-if="!noClose"
					class="popup-close"
					:aria-label="t('button.close')"
					@keydown.enter="close"
				>
					<svg-item :icon="webContext+'/close'" @click="close"/>
				</button>

				<scroller class="popup-content">
					<slot></slot>
				</scroller>

				<div class="popup-footer" v-if="hasButtonsSlot">
					<div class="form-row-buttons">
						<slot name="buttons"></slot>
					</div>
				</div>

			</div>

		</Teleport>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, onMounted, nextTick, computed, useSlots } from 'vue';
		import { useGlobalStore } from '@global/stores/GlobalStore';
		import { useLazyload } from '@global/composables/useLazyload';
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			autoOpen:	{ type:Boolean, default: false },
			noClose:	{ type:Boolean, default: false },
			lightbox:	{ type:Boolean, default: false },
		});

		const emit = defineEmits(['mounted', 'open', 'close']);

		onMounted(() => {
			if(props.autoOpen) { open(); }
			emit('mounted');
		});

		const store = useGlobalStore();
		const slots = useSlots();
		const hasButtonsSlot = computed(() => { return !!slots.buttons; });

		const { updateLazyload } = useLazyload();
		const { t } = useLanguage();


		/////////////////////////////////
		// OPEN
		/////////////////////////////////

		const isOpen = ref(false);
		const root = useTemplateRef('root');

		async function open() {

			// update state
			isOpen.value = true;
			await nextTick();

			// lightbox
			if(props.lightbox) {
				store.setLightbox(true);
				setTimeout(() => u('.lightbox').first().addEventListener('click', close), 200);
			}

			// animation
			gsap.from(root.value, {
				duration: 0.5,
				y:"-=10",
				opacity: 0,
				scale: 0.8,
				ease: 'power3.out',
				clearProps: 'all',
				delay: 0.25,
				onComplete: setFocus,
			});

			// events
			updateLazyload();
			document.addEventListener('keydown', closeOnEsc);
			emit('open');
		}


		/////////////////////////////////
		// CLOSE
		/////////////////////////////////

		function closeOnEsc(e) {

			if(e.key == 'Escape') { close(e); }
		}

		function close(e) {

			if(!isOpen.value) { return; }

			restoreFocus(e);
			document.removeEventListener('keydown', closeOnEsc);

			gsap.to(root.value, {duration: 0.2, y:"-=10", opacity: 0, scale: 0.8, ease: 'power3.out', clearProps: 'all', onComplete: () => {
				isOpen.value = false;
				if(props.lightbox) { store.setLightbox(false); }
				emit('close');
			}});

			if(props.lightbox && u('.lightbox').first()) {
				u('.lightbox').first().removeEventListener('click', close);
			}
		}


		/////////////////////////////////
		// ACCESSIBILITY
		/////////////////////////////////

		var activeElement = null;

		function setFocus() {

			if(props.noClose) {
				activeElement = document.activeElement;
				u(root.value).find('.popup-content').first()?.focus();
			}
			else {
				activeElement = document.activeElement;
				u(root.value).find('.popup-close').first()?.focus();
			}
		}

		function restoreFocus(e) {

			// is KeyboardEvent
			if(activeElement && (e?.type == 'keydown' || e?.pointerType == '')) {
				activeElement.focus();
			}
		}


		defineExpose({open, close});


	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
				"button.close": "Fenster schließen",
			},
			"en": {
				"Popup mit Detail-Funktionen": "Popup with detail functions",
				"button.close": "Close popup",
			}
		}
	</i18n>

