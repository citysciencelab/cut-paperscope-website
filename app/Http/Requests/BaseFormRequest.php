<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// 	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Requests;

	// Laravel
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Support\Str;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// 	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class BaseFormRequest extends FormRequest {


	protected bool $forceJsonResponse = false;


	public function __construct() {

		if($this->forceJsonResponse) { request()->headers->set('Accept', 'application/json'); }
	}


	public function authorize(): bool {

		return true;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	VALIDATOR
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function validated($key = null, $default = null) {

		if($key) { return parent::validated($key,$default); }
		$validated = parent::validated();

		/** @var Illuminate\Validation\Validator $validator */
		$validator = $this->getValidatorInstance();
		$rules = array_keys($validator->getRulesWithoutPlaceholders());

		// add missing validated values with rules as null
		$validated = array_merge( array_fill_keys($rules,null), $validated);

		return (object) $validated;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CUSTOM RULES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function rule(string $name, bool $required = false, mixed $params = null): string|array {

		$rule = match($name) {
			'email' => 					'bail|nullable|string|email:strict|not_regex:/[äüö]/i|max:100',
			'username' => 				['bail','required','string','regex:/^[a-z0-9]([._-](?![._-])|[a-z0-9]){1,28}[a-z0-9]$/','min:3','max:30'],

			// default form inputs
			'input-date' => 			'bail|nullable|date_format:"j.n.Y"',
			'input-datetime' => 		'bail|nullable|date_format:"j.n.Y H:i"',
			'input-time' => 			'bail|nullable|date_format:"H:i"',
			'input-richtext' => 		'bail|nullable|string',
			'content-json' => 			'bail|nullable|array',
			'boolean' => 				'bail|nullable|boolean',

			// form uploads
			'input-file-upload' => 		'bail|nullable|string|file_extension:jpg,jpeg,png,gif,svg,mp4,mp3,pdf,txt,doc,docx,html,zip|max:256',
			'input-image-upload' => 	'bail|nullable|string|file_extension:jpg,jpeg,png,gif,svg|max:256',
			'input-video-upload' => 	'bail|nullable|string|file_extension:mp4|max:256',
			'input-audio-upload' => 	'bail|nullable|string|file_extension:mp3|max:256',
			'input-media-upload' => 	'bail|nullable|string|file_extension:jpg,jpeg,png,gif,svg,mp4|max:256',
			'input-doc-upload' => 		'bail|nullable|string|file_extension:pdf,vtt|max:256',
			'input-code-upload' => 		'bail|nullable|string|file_extension:html|max:256',
			'input-clocktime' => 		'bail|nullable|string|regex:/^([0-2]?[0-9]):[0-5][0-9]$/',

			// relations
			'input-relation' => 		'bail|nullable|array',
			'input-relation-item' => 	'bail|nullable|uuid'.($params?'|exists:'.$params.',id':''),
			'parent_id' => 				'bail|nullable|string|uuid',
			'parent_type' => 			'bail|nullable|string|regex:/^[\w\d]+$/i|max:40',

			// file management
			'folder' => 				'bail|nullable|string|regex:/^[\w\d\/_-]+$/i|max:256',
			'storage' => 				'bail|nullable|string|in:public,s3,testing',

			// shop
			'stripe-id' => 				'bail|nullable|string|max:50|starts_with:prod_',
		};

		if($required) { $rule = str_replace('nullable|','required|',$rule); }

		return $rule;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	ERROR MESSAGES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// rename variables for form error messages
	public function attributes(): array {

		return [

			// user
			'username' => 				trans('Benutzername'),

			// default form inputs
			'input-date' => 			trans('Datum'),
			'input-datetime' => 		trans('Datum und Uhrzeit'),
			'input-richtext' => 		trans('Text'),
			'content-json' => 			trans('Json-Content'),

			// form uploads
			'input-file-upload' => 		trans('Datei'),
			'input-image-upload' => 	trans('Bilddatei'),
			'input-video-upload' => 	trans('Videodatei'),
			'input-audio-upload' => 	trans('Audio'),
			'input-doc-upload' => 		trans('Dokument'),
			'input-code-upload' => 		trans('Datei'),

			// relations
			'input-relation' => 		trans('Relation'),
			'input-relation-item' => 	trans('Relation-Element'),

			// shop
			'stripe-id' => 				trans('Stripe-Id'),
		];
	}


	public function messages(): array {

		return [
			//'data.*' => 'Datenfeld muss ausgefüllt sein',
		];
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MULTI LANG
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function translate(array $rules): array {

		$translated = [];

		if(config('app.features.multi_lang')) {

			$fallbackLang = config('app.fallback_locale');
			$langs = config('app.available_locales');
			$langs = array_diff($langs, [$fallbackLang]);

			foreach($rules as $key => $rule) {

				if(strpos($rule, '|translate') !== false) {

					// default value
					$value = str_replace('|translate', '', $rule);
					$translated[$key.'_'.$fallbackLang] = $value;

					// other languages as optional
					$value = str_replace('required', 'nullable', $value);
					foreach($langs as $lang) { $translated[$key.'_'.$lang] = $value; }
				}
				else {
					$translated[$key] = $rule;
				}
			}
		}
		// replace "|translate" rule
		else {
			foreach($rules as $key => $rule) {
				$translated[$key] = str_replace('|translate', '', $rule);
			}
		}

		return $translated;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function getModelFromRoute(): ?string {

		$model = explode('.', $this->route()->getName());

		array_pop($model);
		if(count($model)<2) { return false; }

		return Str::plural( array_pop($model) );
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
