<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="data-list-navi" v-if="options.routes?.search">

			<!-- SEARCH -->
			<div class="data-list-navi-search" v-if="options.routes?.search">
				<svg-item icon="backend/datalist-header-search"/>
				<input
					id="search"
					v-model="value.search"
					maxlength="20"
					class="data-list-navi-search"
					type="text"
					autoComplete="off"
					@keyup="search"
				/>
			</div>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, watch } from 'vue';
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = useLanguage();

		const props = defineProps({
			modelValue:		{ type: Object, required: true },				// bind variable to v-model
			options: 		{ type: Object, default() { return {}; } },
			cols:			{ type: Array, default() { return []; } },
		});

		const emit = defineEmits(['search','reload']);


		/////////////////////////////////
		// VALUE
		/////////////////////////////////

		const value = ref(Object.assign({ search: '', }, props.modelValue));

		watch(() => props.modelValue, newValue => value.value = newValue);


		/////////////////////////////////
		// SEARCH
		/////////////////////////////////

		var searchTimer = null;

		function search() {

			// emit debounced event
			if(value.value.search?.length >2) {
				clearTimeout(searchTimer);
				searchTimer = setTimeout(()=> value.value.search?.length>2?emit('search'):null, 200);
			}
			// reset to original data
			else if(value.value.search?.length==0) { emit('search'); }
		}


	</script>


