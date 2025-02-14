<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div :class="['form-row', rowId]">

			<input-label :label="$attrs.label" :info="$attrs.info" v-bind="labelAttrs"/>

			<!-- INPUT -->
			<ckeditor
				:id="inputId"
				v-model="value"
				:editor="ClassicEditor"
				:config="editorConfig"
				ref="editor"
				@focus="removeError"
				@input="updateInput"
			/>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, watch } from 'vue';
		import { useInput } from '@global/composables/useInput';
		import { useApi } from '@global/composables/useApi';
		import { useLanguage } from '@global/composables/useLanguage';

		import Cookies from 'js-cookie';

		// Editor Core
		import CKEditor from '@ckeditor/ckeditor5-vue';
		import {
			ClassicEditor, Essentials,
			Heading, Bold, Italic, Underline, FontColor,
			Link, Paragraph,
			List, Table, TableToolbar,
			Image, ImageInsert, MediaEmbed,
			Undo, Autoformat,
		} from 'ckeditor5';
		import 'ckeditor5/ckeditor5.css';

		// custom
		import InputRichtextUploader from '@global/components/form/InputRichtextUploader';
		import InputRichtextProvider from '@global/components/form/InputRichtextProvider';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			modelValue:		{ type: [String, Number, Object] },		// bind variable to v-model
			error: 			{ default: null },						// form data to show error

			id:				{ type: String, default: null }, 		// unique form id for this input element
			multilang: 		{ type: Boolean, default: false },		// same input for all languages

			// html
			type: 			{ type: String, default: 'text' },		// html input type: text, password, email, number, ...
			readonly: 		{ type: Boolean },						// html readonly attribute
			required: 		{ type: Boolean },						// html required attribute (show asterisk)

			// editor
			headings: 		{ type: Boolean, default: true }, 		// allow headline tags
			onlyText: 		{ type: Boolean, default: false },		// only text, no media, no custom blocks

			// file uploader
			folder: 		{ type: String, default: undefined }, 							// sub folder in storage
			storage: 		{ type: String, default: window.config.storage_default }, 		// laravel storage: public, s3
		});

		const emit = defineEmits(['update:modelValue', 'enter']);

		const { value, rowId, inputId, showError, updateInput, removeError, labelAttrs } = useInput(props, emit);
		const { createRoute } = useApi();
		const { t, activeLang } = useLanguage();


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		if(value.value == null) { value.value = ""; }
		watch(value, () => { if(value.value == null) { value.value = ""; } });


		/////////////////////////////////
		// CKE CONFIG
		/////////////////////////////////

		var editorConfig = {
			language: activeLang.value,
			plugins: [
				Essentials,
				Heading, Bold, Italic, Underline, FontColor,
				Link, Paragraph,
				Undo, Autoformat
			],
			toolbar: [
				'heading', '|',
				'bold', 'italic', 'underline', 'fontColor', 'link', '|',
			],
			fontColor: {
				colors: [
					{ color: '#000', label: 'Black' },
					{ color: '#CE2475', label: 'Red' },
					{ color: '#2665C4', label: 'Blue' },
				],
				colorPicker: false,
				documentColors: 0,
			},
			image: {
				upload: { types: ['jpeg', 'png', 'gif'] }
			},
			link: {
				decorators: {
					openInNewTab: {
						mode: 'manual',
						label: 'Open in a new tab',
						defaultValue: true,
						attributes: { target: '_blank', rel: 'noopener noreferrer' },
					},
					buttonStyle: {
						mode: 'manual',
						label: 'Display as button',
						classes: ['btn', 'small', 'btn-rte'],
					},
				},
			},
			table: {
				contentToolbar: [ 'tableColumn', 'tableRow', 'mergeTableCells' ]
			},
			heading: {
				options: [
					{ model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
				]
			},
			mediaEmbed: InputRichtextProvider,
			simpleUpload: {
				storage: props.storage,
				uploadUrl: createRoute('api.backend.file-upload'),
				folder: props.folder,
				withCredentials: true,
				headers: {
					'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN'),
				}
			},
		};

		// add headings
		if(props.headings) {
			editorConfig.heading.options.unshift(
				{ model: 'heading3', view: 'h3', title: 'Headline', class: 'ck-heading_heading3' }
			);
		}
		else {
			editorConfig.toolbar.splice(0, 1);
		}

		// add if custom blocks and media
		if(!props.onlyText) {
			editorConfig.plugins.push(List, Table, TableToolbar);
			editorConfig.plugins.push( Image, ImageInsert, InputRichtextUploader, MediaEmbed);
			editorConfig.toolbar.push('bulletedList', 'numberedList', 'insertTable', '|', 'insertImage', 'mediaEmbed', '|',);
		}

		// append end of toolbar
		editorConfig.toolbar.push('undo','redo');


		/////////////////////////////////
		// CKE
		/////////////////////////////////

    	const ckeditor = CKEditor.component;
		const editor = useTemplateRef('editor');


		/////////////////////////////////
		// CLIPBOARD
		/////////////////////////////////

		function initClipboard() {

			setTimeout(() => {
				const plugin = editor.value.instance.plugins.get( 'ClipboardPipeline' );
				plugin.on('inputTransformation', onPaste);
			}, 250);
		}

		watch(editor, initClipboard, {once: true});


		function onPaste(evt, data) {

			// remove recursive
			for(const child of data.content.getChildren()) {
				removeColorStyle(child);
			}
		}


		function removeColorStyle(child) {

			if(child.is('element')) {
				child._removeStyle('color');
				for(const child2 of child.getChildren()) {
					removeColorStyle(child2);
				}
			}
		}


	</script>


