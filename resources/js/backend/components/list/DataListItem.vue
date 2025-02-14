<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<tr :class="['data-list-item',{'hidden': isHidden}, {'exclude': isExcluded}, {'expired-start': isExpiredStart}, {'expired-end': isExpiredEnd}]">

			<!-- NAME-->
			<td :colspan="cols.length>0?null:2">
				<router-link v-if="contentRoute && !isExcluded" :to="link(contentRoute, getRouteProps())">
					<img :src="props.item[options.imageProp]" class="data-list-item-image" v-if="options.imageProp" :alt="t('Vorschaubild')"/>
					{{ itemName }}
					<svg-item v-if="isHidden" icon="backend/datalist-hidden"/>
				</router-link>
				<template v-else>
					<img :src="props.item[options.imageProp]" class="data-list-item-image" v-if="options.imageProp" :alt="t('Vorschaubild')"/>
					{{ itemName }}
					<svg-item v-if="isHidden" icon="backend/datalist-hidden"/>
				</template>
			</td>

			<!-- COLS -->
			<td :class="['small',col.type, col.property.replace('_','-')]" v-for="col in cols">
				<svg-item v-if="(col.type=='bool' || col.type=='boolean')" :style="{visibility:getValue(col)?'visible':'hidden'}" class="data-list-item-icon" icon="backend/datalist-check"/>
				<template v-else>{{ getValue(col) }}</template>
			</td>

			<!-- BUTTONS -->
			<data-list-buttons :item="item" :exclude="isExcluded" @edit="id => emit('edit',id)" @deleted="id => emit('deleted',id)"/>

		</tr>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { inject, computed } from 'vue';
		import { useRouter } from 'vue-router';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useDate } from '@global/composables/useDate';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = useLanguage();

		const props = defineProps({
			item: { type: Object, required: true },
			cols: { type: Array, default() { return []; } },
			exclude: { type: Array, default() { return []; } }, 		// array of items to exclude for editing
		});

		const emit = defineEmits(['edit','deleted']);


		/////////////////////////////////
		// OPTIONS
		/////////////////////////////////

		const options = inject('options', {});
		const contentRoute = computed(()=> options.routes?.content);
		const itemName = computed(()=> options.nameProp ? props.item[options.nameProp] : props.item.name);


		/////////////////////////////////
		// VALUE
		/////////////////////////////////

		const { dateToTimestamp } = useDate();

		const isExpiredStart = props.item.published_start ? dateToTimestamp(props.item.published_start) > new Date() : false;
		const isExpiredEnd = props.item.published_end ? dateToTimestamp(props.item.published_end) < new Date() : false;
		const isHidden = props.item.public === false || props.item.blocked === true;
		const isExcluded = computed(()=> props.exclude.findIndex(i => i.id == props.item.id) != -1);

		function getValue(col) {

			var value = props.item[col.property];
			if(value == null) return '';

			// format by type
			switch(col.type) {
				// case 'test':
				// 	return value ? t('✓') : t('');
			}

			return value;
		}


		/////////////////////////////////
		// ROUTER
		/////////////////////////////////

		const router = useRouter();

		function getRouteProps() {

			const route = router.getRoutes().find( route => route.name == options.routes?.content );
			if(!route) return {};

			let params = {};
			if(route.path.includes(':id')) 		{ params.id = props.item.id };
			if(route.path.includes(':slug')) 	{ params.slug = props.item.slug };

			return { ...options.routes?.contentParams, ...params };
		}


	</script>


