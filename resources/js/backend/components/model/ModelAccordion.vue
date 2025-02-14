<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<section :class="['content','model-accordion',{'open':isOpen}]">

			<div ref="header" class="model-accordion-header" @click="toggle">
				<h4>
					{{ t(label) }}
					<svg-item icon="backend/accordion"/>
				</h4>
			</div>

			<div ref="content" class="cols model-accordion-content">
				<slot></slot>
			</div>

			<div ref="close" class="model-accordion-close" @click="toggle">
				<svg-item icon="backend/accordion"/>
			</div>

		</section>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, onMounted } from 'vue';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			label: { type:String, default:'' },
			open: { type:Boolean, default:true },
		});


		/////////////////////////////////
		// ANIMATION
		/////////////////////////////////

		const header = useTemplateRef('header');
		const content = useTemplateRef('content');
		const close = useTemplateRef('close');
		const isOpen = ref(props.open);

		var anim = null;

		onMounted(initAnim);

		function initAnim() {

			anim = gsap.timeline({paused:true, onReverseComplete:(e) => anim.invalidate(), onComplete:onComplete});
			anim.fromTo(header.value, {autoAlpha:1}, {duration:0.5, autoAlpha: 0, ease:'power2.inOut'}, 0.0);
			anim.fromTo(content.value, {height:0}, {duration:0.6, height:'auto', ease:'power2.inOut'}, 0.2);
			anim.fromTo(close.value, {autoAlpha:0}, {duration:0.3, autoAlpha: 1, ease:'power2.inOut'}, 0.6);

			// revert this animation
			if(isOpen.value) { anim.time(0.8).play(); }
			else { content.value.style.overflow = 'hidden'; }
		}


		function toggle() {

			isOpen.value = !isOpen.value;
			if(!isOpen.value) { content.value.style.overflow = 'hidden'; }
			anim.reversed(!isOpen.value).resume();
		}


		function onComplete() {

			anim.invalidate();
			content.value.style.overflow = 'visible';
		}


	</script>


