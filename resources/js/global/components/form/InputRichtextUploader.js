/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	import { Plugin, FileRepository, logWarning } from 'ckeditor5';

	import slugify from 'slugify';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	PLUGIN CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


export default class InputRichtextUploader extends Plugin {


	static get requires() { return [ FileRepository ]; }
	static get pluginName() { return 'InputRichtextUploader'; }


	init() {

		const options = this.editor.config.get( 'simpleUpload' );

		if (!options) { return; }
		if (!options.uploadUrl) { return logWarning( 'simple-upload-adapter-missing-uploadurl' ); }

		this.editor.plugins.get( FileRepository ).createUploadAdapter = loader => {
			return new Adapter( loader, options );
		};
	}
}


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class Adapter {

	constructor( loader, options ) {

		this.loader = loader;
		this.options = options;
	}


	upload() {

		return this.loader.file
			.then( file => new Promise( ( resolve, reject ) => {
				this._initRequest();
				this._initListeners( resolve, reject, file );
				this._sendRequest( file );
			} ) );
	}


	abort() {

		if(this.xhr) { this.xhr.abort(); }
	}


	_initRequest() {

		const xhr = this.xhr = new XMLHttpRequest();
		xhr.open( 'POST', this.options.uploadUrl, true );
		xhr.responseType = 'json';
	}


	_initListeners( resolve, reject, file ) {

		const xhr = this.xhr;
		const loader = this.loader;
		const genericErrorText = `Couldn't upload file: ${ file.name }.`;

		xhr.addEventListener( 'error', () => reject( genericErrorText ) );
		xhr.addEventListener( 'abort', () => reject() );
		xhr.addEventListener( 'load', () => {
			const response = xhr.response;

			if ( !response || response.error ) {
				return reject( response && response.error && response.error.message ? response.error.message : genericErrorText );
			}

			// set absolute url depending on storage
			var storage = '';
			switch(this.options.storage) {
				case 'public': 	storage = window.config.storage_url_public; break;
				case 's3': 		storage = window.config.storage_url_s3; break;
			}

			resolve( { default: storage + response.data });
		} );

		// Upload progress when it is supported.
		/* istanbul ignore else */
		if (xhr.upload) {
			xhr.upload.addEventListener( 'progress', evt => {
				if ( evt.lengthComputable ) {
					loader.uploadTotal = evt.total;
					loader.uploaded = evt.loaded;
				}
			} );
		}
	}


	_sendRequest( file ) {
		// Set headers if specified.
		const headers = this.options.headers || {};

		// Use the withCredentials flag if specified.
		const withCredentials = this.options.withCredentials || false;

		for ( const headerName of Object.keys( headers ) ) {
			this.xhr.setRequestHeader( headerName, headers[ headerName ] );
		}

		this.xhr.withCredentials = withCredentials;

		// slugify the filename (deep copy because readonly)
		let uploadFile = new File(
			[file],
			slugify(file.name, {locale:'de', lower:true}),
			{
				type: file.type,
				lastModified: file.lastModified,
			}
		);

		// Prepare the form data.
		const data = new FormData();
		data.append('folder',this.options.folder ?? '/');
		data.append('storage',this.options.storage);
		data.append('stream_offset', '78fb153f02e9d3a43b4e5a81273ed716=');
		data.append('file', uploadFile);

		// Send the request.
		this.xhr.send( data );
	}
}

