<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="data-list-paginator">

			<!-- FIRST -->
			<div class="data-list-paginator-item" v-if="firstIndex!=1" @click="onItemClicked(1)">
				1
			</div>
			<div class="data-list-paginator-item first" v-if="firstIndex!=1">
				...
			</div>

			<!-- RANGE -->
			<div :class="['data-list-paginator-item',{active:firstIndex + item - 1 == page}]" v-for="item in (lastIndex-firstIndex+1)" @click="onItemClicked(firstIndex + item - 1)">
				{{ firstIndex + item - 1 }}
			</div>

			<!-- LAST -->
			<div class="data-list-paginator-item last" v-if="lastIndex<count">
				...
			</div>
			<div class="data-list-paginator-item" v-if="lastIndex<count" @click="onItemClicked(count)">
				{{ count }}
			</div>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { computed } from 'vue';


		/////////////////////////////////
		// INIT
		/////////////////////////////////


		const props = defineProps({
			page: { type: Number, required: true },
			count: { type: Number, required: true },
		});

		const emit = defineEmits(['update']);


		/////////////////////////////////
		// RANGE
		/////////////////////////////////

		const indexRange = 2;
		const firstIndex = computed(() => props.page - indexRange > 1 ? props.page - indexRange : 1)
		const lastIndex = computed(() => props.page + indexRange < props.count ? props.page + indexRange : props.count)


		/////////////////////////////////
		// EVENTS
		/////////////////////////////////

		function onItemClicked(page) { emit('update',page); }


	</script>


