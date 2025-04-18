/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	ZIGGY ROUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	declare global {
		interface Window {
			config: any;
			Ziggy: any;
		}
	}

	// Ziggy library
	import { Ziggy } from './ZiggyExport.js'

	// Overwrite hardcoded domain names
	Ziggy.baseDomain = window.location.hostname;
	Ziggy.baseProtocol = window.location.protocol.replace(':','');
	Ziggy.baseUrl = window.location.origin + window.config?.base_path;
	Ziggy.url = Ziggy.baseUrl.replace(/\/+$/,"");
	window.Ziggy = Ziggy;

	import route from 'ziggy-js';
	export { route }


