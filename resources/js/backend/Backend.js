/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { createApp } from "vue";
	import { createPinia } from 'pinia'
	import { createHead } from '@unhead/vue'
	import { createI18n } from 'vue-i18n';

	// vue plugins
	import BackendRoot from './BackendRoot.vue';
	import BackendRouter from './BackendRouter';

	// global
	import global from '@global/Global';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	VUE APP
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const backend = createApp(BackendRoot);

	// backend static components
	const components = import.meta.glob([
		'./components/dashboard/**/*.vue',
		'./components/header/**/*.vue',
		'./components/navi/**/*.vue',
		'./components/list/**/*.vue',
		'./components/fragment/**/*.vue',
		'./components/footer/**/*.vue',
		'./components/popup/**/*.vue',
		'./components/content/**/*.vue',
		'./components/model/**/*.vue',
	], { eager: true });
	global.addComponents(backend,components);


	// backend async components
	const asyncComponents = import.meta.glob([
		'./components/user/**/*.vue',
		'./components/file-manager/**/*.vue',
	]);
	global.addAsyncComponents(backend,asyncComponents);



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MAIN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// create vue app
	global.init(backend,'backend');

	// init i18n
	const messages = import.meta.glob('./lang/*.js', { eager: true });
	const i18n = global.getI18n(messages)

	// init vue plugins
	backend.use(createPinia());
	backend.use(createHead());
	backend.use(createI18n(i18n));
	backend.use(BackendRouter);

	// start vue app
	BackendRouter.isReady().then(() => backend.mount('#app'))


