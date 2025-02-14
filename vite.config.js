/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	import { defineConfig } from 'vite';
	import { createRequire } from 'node:module';
	const require = createRequire( import.meta.url );

	// Vue.js
	import vue from '@vitejs/plugin-vue';
	import VueI18nPlugin from '@intlify/unplugin-vue-i18n/vite'
	import { viteStaticCopy } from 'vite-plugin-static-copy'

	// Plugins
	import laravel from 'laravel-vite-plugin';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CONFIG
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */



export default defineConfig({

	base: "./",

	build: {

		chunkSizeWarningLimit: 910,					// InputRichtext is 905kb and can't be split into chunks

		// target older browsers
		//minify: 'terser',
		//target: "safari12",

		rollupOptions: {

			external: [/fonts/, /img/, /svg\/.+\.svg/, /videos/],

			output: {

				chunkFileNames: 'js/[name]-[hash].js',
				entryFileNames: 'js/[name]-[hash].js',

				assetFileNames: (assetInfo) => {

					// custom naming for thirdparty css files
					if(assetInfo.name == 'style.css') { return `css/uppy-[hash].css`; }

					var extType = assetInfo.name.split(".").pop();
					if(assetInfo.name == 'css/app.css') { return `[name]-[hash].css`; }
					else if(assetInfo.name == 'css/backend.css') { return `[name]-[hash].css`; }
					else if (extType=='css') { return `css/[name]-[hash].css`; }
					else { return 'assets/[name]-[hash][extname]'; }
				},

				manualChunks(id) {
					//if(id.includes('dashjs')) { return 'DashJs'; }
				},
			},
		},
	},


	plugins: [

		laravel({
			input: {
				'app/app': 'resources/js/app/App.js',
				'css/app': 'resources/sass/app/app.scss',
				'backend/backend': 'resources/js/backend/Backend.js',
				'css/backend': 'resources/sass/backend/backend.scss',
			},
		}),

		vue({
			template: {
				compilerOptions: {
      				isCustomElement: (tag) => tag.startsWith('media-'),		// vidstack media components
				},
				transformAssetUrls: {
					base: null,
					includeAbsolute: false,
				},
			},
		}),

		VueI18nPlugin({
			runtimeOnly: true,
			strictMessage: false,
		}),

		viteStaticCopy({
			targets: [
				{ src: 'node_modules/cesium/Build/Cesium/Assets', dest: '../cesium' },
				{ src: 'node_modules/cesium/Build/Cesium/ThirdParty', dest: '../cesium' },
				{ src: 'node_modules/cesium/Build/Cesium/Widgets', dest: '../cesium' },
				{ src: 'node_modules/cesium/Build/Cesium/Workers', dest: '../cesium' },
			]
		})
	],


	// Workaround full page reload when writing session files in storage/framework
	// laravel/framework v10.9 => v.10.10
	server: {
		watch: {
			ignored: ['**/storage/**','**/tests/**'],
		},
	},


	resolve: {
		alias: {
			'@public': 			'/public',
			'@resources': 		'/resources',
			'@app': 			'/resources/js/app',
			'@backend': 		'/resources/js/backend',
			'@global': 			'/resources/js/global',
			'@tests': 			'/tests',
			'@vendor': 			'/vendor',
			'@node_modules':	'/node_modules',
		}
	},


	css: {
		preprocessorOptions: {
			scss: {
				api: 'modern-compiler',
				// modify path to assets in scss files
				additionalData: process.env.NODE_ENV == 'production' ? `$public: "../../";` : `$public: "/public/";`,
			},
		},
	},


	test: {
		globals: true,
		environment: 'happy-dom',
		setupFiles: [ './tests/Vitest/vitest.setup.js' ],
		outputFile: {
			junit: "tests/Vitest/Results/junit.xml",
		},
		exclude: [
			'**/node_modules/**',
			'**/.{idea,git,cache,output,temp}/**',
			'**/{karma,rollup,webpack,vite,vitest,jest,ava,babel,nyc,cypress,tsup,build}.config.*',
			'**/scripts/**',
		],
		coverage: {
			provider: 'istanbul',
			all: true,
			include: [
				'resources/js/app/pages/**/*.{js,ts,vue}',
				'resources/js/app/components/**/*.{js,ts,vue}',
				'resources/js/app/composables/**/*.{js,ts,vue}',
				'resources/js/backend/pages/**/*.{js,ts,vue}',
				'resources/js/backend/components/**/*.{js,ts,vue}',
				'resources/js/backend/composables/**/*.{js,ts,vue}',
				'resources/js/global/pages/**/*.{js,ts,vue}',
				'resources/js/global/components/**/*.{js,ts,vue}',
				'resources/js/global/composables/**/*.{js,ts,vue}',
			],
			reporter: ['html'],
			reportsDirectory: './tests/Vitest/CodeCoverage'
		},
	},



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

});
