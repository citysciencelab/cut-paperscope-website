<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="project-paper">

			<!-- MAP -->
			<div class="project-paper-map">
				<div id="map"></div>
			</div>

			<p class="small project-paper-note" v-html="t('copy.drag')"></p>

			<!-- FORM -->
			<template v-if="form.start_longitude">

				<!-- COORDINATES -->
				<div class="cols">
					<input-text class="col-50" label="Start Longitude" id="start-longitude" v-model="form"/>
					<input-text class="col-50" label="Start Latitude" id="start-latitude" v-model="form"/>
					<input-text class="col-50" :label="t('Ende')+' Longitude'" id="end-longitude" v-model="form"/>
					<input-text class="col-50" :label="t('Ende')+' Latitude'" id="end-latitude" v-model="form"/>
				</div>

				<!-- DOWNLOAD -->
				<div class="form-row-buttons">
					<btn :label="t('Zurücksetzen')" icon="btn-reset" @click="clearCoordinates"/>
					<btn :label="t('PDF erstellen')" class="btn-download" icon="btn-download" :disabled="!form.start_longitude" @click="getPdf"/>
				</div>

			</template>
			<div class="cols" v-else>
				<input-text :placeholder="t('Adresse suchen')" class="col-50" id="address" v-model="address"/>
			</div>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, onMounted, useTemplateRef, inject, computed } from 'vue';
		import { useConfig } from '@global/composables/useConfig';
		import { useLanguage } from '@global/composables/useLanguage';

		import { Map, View } from 'ol/index.js';
 		import { Fill, Stroke, Style, Circle as CircleStyle } from 'ol/style.js';
		import { Point, Polygon, Circle } from 'ol/geom.js';
		import { useGeographic, toLonLat } from 'ol/proj.js';

		import TileLayer from 'ol/layer/Tile.js';
		import VectorLayer from 'ol/layer/Vector.js';

		import OSM from 'ol/source/OSM.js';
		import Feature from 'ol/Feature.js';
		import Collection from 'ol/Collection.js';
		import VectorSource from 'ol/source/Vector.js';
		import {boundingExtent} from 'ol/extent.js';
		import {DragBox, Select, Translate} from 'ol/interaction.js';
		import {platformModifierKeyOnly} from 'ol/events/condition.js';

		import '@node_modules/ol/ol.css';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { baseUrl } = useConfig();
		const { t } = useLanguage();

		const form = inject('form');


		/////////////////////////////////
		// MAP
		/////////////////////////////////

		var map = null;
		var vectorLayer = null;
		var vectorSource = null;

		function initMap() {

			useGeographic();

			// container for drawing
			vectorSource = new VectorSource();
			vectorLayer = new VectorLayer({ source: vectorSource });

			// create map
			map = new Map({
				target: 'map',
				layers: [
					new TileLayer({ source: new OSM() }),
					vectorLayer
				],
				view: new View({
					center: [9.99, 53.565],
					zoom: 14
				})
			});

			// add rectangle if already set
			if(form.value.start_longitude) {

				const start = [form.value.start_longitude, form.value.start_latitude];
				const end = [form.value.end_longitude, form.value.end_latitude];
				addRectangle(start, end);
			}

			initDragBox();
			initInteraction();
		}


		onMounted(initMap);
		defineExpose({ focusRectangle });


		/////////////////////////////////
		// DRAGBOX
		/////////////////////////////////

		var dragBox = null;

		function initDragBox() {

			dragBox = new DragBox({
  				condition: platformModifierKeyOnly,
			});

			dragBox.on('boxend', onDragBox);
			map.addInteraction(dragBox);
		}


		function onDragBox() {

			vectorSource.clear();

			// get long and lat
			const coordinates = dragBox.getGeometry().getCoordinates();
			const longLat = coordinates[0].map(coordinate => toLonLat(coordinate));

			// update
			updateCoordinates(longLat);
			addRectangle(longLat[0], longLat[2]);
		}


		/////////////////////////////////
		// INTERACTION
		/////////////////////////////////

		function initInteraction() {

			const select = new Select({
				filter: feature => feature.get('name')?.includes('handle')
			});
			map.addInteraction(select);

			const translate = new Translate({
				features: select.getFeatures(),
			});
			map.addInteraction(translate);
		}


		/////////////////////////////////
		// RECTANGLE
		/////////////////////////////////

		function addRectangle(startPoint, endPoint) {

			// create points
			const tl = [startPoint[0], endPoint[1]];
			const bl = startPoint;
			const br = [endPoint[0], startPoint[1]];
			const tr = endPoint;
			const points = [tl, bl, br, tr, tl];

			// create rectangle
			const polygon = new Polygon([points]);
			const feature = new Feature({geometry: polygon, name: 'rectangle'});
			feature.setStyle(new Style({
				fill: new Fill({color: 'rgba(55, 138, 220, 0.1)'}),
				stroke: new Stroke({color: '#378ADC', width: 2})
			}));
			vectorSource.addFeature(feature);

			// handle start
			const start = new Feature({ geometry: new Point(startPoint), name: 'handleStart' });
			start.setStyle(handleStyle);
			vectorSource.addFeature(start);
			start.on('change', resizeRectangle);

			// handle end
			const end = new Feature({ geometry: new Point(endPoint), name: 'handleEnd' });
			end.setStyle(handleStyle);
			vectorSource.addFeature(end);
			end.on('change', resizeRectangle);
		}

		function focusRectangle() {

			const features = vectorSource.getFeatures();
			const rectangle = features.find(feature => feature.get('name') === 'rectangle');
			if(!rectangle) { return; }

			const extent = rectangle.getGeometry().getExtent();
			map.getView().fit(extent, {padding: [100, 100, 100, 100]});
		}

		function resizeRectangle() {

			const features = vectorSource.getFeatures();
			const rectangle = features.find(feature => feature.get('name') === 'rectangle');
			const start = features.find(feature => feature.get('name') === 'handleStart');
			const end = features.find(feature => feature.get('name') === 'handleEnd');

			const startCoordinates = start.getGeometry().getCoordinates();
			const endCoordinates = end.getGeometry().getCoordinates();

			const tl = [startCoordinates[0], endCoordinates[1]];
			const bl = startCoordinates;
			const br = [endCoordinates[0], startCoordinates[1]];
			const tr = endCoordinates;
			const points = [tl, bl, br, tr, tl];

			rectangle.getGeometry().setCoordinates([points]);

			// update form
			updateCoordinates([startCoordinates, [], endCoordinates]);
		}


		const handleStyle = new Style({
			image: new CircleStyle({
				radius: 9,
				fill: new Fill({ color: 'rgb(55, 138, 220)', }),
				stroke: new Stroke({ color: 'white', width: 2, }),
			}),
		});


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		const address = ref(undefined);

		function updateCoordinates(longLat) {

			// update form
			form.value.start_longitude = longLat[0][0];
			form.value.start_latitude = longLat[0][1];
			form.value.end_longitude = longLat[2][0];
			form.value.end_latitude = longLat[2][1];
		}

		function clearCoordinates() {

			form.value.start_longitude = undefined;
			form.value.start_latitude = undefined;
			form.value.end_longitude = undefined;
			form.value.end_latitude = undefined;
			vectorSource.clear();
		}


		/////////////////////////////////
		// PDF
		/////////////////////////////////


		function getPdf(e) {

			if(!form.value.start_longitude || !map) { return; }

			// get handle coordinates in screen coordinates
			const start = map.getPixelFromCoordinate([form.value.start_longitude, form.value.start_latitude]);
			const end = map.getPixelFromCoordinate([form.value.end_longitude, form.value.end_latitude]);
			const ratio = Math.abs( (end[0] - start[0]) / (end[1] - start[1]) );

			// open pdf in new tab
			const pdfUrl = baseUrl+`project/pdf?ratio=${ratio}`+(form.value.slug?`&slug=${form.value.slug}`:'');
			window.open(pdfUrl, '_blank');
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
				"copy.drag": "Ziehe ein Rechteck mit gedrückter <span>Strg<\/span> (Windows) oder <span>Command<\/span> (Mac) auf, um den Planungsbereich festzulegen.",
			},
			"en": {
				"copy.drag": "Drag a rectangle with <span>Ctrl<\/span> (Windows) or <span>Command<\/span> (Mac) pressed to set the planning area.",
				"Ende": "End",
				"Adresse suchen": "Search address",
				"Zurücksetzen": "Reset",
				"PDF erstellen": "Create PDF",
			}
		}
	</i18n>
