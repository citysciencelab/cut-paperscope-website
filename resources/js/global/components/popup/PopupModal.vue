<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<Teleport v-if="isOpen" to="body">

			<div ref="root" class="popup modal" role="dialog" aria-modal="true">

				<button
					class="popup-close"
					:aria-label="t('button.close')"
					@keydown.enter="close"
				>
					<svg-item :icon="webContext+'/close'" @click="close"/>
				</button>

				<scroller class="popup-content">
					<slot>
						<h4 class="modal-title">{{ modalData.title }}</h4>
						<p :class="['modal-copy',{error:modalData.alert}]">{{ modalData.copy }}</p>
					</slot>
				</scroller>

				<div class="popup-footer">
					<div class="form-row-buttons">
						<btn label="Abbrechen" class="small secondary" @click="close"/>
						<btn :label="modalData.confirmLabel ?? 'Speichern'" class="small btn-confirm" @click="confirm"/>
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

		import { ref, useTemplateRef, nextTick } from 'vue';
		import { useGlobalStore } from '@global/stores/GlobalStore';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const modalData = ref({});


		/////////////////////////////////
		// OPEN
		/////////////////////////////////

		const store = useGlobalStore();
		const isOpen = ref(false);
		const root = useTemplateRef('root');

		async function open(data) {

			// update state
			isOpen.value = true;
			modalData.value = data;
			await nextTick();

			// animation
			store.setLightbox(true);
			gsap.from(root.value, {duration: 0.5, y:"-=10", opacity: 0, scale: 0.8, ease: 'power3.out', clearProps: 'all'});
		}


		/////////////////////////////////
		// CONFIRM
		/////////////////////////////////

		function confirm() {

			if(modalData.value.callback) { modalData.value.callback(); }

			close();
		}


		/////////////////////////////////
		// CLOSE
		/////////////////////////////////

		function close() {

			store.setLightbox(false);
			gsap.to(root.value, {duration: 0.2, y:"-=10", opacity: 0, scale: 0.8, ease: 'power3.out', clearProps: 'all', onComplete: () => {
				modalData.value = {};
				isOpen.value = false;
			}});
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
				"button.close": "Close popup",
			}
		}
	</i18n>

