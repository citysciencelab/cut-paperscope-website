<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div :class="['form-row', rowId]">

			<input-label :label="$attrs.label" :info="$attrs.info" v-bind="labelAttrs"/>

			<div :class="['input-select',{'error':showError},{'focus':hasFocus},{'open':isOpen},{'disabled':readonly},{'floating':yOffset!=0}]">

				<!-- NATIVE SELECT -->
				<svg-item :icon="webContext+'/input-select'"/>
				<select
					:id="inputId"
					:name="id"
					v-model="value"
					@focus="onFocus"
					@blur="hasFocus=false"
					@input="updateNative"
					:disabled="readonly"
				>
					<option v-if="placeholder" :value="null">{{ t(placeholder) }}</option>
					<option v-for="opt in options" :value="getValue(opt)">{{ t(getLabel(opt)) }}</option>
				</select>

				<!-- CUSTOM SELECT -->
				<div class="input input-select-custom" @click="toggle" :aria-hidden="!isOpen">
					<svg-item :icon="webContext+'/input-select'"/>
					<p>{{ t(activeLabel ?? placeholder ?? '' ) }}</p>
				</div>
				<div ref="popup" class="input-select-popup" v-show="isOpen">
					<scroller>
						<p v-if="placeholder" :class="['input-select-option',{'active':!activeLabel}]" :data-value="null" @click="update">
							<svg-item :icon="webContext+'/input-select-check'"/>
							{{ t(placeholder) }}
						</p>
						<p :class="['input-select-option',{'active':activeLabel==getLabel(opt)}]" v-for="opt in options" :data-value="getValue(opt)" @click="update">
							<svg-item :icon="webContext+'/input-select-check'"/>
							{{ t(getLabel(opt)) }}
						</p>
					</scroller>
				</div>

			</div>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, computed, nextTick } from 'vue';
		import { useInput } from '@global/composables/useInput';


		/*
		*	Usage:
		*	<input-select label="Click" id="username" v-model="myVar" options="[ {'Label':123}, ... ]"/>
		*/


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			modelValue:		{ type: [ String, Number, Object ] },					// bind variable to v-model
			error: 			{ default: null },										// form data to show error
			options: 		{ type: Array, required: true }, 						// array of objects with label and value

			id:				{ type: String, default: null }, 						// unique form id for this input element
			placeholder: 	{ type: String, default: 'Bitte auswählen' },			// show a placeholder text input element
			lang: 			{ type: String, default: undefined },

			// html
			readonly: 		{ type: Boolean },										// html readonly attribute
			autofocus: 		{ type: Boolean },										// html autofocus attribute
			required: 		{ type: Boolean },										// html required attribute (show asterisk)
		});

		const emit = defineEmits(['update:modelValue']);

		const { modelIsObject, value, rowId, inputId, propId, showError, updateInput, removeError, labelAttrs } = useInput(props, emit);


		/////////////////////////////////
		// NATIVE SELECT
		/////////////////////////////////

		const hasFocus = ref(false);


		const activeLabel = computed(() => {

			const option = props.options.find(option => getValue(option) == value.value);
			return option ? getLabel(option) : null;
		});


		const getLabel = option => Object.keys(option)[0];
		const getValue = option => Object.values(option)[0];


		function onFocus() {

			hasFocus.value = true;
			if(isOpen.value) { close(); }

			removeError();
		}


		function updateNative(e) {

			value.value = e.target.value;
			updateInput();
		}


		/////////////////////////////////
		// CUSTOM SELECT
		/////////////////////////////////

		const isOpen = ref(false);
		const popup = useTemplateRef('popup');
		const yOffset = ref(0);
		const anim = ref(null);


		const toggle = () => isOpen.value ? close() : open();
		const clickOutside = e => !popup.value.contains(e.target) ? close() : null;


		async function open() {

			if(props.readonly) { return; }

			// update status
			removeError();
			await nextTick();

			isOpen.value = true;
			gsap.set(popup.value, {opacity:0});

			// wait for scroller component to be ready
			setTimeout(()=> {

				// detect click outside of popup
				document.addEventListener('click', clickOutside);

				// compare position of popup and app
				const rect = popup.value.getBoundingClientRect();
				const bottom = document.documentElement.scrollTop + rect.top + rect.height;
				const appHeight = u('#app').outerHeight();

				// apply negative offset if popup is outside of app
				yOffset.value = bottom > appHeight ? appHeight - bottom - 20 : 0;
				anim.value = gsap.fromTo(popup.value, {opacity:0, y:yOffset.value-10}, {opacity:1, y:yOffset.value, duration:0.15, ease:'power2.out'});
			},100);
		}


		function close() {

			// detect click outside of popup
			document.removeEventListener('click', clickOutside);

			// reset everything after close anim
			anim.value?.eventCallback('onReverseComplete', () => {
				anim.value?.kill();
				isOpen.value = false;
				yOffset.value = 0;
				u(popup.value).attr('style', null);
			})
			.reverse();
		}


		function update(e) {

			value.value = e.target.dataset.value;
			updateInput();
			close();
		}


	</script>


