<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="cols product-teaser">

			<!-- COL LEFT -->
			<component :is="user && !hasProduct ?'a':'router-link'" class="col-50" :to="user?productUrl:checkoutUrl" :href="user?checkoutUrl:null">
				<lazy-picture :file="product.teaser_image" :width="800" :height="600"/>
			</component>

			<!-- COL RIGHT -->
			<div class="col-50">
				<h3>{{ product.stripe_name }}</h3>
				<div v-html="product.teaser_description"></div>
				<p class="product-teaser-price">{{ product.stripe_price_value }}</p>

				<!-- BUY/SHOW -->
				<btn :label="t('In Bearbeitung')" v-if="userProduct && userProduct.status != 'succeeded'" class="secondary disabled"/>
				<btn :label="t('Ansehen')" v-else-if="userProduct" to="product" :params="{slug:product.slug}"/>
				<component :is="user && !userProduct ?'a':'router-link'" class="btn" v-else :to="user?productUrl:checkoutUrl" :href="user?checkoutUrl:null">
					{{ t('Jetzt kaufen') }}
				</component>
			</div>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, computed } from 'vue';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useConfig } from '@global/composables/useConfig';
		import { useUser } from '@global/composables/useUser';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({
			product: { type: Object, required: true }
		});


		/////////////////////////////////
		// PRODUCT
		/////////////////////////////////

		const { t, activeLang } = useLanguage();
		const { basePath } = useConfig();
		const { user, getUserProduct } = useUser();

		const userProduct = computed(() => getUserProduct(props.product.id));

		const checkoutUrl = computed(() => {
			const url = basePath+activeLang.value+'/stripe/checkout/'+props.product.type+'/'+props.product.id;
			return user.value ? url	: {name:'login',query:{redirect:url}};
		});

		const productUrl = computed(() => ({name:'product',params:{slug:props.product.slug}}) );


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
				"In Bearbeitung": "processing",
				"Ansehen": "view",
				"Jetzt kaufen": "buy now",
			}
		}
	</i18n>
