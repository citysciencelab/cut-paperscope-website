<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<btn class="small secondary" :id="uppyId" :label="label" @click="open"/>

		<popup ref="uploader" class="file-uploader-popup" @close="onClosed">
			<div :id="dashboardId"></div>
		</popup>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, watch, getCurrentInstance, onBeforeUnmount } from 'vue';
		import { useRoute } from 'vue-router';

		import { useLanguage } from '@global/composables/useLanguage';
		import { useApi } from '@global/composables/useApi';
		import { useConfig } from '@global/composables/useConfig';

		import Uppy from '@uppy/core'
		import Dashboard from '@uppy/dashboard';
		import Tus from '@uppy/tus';
		import AwsS3 from '@uppy/aws-s3';
		import German from '@uppy/locales/lib/de_DE';

		import Cookies from 'js-cookie';
		import slugify from 'slugify';

		import '@uppy/core/dist/style.css';
		import '@uppy/dashboard/dist/style.css';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			label:				{ type: String, default: "Datei hochladen" }, 					// label of the button
			multipleFiles: 		{ type: Boolean, default: false },
			folder: 			{ type: String, default: undefined }, 							// sub folder in storage
			type: 				{ type: String, default: 'file' }, 								// file, image, video, audio, media, doc, code
			storage: 			{ type: String, default: window.config.storage_default }, 		// laravel storage: public, s3
		});

		const emit = defineEmits(['done','file-added','complete','upload-success']);
		const { activeLang, t } = useLanguage();
		const { createRoute } = useApi();
		const { baseUrl } = useConfig();


		/////////////////////////////////
		// POPUP
		/////////////////////////////////

		const uploader = useTemplateRef('uploader');


		function open() {

			uploader.value.open();
			setTimeout(initUppy,50);	// estimated double nextTick duration
		}


		function onClosed() {

			uppy?.destroy();
			uppy = null;
		}


		onBeforeUnmount(onClosed);


		/////////////////////////////////
		// UPPY
		/////////////////////////////////

		const uppyId = 'upload-' + getCurrentInstance().uid;
		const dashboardId = 'dashboard-' + getCurrentInstance().uid;
		var uppy = null;


		function initUppy() {

			let config = {
				id: uppyId,
				autoProceed: false,
				allowMultipleUploads: props.multipleFiles,
				formData: true,
				locale: activeLang.value == 'en' ? null : German,
				restrictions: {
				 	allowedFileTypes: getAllowedFileTypes(),
				 	maxNumberOfFiles: props.multipleFiles ? 10 : 1,
				},
				meta: {
					folder: props.folder ?? '/',
					'stream_offset': '78fb153f02e9d3a43b4e5a81273ed716=',
				},
				onBeforeFileAdded,
			};

			// apply max file size for image uploads
			if(props.type=='image') {
				config.restrictions.maxFileSize = 1024 * 1000 * 8; // max. 8MB
			}

			// create uppy instance
			if(uppy) { uppy.cancelAll(); uppy.destroy(); }
			uppy = new Uppy(config);

			uppy.use(Dashboard, {
				target: '#'+dashboardId,
				trigger: '#'+uppyId,
				inline: true,
				width: '100%',
				height: 300,
				thumbnailWidth: 200,
				proudlyDisplayPoweredByUppy: false,
				doneButtonHandler,
			});

			// set correct uppy uploader for storage
			initStorageUploader();

			// Uppy Events
			uppy.on('file-added', file => emit('file-added',file));
			uppy.on('complete', result => emit('complete',result));
			uppy.on('upload-success', (data,response) => emit('upload-success',data,response));
		}


		/////////////////////////////////
		// UPPY EVENTS
		/////////////////////////////////

		const route = useRoute();

		function onBeforeFileAdded(currentFile, files) {

			// create slug from file name for correct url
			var name = route.name == 'backend.file-manager' ? '' : parseInt(Date.now()/1000)+'-';
			name += slugify(currentFile.name, {locale:'de', lower:true});

			// validate pixel dimensions if image
			if(currentFile.type.includes('image/')) {

				// convert file to image
				var url = URL.createObjectURL(currentFile.data);
				var img = new Image;
				img.onload = () => {

					if(img.width>4000 || img.height >4000) {

						// show error message
						this.uppy.info(t('error.maxpixel'), 'error', 20*1000);

						// remove file from list
						this.uppy.removeFile(currentFile.id);
					}
					URL.revokeObjectURL(img.src);
				};
				img.src = url;
			}

			// add file to upload list
			const modifiedFile = {
				...currentFile,
				meta: {	...currentFile.meta, name },
				name
			};

			return modifiedFile;
		}

		function doneButtonHandler() {

			uppy.cancelAll();
			uploader.value.close();
			emit('done');
		}


		/////////////////////////////////
		// STORAGE
		/////////////////////////////////

		function initStorageUploader() {

			// set new storage
			switch(props.storage) {
				case 'public': 	initStorageTus(); break;
				case 's3': 		initStorageS3(); break;
			}
		};

		function initStorageTus() {

			uppy.use(Tus, {
				limit: 2,
				endpoint: createRoute('api.backend.tus'),
				chunkSize: 1024 * 1024 * 5, // 5MB
				removeFingerprintOnSuccess: true, // forget file on successful upload. Reupload forces overwrite,
				headers: {
					'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN'),
					'Credentials': true,
					'X-Stream-Offset': '78fb153f02e9d3a43d4e5a81273eb716=',
				},
				getResponseError (responseText, response) {
					return new Error(JSON.parse(responseText).message);
				}
			});
		}

		function initStorageS3() {

			uppy.use(AwsS3, {
				limit: 2,
				endpoint: baseUrl+'api/backend/',
				shouldUseMultipart(file) { return true; },
				getChunkSize() { return 1024 * 1024 * 20; },
				headers: {
					'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN'),
					'Credentials': true,
				},

			});
		}

		watch(() => props.storage,() => uppy ? initUppy() : null );


		/////////////////////////////////
		// FILES
		/////////////////////////////////

		function getAllowedFileTypes() {

			var images 		= ['image/jpeg', 'image/png', 'image/gif', 'image/webp','image/svg+xml'];
			var docs 		= ['application/pdf', '.vtt', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
			var archives 	= ['application/pdf', 'application/zip', '.zip'];

			switch(props.type) {
				case 'image':  	return images;
				case 'video': 	return ['video/mp4'];
				case 'audio': 	return ['audio/mpeg'];
				case 'media': 	return [...images, 'video/mp4', 'audio/mpeg'];
				case 'doc': 	return [...docs, ...archives];
				case 'code': 	return ['text/html'];
				default: 		return [...images, 'video/mp4', 'audio/mpeg', ...docs, ...archives];
			}
		}

		watch(() => props.folder, () => uppy?.setMeta({ folder: props.folder }) );


	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
				"error.maxpixel": "Fehler: Das Bild hat mehr als 4000px in Breite oder Höhe.",
			},
			"en": {
				"error.maxpixel": "Error: The image has more than 4000px in width or height.",
			}
		}
	</i18n>
