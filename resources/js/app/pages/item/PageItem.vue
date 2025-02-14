<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<section class="content">

			<filter-slider :items="itemsFilter" active="newest" @update="onFilterUpdate"/>
			<p v-for="item in items" class="item">
				{{ item.title }}
			</p>
			<paginator-scroller :paginator @update="onPaginatorUpdate"/>

		</section>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref } from 'vue';
		import { usePage } from '@global/composables/usePage';
		import { useApi } from '@global/composables/useApi';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = usePage("Items");
		const { apiGet } = useApi();


		/////////////////////////////////
		// DATA
		/////////////////////////////////

		const items = ref([]);

		function loadItems() {

			const data = {
				...filters.value,
				range: 15,
				page: paginator.value.currentPage ?? 1,
			};

			apiGet('item.list', data, (data, pag) => {
				items.value = paginator.value.currentPage == 1 ? data : items.value.concat(data); 	// push() not working with reactivity
				paginator.value = pag;
			});
		}


		/////////////////////////////////
		// FILTER
		/////////////////////////////////

		const paginator = ref({});
		const filters = ref({});

		const itemsFilter = [
			{'Neueste': 'newest'},
			{'Älteste': 'oldest'},
		];


		function onFilterUpdate(newOrder) {

			filters.value.order = newOrder;
			paginator.value.currentPage = 1;
			loadItems();
		}

		function onPaginatorUpdate(newPage) {

			paginator.value.currentPage = newPage;
			loadItems();
		}


	</script>


