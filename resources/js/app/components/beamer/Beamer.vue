<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div id="beamer">

			<div class="beamer-buttons">
				<btn :icon="isCalibrating ? 'close' : 'btn-edit'" class="small secondary" @click="toggleCalibrate"/>

				<!-- FORM -->
				<input-text  id="scaling"  v-if="isCalibrating" v-model="scaling" type="number"/>
				<btn label="save" class="small secondary" v-if="isCalibrating" @click="saveCalibration"/>

				<btn icon="btn-fullscreen" class="small secondary" v-else @click="toggleFullscreen"/>
				<btn :icon="socketConnected ? 'btn-connected' : 'btn-disconnected'" :class="['small secondary socket',{'disconnected': !socketConnected}]" @click="toggleWebsocket"/>
			</div>
		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
		import { useRoute } from 'vue-router';
		import { useConfig } from '@global/composables/useConfig';
		import { useApi } from '@global/composables/useApi';
		import { useBroadcast } from '@global/composables/useBroadcast';

		import p5 from 'p5';
		import pMapper from '@resources/js/app/components/beamer/mapper/ProjectionMapper';
		import PerspT from "@resources/js/app/components/beamer/mapper/perspective/PerspT";


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const route = useRoute();
		const { baseUrl } = useConfig();
		const { apiGetSlug } = useApi();


		/////////////////////////////////
		// PROJECT
		/////////////////////////////////

		const project = ref(null);

		if(route.params.slug) {

			apiGetSlug('project', onProjectLoaded).catch(e => console.log(e));
		}

		function onProjectLoaded(data) {

			project.value = data;
			scaling.value = parseFloat(localStorage.getItem("paperscope-calibration-"+project.value.slug)) || 1.0;

			if(p5Instance) { initMapper(p5Instance); }
			initBroadcast();
		}


		/////////////////////////////////
		// ANIM
		/////////////////////////////////

		onMounted(() => {

			gsap.to("#beamer", { duration: 0.5, opacity: 1, delay: 1.5 });
			new p5(initP5, u("#beamer").first());
		});

		watch(route, () => {

			gsap.to("#beamer", { duration: 0.5, opacity: 0 });
		});


		/////////////////////////////////
		// P5
		/////////////////////////////////

		var p5Instance = null;

		function initP5(p) {

			p.setup = () => setup(p);
			p.draw = () => draw(p);
		}

		function setup(p) {

			p5Instance = p;

			p.createCanvas(window.innerWidth, window.innerHeight, p.WEBGL);
			p.loadFont(baseUrl+'fonts/inter/Inter-Regular.ttf', font =>p.textFont(font));
			if(project.value) { initMapper(p) };
		}

		function draw(p) {

			p.background(0);

			mapRender?.displayTexture(mapImage);
			mapCalibrate?.display(isCalibrating.value ? p.color(240,100,160) : p.color(0,0,0));
		}


		/////////////////////////////////
		// MAPPER
		/////////////////////////////////

		var mapCalibrate = null;
		var mapRender = null;
		var mapImage = null;

		function initMapper(p) {

			pMapper.pInst = p;
			pMapper.init(window.innerWidth, window.innerHeight);

			p.registerMethod("post", () => pMapper.displayControlPoints());
			p.registerMethod("post", () => pMapper.updateEvents());

			const scaling = 0.75;
			const ratio = project.value.ratio;
			const width = ratio < 1.0 ? parseInt(297 * ratio) : 297;
			const height = ratio >= 1.0 ? parseInt(297 / ratio) : 297;

			mapRender = pMapper.createQuadMap(parseInt(width * scaling), parseInt(height * scaling), 20);
			mapCalibrate = pMapper.createQuadMap(30, 30, 5);
			//mapCalibrate.isHidden = true;
			mapCalibrate.controlPointColor = p.color(180,0,255);

			pMapper.load(project.value.slug);
			mapImage = p.loadImage(baseUrl+'project/map?slug='+project.value.slug);
			mapRender.imgWidth = ratio < 1.0 ? parseInt(1024 * ratio) : 1024;
			mapRender.imgHeight =  ratio >= 1.0 ? parseInt(1024 / ratio) : 1024;

			transformMatRender();
		}

		function toggleBeamer(value) {

			if(isCalibrating.value) { return; }
			mapRender.isHidden = value;
		}


		/////////////////////////////////
		// CALIBRATION
		/////////////////////////////////

		const isCalibrating = ref(false);
		const scaling = ref(1.0);

		function toggleCalibrate() {

			pMapper.toggleCalibration();
			isCalibrating.value = !isCalibrating.value;

			mapRender.isHidden = isCalibrating.value;

			if(!isCalibrating.value) { transformMatRender(); }
		}

		function saveCalibration() {

			transformMatRender();
			pMapper.save(project.value.slug);

			// save scaling
			localStorage.setItem("paperscope-calibration-"+project.value.slug, scaling.value);
			sendChannel('project.'+project.value.slug, 'Scaling', { value: scaling.value });
		}

		function transformMatRender() {

			const source = [
				0, 0,
				mapCalibrate.width, 0,
				mapCalibrate.width, mapCalibrate.height,
				0, mapCalibrate.height,
			];

			const target = [
				mapCalibrate.mesh[mapCalibrate.TL].x, mapCalibrate.mesh[mapCalibrate.TL].y,
				mapCalibrate.mesh[mapCalibrate.TR].x, mapCalibrate.mesh[mapCalibrate.TR].y,
				mapCalibrate.mesh[mapCalibrate.BR].x, mapCalibrate.mesh[mapCalibrate.BR].y,
				mapCalibrate.mesh[mapCalibrate.BL].x, mapCalibrate.mesh[mapCalibrate.BL].y,
			];

			const perspT = PerspT(source, target);

			const pp = [
				[0, 0],
				[mapRender.width*scaling.value, 0],
				[mapRender.width*scaling.value, mapRender.height*scaling.value],
				[0, mapRender.height*scaling.value]
			];

			const keys = [mapRender.TL, mapRender.TR, mapRender.BR, mapRender.BL];
			keys.forEach((key, i) => {
				const p = perspT.transform(pp[i][0],pp[i][1]);
				mapRender.mesh[key].x = p[0];
				mapRender.mesh[key].y = p[1];
			});

			mapRender.x = mapCalibrate.x;
			mapRender.y = mapCalibrate.y;

			mapRender.calculateMesh();
   		}

		/////////////////////////////////
		// KEYBOARD EVENTS
		/////////////////////////////////

		window.addEventListener('keydown', onKeydown);

		function onKeydown(e) {

			if(e.key == 'c') { toggleCalibrate(); }

			// arrow keys
			if(e.key == 'ArrowUp') { movePoint("y",-1); }
			if(e.key == 'ArrowDown') { movePoint("y",1); }
			if(e.key == 'ArrowLeft') { movePoint("x",-1); }
			if(e.key == 'ArrowRight') { movePoint("x",1); }
		}

		function movePoint(prop,value) {

			if(window.activeControlPoint) {
				window.activeControlPoint[prop] += value;
				window.activeControlPoint.parent.calculateMesh();
			}
		}

		function movePointVertical(value) {

		}

		onBeforeUnmount(() => {

			window.removeEventListener('keydown', onKeydown);
		});


		/////////////////////////////////
		// FULLSCREEN
		/////////////////////////////////

		function toggleFullscreen() {

			const elem = document.documentElement;
			if(document.fullscreenElement) { document.exitFullscreen(); }
			else { elem.requestFullscreen(); }

			setTimeout(()=> {
				p5Instance.resizeCanvas(window.innerWidth, window.innerHeight);
			},1000);
		}


		/////////////////////////////////
		// BROADCAST
		/////////////////////////////////

		const { subscribeChannel, sendChannel, socketConnected, toggleWebsocket } = useBroadcast();

		function initBroadcast() {

			subscribeChannel('project.'+project.value.slug, onChannelMessage);
		}

		function onChannelMessage(event, data) {

			if(event == 'ToggleBeamer') { toggleBeamer(data.value); }
		}


	</script>


