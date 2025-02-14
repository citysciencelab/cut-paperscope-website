<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Resources\Base;

	// Laravel
	use Illuminate\Http\Request;
	use Illuminate\Http\Resources\Json\JsonResource;
	use Illuminate\Support\Str;
	use Illuminate\Support\Carbon;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Http\Resources\MissingValue;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class BaseModelResource extends JsonResource {

	protected Request $request;

	protected $isBackendRequest = false;
	protected $isPreviewRequest = false;

	protected $translatedProps = [];



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	PROPS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function toArray(Request $request): array {

		$props = array_merge(
			$this->getBaseProperties($request),
			$this->getProps($request),
			$this->translatedProps,
		);

		// auto convert props
		foreach($props as $key => $val) {
			if($val instanceof Carbon) { $props[$key] = $val->format('j.n.Y H:i'); }
			else if($this->isJsonString($val)) { $props[$key] = json_decode($val); }
		}

		return $props;
	}


	public function getProps(Request $request): array {

		return [];
	}


	protected function isJsonString(mixed $value): bool {

		return is_string($value) && Str::startsWith($value,'{') && Str::endsWith($value,'}');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	BASE PROPERTIES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getBaseProperties(Request $request): array {

		$this->request = $request;
		$this->validateBackendRequest();
		$this->validatePreviewRequest();

		$props = ['id' => $this->id];

		// model name as type
		$props['type'] = Str::snake(class_basename($this->resource));

		// add default properties
		$props['name'] 		= $this->when($this->isBackendRequest, $this->name);
		$props['slug'] 		= $this->whenHas('slug');
		$props['public'] 	= $this->when(isset($this->public) && ($this->isBackendRequest || $this->isPreviewRequest), $this->public);
		$props['order'] 	= $this->whenHas('order');

		// add published properties
		$props['published_start'] 	= $this->whenHas('published_start');
		$props['published_end'] 	= $this->whenHas('published_end');

		// add page properties
		$isPage = isset($this->navi_label) || isset($this->navi_label_de )|| isset($this->navi_label_en);
		if($isPage) {
			$props['navi_label'] 	= $this->translate('navi_label');
			$props['navi_visible'] 	= $this->translate('navi_visible');
		}

		// add social sharing properties
		$isSharing = isset($this->meta_title) || isset($this->meta_title_de) || isset($this->meta_title_en);
		if($isSharing) {
			$props['meta_title']			= $this->translate('meta_title');
			$props['meta_description']		= $this->translate('meta_description');
			$props['social_description']	= $this->translate('social_description');
			$props['social_image']			= $this->translate('social_image');
		}

		return $props;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	AUTH
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function validateBackendRequest(): void {

		// exit if not correct backend header
		if($this->request->header('X-Context') !== 'backend') { return; }

		// exit if not correct backend referrer
		if(!Str::contains($this->request->header('referer',''),'backend/')) { return; }

		$this->isBackendRequest = true;
	}


	protected function validatePreviewRequest(): void {

		$me = Auth::user();
		if(!$me) { return; }

		if($this->request->header('X-Preview') == $me->id && $me->isBackendUser()) {

			$this->isPreviewRequest = true;
		}
	}


	protected function addBackendProperties(array $props): mixed {

		// check if user is backend user
		if(!Auth::check() || !Auth::user()->isBackendUser()) { return new MissingValue; }

		// check if backend request
		if(!$this->isBackendRequest) { return new MissingValue; }

		return $this->merge($props);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HELPER RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getSingleRelation(string $targetClass, string $relationName): mixed {

		// check if relation is loaded
		if(!$this->resource->relationLoaded($relationName)) { return new MissingValue; }

		return new $targetClass($this[$relationName]);
	}


	public function getManyRelation(string $targetClass, string $relationName): mixed {

		// check if relation is loaded
		if(!$this->resource->relationLoaded($relationName)) { return new MissingValue; }
		$collection = $targetClass::collection($this[$relationName]);

		return $collection->sortBy(function($item) { return $item->pivot->order; })->values()->all();
	}


	public function getChildRelation(string $targetClass, string $relationName): mixed {

		// check if relation is loaded
		if(!$this->resource->relationLoaded($relationName)) { return new MissingValue; }

		$collection = $targetClass::collection($this[$relationName]);

		return $collection->sortBy('order')->values()->all();
	}


	public function getJsonContent($jsonString): mixed {

		if(empty($jsonString)) { return null; }

		return json_decode($jsonString);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	TRANSLATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function translate(string $prop) {

		if(!config('app.features.multi_lang')) { return $this[$prop]; }

		$activeLocale 		= app()->getLocale();
		$availableLocales 	= config('app.available_locales');
		$fallbackLocale 	= config('app.fallback_locale');

		$activeProp 		= $prop.'_'.$activeLocale;
		$fallbackProp 		= $prop.'_'.$fallbackLocale;

		// add all languages on backend routes for model editing
		if($this->isBackendRequest) {

			foreach($availableLocales as $lang) {
				$this->translatedProps[$prop.'_'.$lang] = $this[$prop.'_'.$lang];
			}
		}

		$propVal = null;

		// get property in active language if available
		if(!empty($this[$activeProp]) && $this[$activeProp] != '{}') {
			$propVal = $this[$activeProp];
		}
		// fallback to default language if missing translation
		else if(!empty($this[$fallbackProp])) {
			$propVal = $this[$fallbackProp];
		}
		// defaut prop without language suffix
		else {
			$propVal = $this->whenHas($prop);
		}

		// recursive translation for json content
		if($this->isJsonString($propVal)) {

			$propVal = json_decode($propVal);
			$default = json_decode($this[$fallbackProp] ?? $this[$prop] ?? '{}');

			$trans = $this->translateJsonRecursive($propVal, $activeLocale);
			$default = $this->translateJsonRecursive($default, $fallbackLocale);

			// overwrite default with translation
			$propVal = array_replace_recursive($default, $trans);
		}

		return $propVal;
	}


	private function translateJsonRecursive($data, $locale): mixed {

		$res = collect($data)->mapWithKeys(fn($val, $key) =>
			[Str::replaceLast('_'.$locale, '', $key) => is_array($val) || is_object($val) ? $this->translateJsonRecursive($val,$locale) : $val]
		)->all();

		return array_filter($res, fn($v) => !empty($v));
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
