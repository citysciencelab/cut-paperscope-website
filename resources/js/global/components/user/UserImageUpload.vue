<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div ref="root" v-bind="$attrs" class="form-row user-image-upload">

			<p class="error" v-if="hasError">{{ t("error.max_filesize") }}</p>
			<user-image :target="target"/>

			<!-- BUTTONS -->
			<div class="user-image-upload-buttons">
				<span ref="uploadBtn"></span>
				<btn :label="t('Bild zurücksetzen')" v-if="hasCustomImage" @click="confirmReset" class="secondary small"/>
			</div>

		</div>

		<!-- EDIT -->
		<popup ref="editPopup">
			<div class="cropper-upload">
				<img ref="previewImage" class="cropper-preview" :alt="t('Vorschaubild')"/>
				<div v-show="!cropperActive" class="cropper-border"></div>
			</div>
			<template #buttons>
				<btn v-if="!cropperActive" label="Abbrechen" class="secondary small" @click="closePopup"/>
				<btn v-if="!cropperActive" :label="t('Bild zuschneiden')" class="small" @click="initCropper"/>
				<btn v-if="!cropperActive" :label="t('Bild hochladen')" class="small" @click="upload" blocking/>
				<btn v-if="cropperActive" label="Abbrechen" class="secondary small" @click="abortCrop"/>
				<btn v-if="cropperActive" label="Bestätigen" class="small" @click="confirmCrop"/>
			</template>
		</popup>

		<!-- RESET -->
		<popup-modal ref="resetModal"/>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, computed, onMounted, getCurrentInstance, nextTick } from 'vue';
		import { useLanguage } from '@/global/composables/useLanguage';
		import { useApi } from '@/global/composables/useApi';
		import { useUserStore } from '@global/stores/UserStore';

		import Uppy from '@uppy/core';
		import FileInput from '@uppy/file-input';
		import XHRUpload from '@uppy/xhr-upload';

		import Cropper from 'cropperjs';
		import Cookies from 'js-cookie';

		import '@uppy/core/dist/style.css';
		import 'cropperjs/dist/cropper.css';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			target: 	{ type: Object, required: true },
		});


		const { t } = useLanguage();
		const { createRoute, apiPost } = useApi();
		const store = useUserStore();

		const hasCustomImage = computed(() => props.target?.image ? !props.target.image.includes('default-hr.jpg') : false);


		onMounted(() => initUppy());


		/////////////////////////////////
		// UPPY
		/////////////////////////////////

		var uppy = null;

		function initUppy() {

			uppy = new Uppy({
				id: 'uppy' + getCurrentInstance().uid,
				autoProceed: false,
				allowMultipleUploads:false,
				formData: true,
				restrictions: {
					allowedFileTypes: ['image/*'],
					maxNumberOfFiles: 1,
					maxFileSize: 1024 * 1000 * 8, // max. 8MB
				},
				meta: {
					'stream_offset': '78fb153f02e9d3a43b4e5a81273ed716=',
					'id': props.target.id,
				},
			});

			// create upload button
			uppy.use(FileInput, {
				target: uploadBtn.value,
				pretty: true,
				inputName: 'files[]',
				locale: {
					strings: {
						chooseFiles: t('Profilbild ändern'),
					},
				},
			});
			u(uploadBtn.value).find('.uppy-FileInput-btn').addClass('btn small secondary');

			// create uploader
			uppy.use(XHRUpload, {
				endpoint: createRoute('user.image'),
				headers: {
					'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN'),
					'Credentials': true,
					'X-Requested-With': 'XMLHttpRequest',
					'X-Stream-Offset': '78fb153f02e9d3a43d4e5a81273eb716=',
				},
			});

			// init events
			uppy.on('file-added', onImageFileAdded);
			uppy.on('restriction-failed', onRestrictionError);
			uppy.on('upload-success', onUploadComplete);
		}


		/////////////////////////////////
		// UPPY EVENTS
		/////////////////////////////////

		const hasError = ref(false);

		async function onImageFileAdded(file) {

			// skip if processed file for upload
			if(isUploading.value) { return; }

			hasError.value = false;
			editPopup.value.open();
			await nextTick();

			// init preview image
			previewImage.value.removeEventListener('load', updatePreviewImage);
			previewImage.value.addEventListener('load', updatePreviewImage);
			window.removeEventListener('resize', updatePreviewImage);
			window.addEventListener('resize', updatePreviewImage);

			// read file data and create preview image
			let fr = new FileReader();
			fr.addEventListener("load", ()=> previewImage.value.src = fr.result, false);
			fr.readAsDataURL(file.data);
		}


		function onRestrictionError(file,error) {

			hasError.value = true;
			uppy.cancelAll();
		}


		/////////////////////////////////
		// PREVIEW IMAGE
		/////////////////////////////////

		const previewImage = useTemplateRef('previewImage');

		function updatePreviewImage() {

			const cw = u('.cropper-upload').width();
			const ch = u('.cropper-upload').height();

			// place image as contain in cropper
			var w = previewImage.value.naturalWidth;
			var h = previewImage.value.naturalHeight;
			if(w > cw) { h = h * (cw / w); w = cw; }
			if(h > ch) { w = w * (ch / h); h = ch; }
			gsap.set(previewImage.value, {width:w,height:h});

			// show rounded border for image
			var size = w<h ? w : h;
			gsap.set('.cropper-border',{width:size,height:size,display:'block'});
		}


		/////////////////////////////////
		// CROPPER
		/////////////////////////////////

		var cropper = null;
		const cropperActive = ref(false);


		function initCropper() {

			cropper = new Cropper(previewImage.value, {
				aspectRatio: 1,
				viewMode: 2,
			});

			cropperActive.value = true;
		}


		async function abortCrop() {

			cropper.destroy();
			cropper = null;
			cropperActive.value = false;

			await nextTick();
			updatePreviewImage();
		}


		async function confirmCrop() {

			// get cropped image
			var canvas = cropper.getCroppedCanvas({
				width:400,
				height:400,
				fillColor: '#fff',
			});

			cropper.destroy();
			cropper = null;
			cropperActive.value = false;

			// show cropped image as preview
			await nextTick();
			previewImage.value.src = canvas.toDataURL();
		}


		/////////////////////////////////
		// UPLOAD
		/////////////////////////////////

		const uploadBtn = useTemplateRef('uploadBtn');
		const editPopup = useTemplateRef('editPopup');
		const isUploading = ref(false);


		function upload() {

			var iw = previewImage.value.naturalWidth;
			var ih = previewImage.value.naturalHeight;
			var w,h,sx,sy = 0;

			// get dimensions of image
			if(iw>=ih) {
				w = ih;
				h = ih;
				sx = (iw - ih) / 2;
				sy = 0;
			}
			else {
				w = iw;
				h = iw;
				sx = 0;
				sy = (ih - iw) / 2;
			}

			// crop and resize image
			var canvas = document.createElement('canvas');
			canvas.width = 400;
			canvas.height = 400;
			var ctx = canvas.getContext('2d');
			ctx.drawImage(previewImage.value,sx,sy,w,h,0,0,400,400);

			// convert image data to file and start upload
			isUploading.value = true;
			canvas.toBlob((blob)=>{

				var img = new File([blob], "upload.jpg");

				// add data to uppy
				uppy.cancelAll();
				uppy.addFile({
					name:'upload.jpg',
					type:'image/jpeg',
					data: img,
					source: 'Local',
					isRemote: false,
				});

				uppy.upload();
				canvas = null;

			},'image/jpeg');
		}


		function onUploadComplete(data,response) {

			if(response.status == 200) { updateImageData(response.body.data); }

			isUploading.value = false;

			// close popup
			closePopup();
		}


		function updateImageData(newImage) {

			// update image in component
			props.target.image = newImage;

			// update user if my image
			if(store.user.id == props.target.id) { store.setUserImage(newImage); }
		}


		/////////////////////////////////
		// RESET
		/////////////////////////////////

		const resetModal = useTemplateRef('resetModal');


		function confirmReset() {

			resetModal.value.open({
				title: t("Profilbild löschen"),
				copy: t("delete.copy"),
				alert: true,
				confirmLabel: t("Profilbild löschen"),
				callback: () => reset(props.target.id)
			});
		}


		function reset(userId) {

			apiPost('user.image.delete', {id:userId}, updateImageData);
		}


		/////////////////////////////////
		// POPUP
		/////////////////////////////////

		function closePopup() {

			uppy.cancelAll();
			editPopup.value.close();
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
				"error.max_filesize": "Das ausgewählte Profilbild ist größer als 8MB.",
				"delete.copy": "Willst du dein Profilbild wirklich löschen? Das Bild wird unwiderruflich entfernt und gegen ein Standardbild ausgetauscht.",
			},
			"en": {
				"Bild zurücksetzen": "Reset image",
				"Bild zuschneiden": "Crop image",
				"Bild hochladen": "Upload image",
				"Profilbild löschen": "Delete profile picture",
				"Profilbild ändern": "Change profile picture",

				"delete.copy": "Do you really want to delete your profile picture? The picture will be irrevocably removed and replaced with a standard picture.",
				"error.max_filesize": "The selected profile picture is larger than 8MB.",
			}
		}
	</i18n>
