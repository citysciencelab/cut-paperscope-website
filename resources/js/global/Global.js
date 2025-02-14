/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { defineAsyncComponent } from 'vue'

	// libraries
	import axios from 'axios';
	import LazyLoad from "vanilla-lazyload";
	import u from 'umbrellajs';

	// lang
	import LangGlobal from './lang/LangGlobal';
	import LangForm from './lang/LangForm';
	import LangCalendar from './lang/LangCalendar';

	// mandatory global components
	import Btn from '@global/components/content/Btn.vue';
	import SvgItem from '@global/components/content/SvgItem.vue';
	import Scroller from '@global/components/content/Scroller.vue';
	import LazyPicture from '@global/components/content/LazyPicture.vue';
	import LanguageSelect from '@global/components/navi/LanguageSelect.vue';
	import PageTransition from '@global/pages/PageTransition.vue';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MAIN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const init = (app, webContext) => {

		window.config.webContext = webContext;

		// vue
		initComponents(app);
		initGlobalProperties(app, webContext);
		initGlobalDirectives(app);

		// libraries
		initAxios(app, webContext);
		initLazyload(app);
		initUmbrella(app);
	};



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPONENTS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const initComponents = app => {

		// mandatory global components
		app.component('Btn', Btn);
		app.component('SvgItem', SvgItem);
		app.component('LanguageSelect', LanguageSelect);
		app.component('PageTransition', PageTransition);
		app.component('Scroller', Scroller);
		app.component('LazyPicture', LazyPicture);

		// async global components
		const asyncComponents = import.meta.glob([
			'./components/content/Slider.vue',
			'./components/content/Accordion.vue',
			'./components/content/LoadingSpinner.vue',
			'./components/content/PaginatorScroller.vue',
			'./components/content/ScaledText.vue',
			'./components/broadcast/Broadcast.vue',
			'./components/form/**/*.vue',
			'./components/popup/**/*.vue',
			'./components/user/**/*.vue',
			'./components/video/**/*.vue',
			'./components/shop/**/*.vue',
		]);
		addAsyncComponents(app, asyncComponents );
	};


	// add all components from a glob import as static components
	const addComponents = (app,components) => {

		Object.entries(components).forEach(([path, definition]) => {

			const componentName = getComponentName(path);
			app.component(componentName, definition.default);
		});
	}


	// add all components from a glob import as async components
	const addAsyncComponents = (app,components) => {

		Object.entries(components).forEach(([path, definition]) => {

			const componentName = getComponentName(path);
			app.component(componentName, defineAsyncComponent(definition));
		});
	}


	const getComponentName = path => path.split('/').pop().replace(/\.\w+$/, '');



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	GLOBAL PROPERTIES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const initGlobalProperties = (app, webContext) => {

		const conf = window.config;

		// vars available in every <template>
		app.config.globalProperties = {

			// methods
			t: 					(key, params) => app.config.globalProperties.$t(key, params),
			link: 				link,

			// urls and paths
			baseUrl: 			conf.base_url.startsWith('//localhost') ? 'http:'+conf.base_url : conf.base_url,
			storageUrlPublic: 	conf.storage_url_public,
			storageUrlS3: 		conf.storage_url_s3,

			// state
			isLocal: 			conf.base_url.includes('localhost'),
			webContext: 		webContext,
		}
	};



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	GLOBAL METHODS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const link = (name, params = {}, query = {}, props = {}) => {

		var to = { params, query, ...props };

		if(typeof name == 'object') { to = { ...name, ...to }; }
		else { to.name = name; }

		// delete params with path instead of name
		if(to.params && to.path) { delete to.params; }

		// add language prefix if not default language
		if(window.config.active_locale != window.config.fallback_locale) {

			// prepend language to path if not existing
			if(to.path) {
				if(!to.path.startsWith('/')) { to.path = '/'+to.path; }
				if(!to.path.startsWith('/'+window.config.active_locale)) { to.path = '/'+window.config.active_locale+to.path; }
			}
			// add param on named route
			else {
				to.params.lang = window.config.active_locale;
			}
		}

		return to;
	};


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	GLOBAL DIRECTIVES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const initGlobalDirectives = app => {

		app.directive('fade', {

			mounted(el, binding) {

				// initial hide
				if(!binding.value) {
					el.style.opacity = 0;
					el.style.display = 'none';
				}
				el.classList.add('fade-anim');
			},

			updated(el, binding) {

				if(binding.value) {
					el.style.removeProperty('display');
					el.style.removeProperty('visibility');
					clearTimeout(window['fadeTimer'+binding.instance.$.uid]);
					setTimeout(()=> el.style.removeProperty('opacity'), 0);
				}
				else {
					el.style.opacity = 0;
					window['fadeTimer'+binding.instance.$.uid] = setTimeout(()=>{
						binding.arg == 'visibility' ? el.style.visibility = 'hidden' : el.style.display = 'none';
					}, 1001);
				}
			},
		});
	};



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	LAZYLOAD
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const initLazyload = app => {

		window.lazyload = new LazyLoad({ elements_selector: ".lazy" });
	};


	const updateLazyload = () => {

		clearTimeout(window.lazyloadTimer);
		window.lazyloadTimer = setTimeout(() => window.lazyload?.update(), 150);
	};



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	AXIOS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const initAxios = (app, webContext) => {

		window.axios = axios;

		// init headers
		window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
		window.axios.defaults.headers.common['Accept-Language'] = window.config.active_locale;
		window.axios.defaults.headers.common['X-Context'] = webContext;

		// init interceptor
		window.axios.interceptors.request.use((config) => axiosRequestIntercept(config,app));
		window.axios.interceptors.response.use(axiosResponseIntercept, (error) => axiosErrorIntercept(error,app) );
	};


	const axiosRequestIntercept = (config,app) => {

		// remove global error popup
		app._instance?.exposed?.storeGlobalError(null);

		return config;
	};


	const axiosResponseIntercept = response => {

		updateLazyload();
		setTimeout(() => updateLazyload(), 250);

		return response;
	};


	const axiosErrorIntercept = (error,app) => {

		// show global error popup
		if(error.response?.status == 500) {
			const store = app._instance?.exposed ?? window;
			store.storeGlobalError(error.response);
		}

		return Promise.reject(error);
	};



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	UMBRELLA
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const initUmbrella = app => {

		window.u = u;

		// get width without margin
		window.u.prototype.width = function() {

			var width = 0;
			this.each(el => { width += el.offsetWidth; });
			return width;
		};

		// get width with margin
		window.u.prototype.outerWidth = function() {

			var width = 0;
			this.each(el => {
				width += el.offsetWidth;
				var style = getComputedStyle(el);
				width += parseInt(style.marginLeft) + parseInt(style.marginRight);
			});
			return width;
		};

		// get height without margin
		window.u.prototype.height = function() {

			var height = 0;
			this.each(el => { height += el.offsetHeight; });
			return height;
		};

		// get height with margin
		window.u.prototype.outerHeight = function() {

			var height = 0;
			this.each(el => {
				height += el.offsetHeight;
				var style = getComputedStyle(el);
				height += parseInt(style.marginTop) + parseInt(style.marginBottom);
			});
			return height;
		};

		// get element by index
		window.u.prototype.el = function(index) {

			return u(this.nodes[index]);
		}
	};



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	I18N
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const getI18n = webMessages => {

		const messages = {};
		const globalMessages = [LangGlobal, LangForm, LangCalendar];

		// add global messages
		globalMessages.forEach(m => {

			Object.keys(m).forEach(key => {
				if(!messages[key]) { messages[key] = {}; }
				messages[key] = { ...messages[key], ...m[key] };
			});
		});

		// add web messages
		Object.keys(webMessages).forEach(key => {

			const content = webMessages[key].default;

			Object.keys(content).forEach(key => {
				if(!messages[key]) { messages[key] = {}; }
				messages[key] = { ...messages[key], ...content[key] };
			});
		});

		// create config for vue-i18n
		const i18n = {
			locale: 			window.config.active_locale,
			fallbackLocale:		window.config.fallback_locale,
			fallbackWarn: 		false,
			missingWarn: 		false,
			legacy: 			false,
			messages,
		};

		return i18n;
	};



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	export default { init, addComponents, addAsyncComponents, getI18n, updateLazyload };


