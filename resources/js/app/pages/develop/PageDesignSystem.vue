<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<section class="content">
			<h1>
				Design System<br>
				Headline 1
			</h1>
		</section>


		<p class="design-system-separator">Typo</p>
		<section class="content">

			<h2>Headline 2 - Lorem ipsum dolor sit amet consectetur adipisicing elit.</h2>
			<p>
				Lorem ipsum dolor sit amet consectetur adipisicing elit. Suscipit, molestias ex. Praesentium nesciunt? Eveniet modi ut beatae provident
				<router-link :to="link('index')">Textlink</router-link>
				dolor reiciendis possimus laudantium suscipit blanditiis nobis officia architecto quod fugiat cum voluptatum, animi earum tempora repellat aliquid? Voluptatum, itaque architecto. Reprehenderit explicabo quidem minus voluptatibus! Maiores, soluta amet neque assumenda quas, ullam, architecto odio harum illo quibusdam aut. Obcaecati sapiente dolorem nisi eum architecto id, assumenda totam nemo enim explicabo impedit quae minima molestiae debitis minus repudiandae esse, ab doloribus sunt corporis quam sequi ullam. Officiis nulla amet fugit optio, aliquam iure debitis quae.
			</p>
			<ul>
				<li>Lorem ipsum dolor sit amet.</li>
				<li>Lorem, ipsum dolor.</li>
				<li>Lorem ipsum dolor sit amet consectetur adipisicing elit.</li>
			</ul>

			<h3>Headline 3 - Lorem ipsum dolor sit amet consectetur adipisicing elit.</h3>
			<p>
				Lorem ipsum dolor sit amet consectetur, adipisicing elit. Perferendis veniam repellendus voluptas delectus et, doloremque rem soluta repellat, illum laborum sequi voluptatem amet ut a quasi unde dignissimos natus temporibus! Eaque, quas? Corrupti officia nobis culpa harum temporibus reiciendis magnam iste maxime omnis beatae eius consequatur sed, eaque voluptates minus!
			</p>
			<table role="presentation">
				<tbody>
					<tr>
						<td>lorem ipsum</td><td>rferendis veniam repellendus vo</td><td>lorem ipsum</td>
					</tr>
					<tr>
						<td>lorem ipsum</td><td>lorem ipsum</td><td>voluptatem amet ut a quasi unde dig icia architecto quod fugiat cum voluptatum, a</td>
					</tr>
				</tbody>
			</table>

			<h4>Headline 4 - Lorem ipsum dolor sit amet consectetur adipisicing elit.</h4>
			<p>
				Lorem ipsum dolor sit, amet consectetur adipisicing elit. Perspiciatis ipsa dicta voluptate impedit earum tempora, corporis porro voluptatem rem deserunt necessitatibus inventore accusantium ducimus nam deleniti consequuntur aspernatur aliquam dolor.
			</p>
		</section>


		<p class="design-system-separator">Buttons</p>
		<section class="content">

			<h4>default</h4>
			<btn to="index" label="Internal Link"/>
			<btn href="https://www.hello-nasty.com" label="External Link"/>
			<btn :toggle="true" label="Html Button"/>
			<btn icon="close" label="Icon Button"/>
			<btn icon="close" href="https://www.hello-nasty.com"/>

			<h4>secondary</h4>
			<btn class="secondary" to="index" label="Internal Link"/>
			<btn class="secondary" href="https://www.hello-nasty.com" label="External Link"/>
			<btn class="secondary" :toggle="true" label="Html Button"/>
			<btn class="secondary" icon="close" label="Icon Button"/>
			<btn class="secondary" icon="close"/>

			<h4>cta</h4>
			<btn class="cta" to="index" label="Internal Link"/>
			<btn class="cta" href="https://www.hello-nasty.com" label="External Link"/>
			<btn class="cta" :toggle="true" label="Html Button"/>
			<btn class="cta" icon="close" label="Icon Button"/>
			<btn class="cta" icon="close"/>

			<h4>default small</h4>
			<btn class="small" to="index" label="Internal Link"/>
			<btn class="small" href="https://www.hello-nasty.com" label="External Link"/>
			<btn class="small" :toggle="true" label="Html Button"/>
			<btn class="small" icon="close" label="Icon Button"/>
			<btn class="small" icon="close"/>

			<h4>secondary small</h4>
			<btn class="secondary small" to="index" label="Internal Link"/>
			<btn class="secondary small" href="https://www.hello-nasty.com" label="External Link"/>
			<btn class="secondary small" :toggle="true" label="Html Button"/>
			<btn class="secondary small" icon="close" label="Icon Button"/>
			<btn class="secondary small" icon="close"/>

		</section>


		<p class="design-system-separator">Layout Cols (12 col grid)</p>
		<section class="content grid cols">

			<div class="col-100"><p>col-100</p></div>

			<div class="col-50"><p>col-50</p></div>
			<div class="col-50"><p>col-50</p></div>

			<div class="col-33-67"><p>col-33-67</p></div>
			<div class="col-67"><p>col-67</p></div>

			<div class="col-33"><p>col-33</p></div>
			<div class="col-33"><p>col-33</p></div>
			<div class="col-33"><p>col-33</p></div>

			<div class="col-67"><p>col-67</p></div>
			<div class="col-33-67"><p>col-33-67</p></div>

		</section>


		<p class="design-system-separator">Scroller</p>
		<section class="content grid cols">

			<scroller style="height:100px">
				<p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Eveniet sit consectetur tenetur non ad error repellendus, libero quasi culpa sed quidem eligendi quisquam accusantium vitae velit aut, aspernatur accusamus, dignissimos veniam</p>
			</scroller>

			<scroller style="height:150px">
				<p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Eveniet sit consectetur tenetur non ad error repellendus, libero quasi culpa sed quidem eligendi quisquam accusantium vitae velit aut, aspernatur accusamus, dignissimos veniam at iure sint. Nemo, ducimus. Qui, delectus. Cum placeat autem doloremque sed quia earum deleniti omnis ratione vel perspiciatis ad recusandae quas minima consectetur modi, ut nesciunt doloribus accusantium repudiandae nemo mollitia explicabo. Eaque laboriosam velit voluptatibus enim unde minus perspiciatis doloremque voluptatem cumque exercitationem cum iusto repellendus distinctio, deleniti pariatur! Quia atque nulla sapiente beatae molestiae facilis ut necessitatibus. Tempore, quidem molestiae! Sit vel sapiente numquam laboriosam iste!</p>
				<p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Eveniet sit consectetur tenetur non ad error repellendus, libero quasi culpa sed quidem eligendi quisquam accusantium vitae velit aut, aspernatur accusamus, dignissimos veniam at iure sint. Nemo, ducimus. Qui, delectus. Cum placeat autem doloremque sed quia earum deleniti omnis ratione vel perspiciatis ad recusandae quas minima consectetur modi, ut nesciunt doloribus accusantium repudiandae nemo mollitia explicabo. Eaque laboriosam velit voluptatibus enim unde minus perspiciatis doloremque voluptatem cumque exercitationem cum iusto repellendus distinctio, deleniti pariatur! Quia atque nulla sapiente beatae molestiae facilis ut necessitatibus. Tempore, quidem molestiae! Sit vel sapiente numquam laboriosam iste!</p>
			</scroller>

		</section>


		<p class="design-system-separator">Icons</p>
		<section class="content cols iconset">

			<p class="col-33">
				general<br>
				<svg-item icon="app/close"/>
			</p>
			<p class="col-33">
				content<br>
				<svg-item icon="app/accordion"/>
				<svg-item icon="app/slider-button"/>
			</p>
			<p class="col-33">
				form<br>
				<svg-item icon="app/input-checkbox"/>
				<svg-item icon="app/input-delete"/>
				<svg-item icon="app/input-radio"/>
				<svg-item icon="app/input-select-check"/>
				<svg-item icon="app/input-select"/>
			</p>
			<p class="col-33">
				social<br>
				<svg-item icon="app/social-copy"/>
				<svg-item icon="app/social-facebook"/>
				<svg-item icon="app/social-instagram"/>
				<svg-item icon="app/social-linkedin"/>
				<svg-item icon="app/social-mail"/>
				<svg-item icon="app/social-pinterest"/>
				<svg-item icon="app/social-whatsapp"/>
				<svg-item icon="app/social-x"/>
				<svg-item icon="app/social-xing"/>
				<svg-item icon="app/social-youtube"/>
			</p>
			<p class="col-33"></p>
			<p class="col-33"></p>

		</section>


		<p class="design-system-separator">Form</p>
		<section class="content cols">

			<!-- TEXT -->
			<div class="col-50">
				<input-text label="Label InputText" v-model="inputValueText" info="infotext" required/>
			</div>
			<div class="col-50">
				<input-text label="Label with error" v-model="inputValueText" :error="true" info="infotext" required/>
			</div>
			<div class="col-50">
				<input-text label="Label disabled Input" v-model="inputValueText" info="infotext" readonly/>
			</div>
			<div class="col-100 form-space"></div>

			<!-- TEXTAREA -->
			<div class="col-100">
				<input-textarea label="Label Textarea" v-model="lorem20" info="infotext" required/>
			</div>
			<div class="col-100 form-space"></div>

			<!-- SELECT -->
			<div class="col-50">
				<input-select label="Label Select" info="infotext" required :options="itemsSelect"/>
			</div>
			<div class="col-50">
				<input-select label="Label with error" info="infotext" :error="true" required :options="itemsSelect"/>
			</div>
			<div class="col-50">
				<input-select label="Label Select Large" info="infotext" required :options="itemsSelectLarge"/>
			</div>
			<div class="col-50"></div>
			<div class="col-100 form-space"></div>

			<!-- CHECKBOX / RADIO -->
			<div class="col-50">
				<input-checkbox label="Label Checkbox" info="infotext" required :options="itemsSelect" v-model="inputValueCheckbox"/>
			</div>
			<div class="col-50">
				<input-radio label="Label Radio" info="infotext" :options="itemsSelect" v-model="inputValueRadio"/>
			</div>
			<div class="col-100 form-space"></div>

			<!-- DATE / TIME -->
			<div class="col-50">
				<input-date-time label="Label DateTime" info="infotext" v-model="inputValueDateTime" required/>
			</div>
			<div class="col-50"></div>
			<div class="col-100 form-space"></div>

		</section>


		<p class="design-system-separator">Fragments</p>
		<section class="content">

			<component is="fragment-text" class="fragment" :fragment="fragmentText"/>
			<component is="fragment-text-image" class="fragment" :fragment="fragmentTextImageRight"/>
			<component is="fragment-text-image" class="fragment" :fragment="fragmentTextImageLeft"/>
			<component is="fragment-slider-image" class="fragment" :fragment="fragmentSliderImage"/>
			<component is="fragment-video" class="fragment" :fragment="fragmentVideo"/>

		</section>


		<p class="design-system-separator">Global Content Elements</p>
		<section class="content">

			<accordion title="Accordion closed">
				<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Quo consequuntur libero mollitia culpa provident quaerat error minus voluptatem molestias fugiat?</p>
			</accordion>
			<accordion title="Accordion open" open>
				<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Quo consequuntur libero mollitia culpa provident quaerat error minus voluptatem molestias fugiat?</p>
			</accordion>

		</section>

		<section></section>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref } from 'vue';
		import { usePage } from '@global/composables/usePage';
		import { useConfig } from '@global/composables/useConfig';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = usePage("Design System");
		const { baseUrl } = useConfig();


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		const inputValueText = ref("Input value");
		const itemsSelect = ref([]);
		const itemsSelectLarge = ref([]);
		const inputValueCheckbox = ref(["value0","value2"]);
		const inputValueRadio = ref("value1");
		const inputValueDateTime = ref("1.12.2024 14:03");


		setSelectItem(itemsSelect, 3);
		setSelectItem(itemsSelectLarge, 100);

		function setSelectItem(target, count) {

			target.value = [];

			for (let i = 0; i < count; i++) {
				let obj = {}; obj["Label "+i] = "value"+i;
				target.value.push(obj);
			}
		}


		/////////////////////////////////
		// FRAGMENTS
		/////////////////////////////////

		const lorem20 = "Lorem ipsum dolor sit amet consectetur, adipisicing elit. Quod nostrum blanditiis quisquam dolorem exercitationem adipisci at neque dolores dolore libero.";
		const lorem50 = "Lorem ipsum dolor sit amet consectetur adipisicing elit. Expedita asperiores, sequi ut impedit ab animi assumenda quisquam recusandae ad enim ipsum et omnis vitae voluptatibus debitis aut perspiciatis ullam, blanditiis suscipit accusantium ea amet? Dignissimos placeat delectus nam sapiente ipsam quidem. Sit adipisci iusto delectus itaque vero nihil at praesentium!";

		const fragmentText = {
			id: '1',
			content: {
				title: "Title of Fragment Text",
				copy: `<p>${lorem50}</p>`,
			}
		};

		const fragmentTextImageRight = {
			id: '1',
			content: {
				title: "Title of Fragment Text-Image Right",
				copy: `<p>${lorem50}</p>`,
				image: baseUrl+'img/global/develop/test-hr.jpg',
				image_position: 'right',
				image_subline: 'subline for image right',
			}
		};


		const fragmentTextImageLeft = {
			id: '1',
			content: {
				title: "Title of Fragment Text-Image Left",
				copy: `<p>${lorem50}</p>`,
				image: baseUrl+'img/global/develop/test-hr.jpg',
				image_position: 'left',
				image_subline: 'subline for image left',
			}
		};

		const fragmentSliderImage = {
			id: '2',
			content: {
				title: "Title of Fragment Slider Image",
				items: [
					{ image: baseUrl+'img/global/develop/test-hr.jpg', subline: 'subline text', },
					{ image: baseUrl+'img/global/develop/test-hr.jpg', subline: 'subline text', },
					{ image: baseUrl+'img/global/develop/test-hr.jpg', subline: 'subline text', },
					{ image: baseUrl+'img/global/develop/test-hr.jpg', subline: 'subline text', },
				],
				copy: `<p>${lorem50}</p>`,
			}
		};

		const fragmentVideo = {
			id: '3',
			content: {
				video_desktop: "https://stream.mux.com/VZtzUzGRv02OhRnZCxcNg49OilvolTqdnFLEqBsTwaxU/low.mp4",
				video_mobile: "https://www.w3schools.com/html/mov_bbb.mp4",
				poster_desktop: baseUrl+'img/global/develop/test.jpg',
				poster_mobile: baseUrl+'img/global/develop/test.jpg',
			}
		};


	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    STYLE
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<style lang="scss">

		.design-system-separator {

			max-width: var(--maxContentWidth);
			text-align: center;
			border-top: 1px solid #eee;
			border-bottom: 1px solid #eee;
			margin: 5rem auto 2rem auto;
			padding: 1rem 0;
			color: #bbb;
		}


		.grid [class^="col-"] {

			margin-bottom: 2rem;
			padding: 1rem;
			text-align: center;
		}


		.grid .col-100 {background: hsl(188, 10%, 90%); }
		.grid .col-50 {background: hsl(190, 10%, 95%); }
		.grid .col-33, .col-33-67 {background: hsl(192, 20%, 92%); }
		.grid .col-67, .col-67-33 {background: hsl(192, 30%, 95%);}


		.iconset .svg-icon {

			width: 		30px;
			height: 	30px;
			position: 	relative;
			display: 	inline-block;
			margin: 	3px;
		}


		.form-space {

			height: 30px;
		}

	</style>

