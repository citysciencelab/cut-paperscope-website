<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div :class="['accordion',{'open':isOpen}]">

			<!-- HEADER -->
			<div class="accordion-header" @click="toggle">
				<h4>{{ title }}</h4>
				<button class="accordion-header-icon" @click.stop="toggle" :aria-expanded="isOpen" :aria-controls="contentId">
					<svg-item :icon="webContext+'/accordion'"/>
				</button>
			</div>

			<!-- CONTENT -->
			<div ref="content" :id="contentId" class="accordion-content" :aria-hidden="!isOpen">
				<slot></slot>
			</div>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, onMounted, useTemplateRef, getCurrentInstance } from 'vue';
		import { useLazyload } from '@global/composables/useLazyload';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { updateLazyload } = useLazyload();

		const props = defineProps({
			title: 		{ type: String, required:true },
			duration: 	{ type: Number, default: 1 },
			open: 		{ type: Boolean },
		});

		const isOpen = ref(false);
		const contentId = 'accordion-content-' + getCurrentInstance().uid;


		/////////////////////////////////
		// ANIMATION
		/////////////////////////////////

		var anim = null;
		const content = useTemplateRef('content');

		function initAnimation() {

			anim = gsap.timeline({paused:true, onReverseComplete:(e) => anim.invalidate()});
			anim.fromTo(content.value,{display:'none'},{duration:0.1,display:'block'});
			anim.fromTo(content.value,{height:0},{duration:props.duration, height:'auto', ease:'power2.inOut'});

			if(props.open) {
				isOpen.value = true;
				anim.seek(anim.duration());
			}
		}

		onMounted(initAnimation);


		function toggle() {

			isOpen.value = !isOpen.value;
			anim.reversed(!isOpen.value).resume();

			if(isOpen.value) { updateLazyload(); }
		}


	</script>


