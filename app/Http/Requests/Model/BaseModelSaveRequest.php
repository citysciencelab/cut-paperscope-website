<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// 	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Requests\Model;

	// Laravel
	use Illuminate\Support\Facades\Auth;
	use Cocur\Slugify\Slugify;

	// App
	use App\Http\Requests\BaseFormRequest;
	use App\Traits\LangKeysTrait;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// 	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class BaseModelSaveRequest extends BaseFormRequest {

	use LangKeysTrait;

	protected $target = 'items';
	protected $targetClass = \App\Models\App\Base\Item::class;


	public function authorize(): bool {

		$user = Auth::user();

		// allowed to create and edit
		return $user && $user->hasPermissionTo('create ' . $this->target) && $user->hasPermissionTo('edit ' . $this->target);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	VALIDATE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function prepareForValidation(): void {

		// if new model, set slug property for correct "unique" test
		if(empty($this->id) && empty($this->slug) && $this->targetClass::$useSlug) {

			$slug = (new Slugify())->slugify($this->name ?? '');
			$this->merge(array_replace_recursive($this->all(), ['slug' => $slug ]));
		}
	}


	protected function getBaseRules(array $additionalRules = []): array {

		$langs = $this->getLangKeys();
		$fallbackLang = config('app.fallback_locale');

		$baseRules = [

			'id' =>				'bail|sometimes|uuid',
			'name' =>			'bail|required|string|max:50',
			'slug' =>		 	'bail|nullable|string|regex:/^[a-z0-9](-?[a-z0-9])*$/|unique:'.$this->target.',slug,'.$this->id.'|max:100',
			'public' => 		'bail|nullable|boolean',
			'preview' => 		'bail|nullable|boolean',
		];

		// published properties
		if(isset($this->targetClass::$usePublished) && $this->targetClass::$usePublished) {

			$baseRules['published_start'] = 'bail|required|date_format:"j.n.Y H:i"';
			$baseRules['published_end'] = 'bail|nullable|date_format:"j.n.Y H:i"|after_or_equal:published_start';
		}

		// page properties
		if(in_array('page', $additionalRules)) {

			$baseRules['navi_visible'] = 'bail|required|boolean';

			// add translated properties
			foreach($langs as $lang) {
				$isDefaultLang = $lang == '_'.$fallbackLang || $lang == '';
				$baseRules['navi_label'.$lang] = 'bail|nullable|string|max:30'.($isDefaultLang?'|required_if:navi_visible,true':'');
			}
		}

		// sharing properties
		if(in_array('page', $additionalRules) || in_array('sharing', $additionalRules)) {

			// add translated properties
			foreach($langs as $lang) {
				$baseRules['meta_title'.$lang] = 			'bail|nullable|string|max:30';
				$baseRules['meta_description'.$lang] = 		'bail|nullable|string|max:160';
				$baseRules['social_description'.$lang] = 	'bail|nullable|string|max:297';
				$baseRules['social_image'.$lang] = 			$this->rule('input-image-upload');
			}
		}

		return $baseRules;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	ERROR MESSAGES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// rename variables for form error messages
	public function attributes(): array {

		return [

			...parent::attributes(),

			'slug' => 				trans('URL'),
			'published_start' => 	trans('Startdatum'),
			'published_end' => 		trans('Enddatum'),

			'title' => 				trans('Title'),
			'navi_label' => 		trans('Bezeichner Hauptnavi'),
			'navi_visible' => 		trans('Sichtbarkeit Hauptnavi'),
		];
	}


	public function messages(): array {

		return [

			...parent::messages(),
		];
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
