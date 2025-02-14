<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="filter-slider" ref="root">
			<div class="filter-slider-background"> </div>
			<button :id="getItemValue(item)+uid" class="btn filter-slider-item" v-for="item in items" @click="update(item)">
				{{ getItemLabel(item) }}
			</button>
		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, onMounted, watch, nextTick, getCurrentInstance } from 'vue';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useBreakpoints } from '@global/composables/useBreakpoints';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			items: { type: Array, required: true, },
			active: { type: String, default: undefined, },
		});

		const uid = getCurrentInstance().uid;
		const emit = defineEmits(['update']);
		const { t } = useLanguage();
		const { isTablet } = useBreakpoints();


		/////////////////////////////////
		// CLICK
		/////////////////////////////////

		const root = useTemplateRef('root');
		const activeItem = ref(props.active);

		function update(item) {

			activeItem.value = typeof item == 'string' ? item : getItemValue(item);

			// update active item
			const target = u('#'+activeItem.value+uid).first();
			u(root.value).find('.filter-slider-item').removeClass('active background');
			u(target).addClass('active');

			// animation
			const background = u(root.value).find('.filter-slider-background').first();
			const anim = gsap.timeline();
			anim.set(background, { display:'block'});
			anim.to(background, {
				duration: 0.2,
				width: target.offsetWidth,
				x: target.offsetLeft,
				ease: 'power2.inOut',
				onComplete: () => u(target).addClass('background')
			})
			anim.set(background, { display: 'none' }, '+=0.17');

			emit('update', activeItem.value);
		}

		onMounted(() => {
			if(props.active) { update(props.active); }
		});

		watch(isTablet, () => {
			setTimeout(()=> update(activeItem.value),150)
		});

		function getItemValue(item) { return Object.values(item)[0]; }
		function getItemLabel(item) { return t(Object.keys(item)[0]); }

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
			}
		}
	</i18n>

