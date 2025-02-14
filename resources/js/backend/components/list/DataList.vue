<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<data-list-navi :cols="cols" :options="options" v-model="filters" @search="loadSearch" @reload="loadData"/>

		<table ref="root" class="data-list">

			<!-- LAYOUT -->
			<colgroup>
				<col/>
				<col v-if="!cols.length"/>
				<col :class="col.type" v-for="col in cols"/>
				<col :style="'width:'+btnWidth+'px'"/>
			</colgroup>

			<thead>
				<tr>
					<th :class="['data-list-header-item',{'sortable':isSortable}]" @click="toggleDirection" data-property="name">
						<svg-item v-if="!isSortable" icon="backend/datalist-header-direction" class="data-list-header-direction"/>
						{{ t(options.nameLabel ?? 'Name') }}
					</th>
					<th :class="['data-list-header-item',col.type,{'sortable':isSortable}]" v-for="col in cols" @click="toggleDirection" :data-property="col.property">
						<svg-item v-if="!isSortable" icon="backend/datalist-header-direction" class="data-list-header-direction"/>
						{{ t(col.label) }}
					</th>
					<th class="data-list-header-item"></th>
				</tr>
			</thead>

			<tbody>

				<!-- LOADING -->
				<tr v-show="isLoading" class="data-list-item loading">
					<td :colspan="2+cols.length">
						<svg-item inline="backend/loading-datalist"/>
					</td>
				</tr>

				<!-- EMPTY -->
				<tr v-show="!isLoading && !items?.length" class="data-list-item empty">
					<td :colspan="2+cols.length">
						{{ t("Noch keine Elemente vorhanden") }}
					</td>
				</tr>

			</tbody>

			<!-- DATA -->
			<draggable
				item-key="id"
				v-model="items"
				:animation="200"
				tag="tbody"
				:disabled="!isSortable || isLoading || !items?.length"
				draggable=".data-list-item"
				handle=".data-list-btn.sort"
				@change="submitSort"
			>
				<template #item="{element}">
					<data-list-item
						:item="element"
						:cols="cols"
						:key="element.id"
						:exclude="exclude"
						@edit="id => $emit('edit',id)"
						@deleted="onDeleted"
					/>
				</template>
			</draggable>

		</table>

		<data-list-paginator v-if="isPaginator" :page="page" :count="pageCount" @update="onPaginatorClick"/>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, onMounted, onBeforeUnmount, nextTick, provide, computed, watch } from 'vue';
		import { useRouter, useRoute } from 'vue-router';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useApi } from '@global/composables/useApi';

		import draggable from 'vuedraggable';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = useLanguage();
		const { apiGetResponse, apiPostResponse, apiPost } = useApi();

		const props = defineProps({
			modelValue:		{ type: [Array,Object], default() { return []; } },		// bind variable to v-model
			options:		{ type: Object, default() { return {}; } },
			cols:			{ type: Array, default() { return []; } },
			exclude:		{ type: Array, default() { return []; } }, 		// array of items to exclude for editing
		});

		const emit = defineEmits(['update:modelValue', 'edit', 'deleted', 'sorted']);

		const root = useTemplateRef('root');

		onMounted(()=> {

			loadData();

			window.addEventListener("resize",onResize);
			setTimeout(onResize, 250);
		});

		onBeforeUnmount(()=> {

			window.removeEventListener("resize",onResize);
		});

		provide('options', props.options);


		/////////////////////////////////
		// LOAD
		/////////////////////////////////

		const items = ref([]);
		const isLoading = ref(true);
		const filters = ref({});

		defineExpose({items});

		function loadData() {

			// save current table height
			if(items.value.length > 0) {
				const tr = u(root.value).find('.data-list-item:not(.loading)');
				const h = tr.outerHeight() + (tr.length - 2) * 2;		// height and subtract border-spacing
				gsap.set(u(root.value).find('.data-list-item.loading').first(), { height: h });
			}

			isLoading.value = true;
			items.value = [];

			// use local data
			if(!props.options.routes?.load) {
				return updateLocalData();
			}

			// update filters
			filters.value = props.options.routes.loadParams ?? {};
			filters.value.page = page.value,
			filters.value.range = props.options.range,
			filters.value.direction = direction.value;
			filters.value.direction_property = directionProperty.value;


			// find correct api http method
			const route = Ziggy.routes[props.options.routes.load];
			const apiCall = route.methods.includes('GET') ? apiGetResponse : apiPostResponse;

			apiCall(props.options.routes.load, filters.value, response => {

				isLoading.value = false;
				items.value = response.data.data;
				emit('update:modelValue', response.data.data);

				updatePaginator(response.data);
				setTimeout(onResize, 250);
			});
		}

		function updateLocalData() {

			items.value = props.modelValue;

			// force array for items
			if(items.value && !Array.isArray(items.value)) {
				items.value = [items.value];
				emit('update:modelValue', items.value);
			}

			isLoading.value = false;
			setTimeout(onResize, 250);
		};

		watch(() => props.modelValue, updateLocalData, { deep: true });


		/////////////////////////////////
		// SEARCH
		/////////////////////////////////

		const isActiveSearch = ref(false);

		function loadSearch() {

			const value = filters.value.search;

			// reset search
			if(value.length == 0) {
				isActiveSearch.value = false;
				page.value = 1;
				return loadData();
			}

			// reset paginator
			if(!isActiveSearch.value) { page.value = 1; }

			isLoading.value = true;
			items.value = [];
			isActiveSearch.value = true;

			const data = {
				page: page.value,
				range: props.options.range,
				direction: direction.value,
				direction_property: directionProperty.value,
				value,
			};

			apiPostResponse(props.options.routes.search, data, response => {

				items.value = response.data.data;
				isLoading.value = false;

				updatePaginator(response.data);

				// reset buttons width
				u(root.value).find('col:last-child').attr('style', null);
				setTimeout(onResize, 250);
			});
		}


		/////////////////////////////////
		// SORT
		/////////////////////////////////

		const isSortable = computed(()=> props.options.routes?.sort != null || props.options.sortLocal);

		function submitSort() {

			if(props.options.sortLocal) {
				emit("update:modelValue", items.value);
				emit("sorted", items.value);
				return;
			}

			var form = {items: {}};
			items.value.map((item,i) => form.items[item.id] = i);

			apiPost(props.options.routes.sort, form);
		}


		/////////////////////////////////
		// DELETED
		/////////////////////////////////

		function onDeleted(id) {

			items.value = items.value.filter(item => item.id != id);

			if(props.options.deleteLocal) {
				emit("update:modelValue", items.value);
				emit("deleted", id);
				return;
			}

			isPaginator.value ? onPaginatorClick(page.value) : loadData();
		}


		/////////////////////////////////
		// PAGINATOR
		/////////////////////////////////

		const router = useRouter();
		const route = useRoute();

		const page = ref(route.query.page || 1);
		const pageCount = ref(1);
		const isPaginator = ref(false);

		function updatePaginator(data) {

			if(!data.paginator) { return; }

			// update paginator
			page.value 			= data.currentPage
			pageCount.value 	= data.pages;
			isPaginator.value 	= data.pages > 1;

			// check for invalid page
			if(page.value > pageCount.value) {
				page.value = pageCount.value;
				router.push({ query: { page: page.value } });
				loadData();
			}
		}


		function onPaginatorClick(newPage) {

			// update route
			router.push({ query: { page: newPage } });

			// update data
			page.value = newPage;

			isActiveSearch.value ? loadSearch() : loadData();
		}


		/////////////////////////////////
		// DIRECTION
		/////////////////////////////////

		const direction = ref(null);
		const directionProperty = ref(null);

		function toggleDirection(e) {

			if(isSortable.value) { return; }

			// get property from cell
			direction.value = e.target.classList.contains('asc') ? 'desc' : 'asc';
			directionProperty.value = e.target.dataset.property;

			// update classes
			u(root.value).find('.data-list-header-item').removeClass('asc desc');
			e.target.classList.add(direction.value);

			isActiveSearch.value ? loadSearch() : loadData();
		}


		/////////////////////////////////
		// BUTTONS
		/////////////////////////////////

		var buttonCount = 0;
		if(props.options.routes?.edit) { buttonCount++; }
		if(props.options.customEdit) { buttonCount++; }
		if(props.options.routes?.preview) { buttonCount++; }
		if(props.options.routes?.sort || props.options.sortLocal) { buttonCount++; }
		if(props.options.routes?.delete || props.options.deleteLocal) { buttonCount++; }

		const btnWidth = 30 * buttonCount + 10;


		/////////////////////////////////
		// RESIZE
		/////////////////////////////////

		function onResize() {

			// if no cols are defined, first col with 100%
			if(!props.cols.length) {
				gsap.set(u(root.value).find('col').first(), { width: '100%' });
			}
		}


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
				"Ende": "End",
				"Noch keine Elemente vorhanden": "No elements available yet",
				"Geprüft": "Checked",
				"Blockiert": "Blocked",
			}
		}
	</i18n>
