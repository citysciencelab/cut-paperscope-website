<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\App;

	// Laravel
	use App\Http\Controllers\Controller;
	use Illuminate\Support\Str;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Cache;
	use Illuminate\Support\Facades\Config;
	use Illuminate\Http\Request;
	use Illuminate\Http\JsonResponse;

	// App
	use App\Http\Controllers\App\Base\PageController;
	use App\Http\Resources\Base\ItemResource;
	use App\Http\Resources\Base\ItemListResource;
	use App\Http\Resources\Base\PageListResource;
	use App\Http\Requests\App\Base\ListWithFilterRequest;

	// App models
	use App\Models\App\Base\Item;
	use App\Models\App\Base\Page;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class AppController extends Controller {

	// model classes
	protected $modelClass = Item::class;
	protected $modelResourceClass = ItemResource::class;
	protected $modelListResourceClass = ItemListResource::class;

	// model relations
	protected $modelRelations = [];
	protected $modelListRelations = [];

	// return model list with pagination
	protected $paginator = false;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INDEX
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function showIndex(ListWithFilterRequest $request): mixed {

		// update user language
		$redirect = $this->redirectUserLanguage($request);
		if($redirect) { return redirect()->to($redirect); }

		// load initial data
		$config = $this->getConfig();
		$meta = $this->getMetaTags($request);
		$pages = $this->getPages($request);

		return view('app.pages.index',['config'=>$config,'meta'=>$meta,'pages'=>$pages]);
	}


	protected function redirectUserLanguage(ListWithFilterRequest $request): ?string {

		$user = Auth::user();
		if(!$user) { return null; }

		// find a forced language in url
		$availableLocales = Config::get('app.available_locales');
		if(in_array($request->segment(1),$availableLocales)) { return null; }

		// overwrite session with user language
		$request->session()->put('locale',$user->lang);
		app()->setLocale($user->lang);

		// redirect if user has other language than default
		if($user->lang != config('app.fallback_locale')) {

			$url = '/' . $user->lang . '/' . $request->path();
			$query = $request->getQueryString();
			$url = $query ? $url.'?'.$query : $url;
			return $url;
		}

		return null;
	}


	protected function getPages(ListWithFilterRequest $request) {

		$cacheKey = 'pages_' . app()->getLocale();

		// get data from cache or database
		return Cache::rememberForever($cacheKey, function () use ($request) {
			return PageListResource::collection( (new PageController())->loadPublicList($request) );
		});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	META TAGS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function getMetaTags(Request $request): array {

		$prefix = config('app.name') . ' | ';
		$url 	= config('app.url') . ($request->is('/') ? '' : $request->path());
		$slug	= $this->getSlugFromPath($request);
		$model	= null;

		// default tags
		$meta = [
			'title' => 			$prefix . __('Startseite'),
			'description' => 	__('meta.description'),
			'canonical' => 		$url,
			'og:url' => 		$url,
			'og:type' => 		'website',
			'og:site_name' => 	config('app.name'),
			'og:image' => 		config('app.url') . 'img/app/social/social-default.jpg',
		];
		$this->setMetaLanguage($meta);

		// shop pages
		if($request->is('shop','*/shop')) { $meta['title'] = $prefix . __('Shop'); }

		// model pages
		//elseif($request->is('item/*','*/item/*')) 	{ $model = Item::whereSlug($slug)->public()->first(); }

		// try a dynamic page
		else { $model = Page::whereSlug($slug)->public()->first(); }

		// set meta tags from model
		if($model) { $this->setMetaTagsFromModel($model,$meta,$prefix); }

		// add OpenGraph tags
		if(!isset($meta['og:title'])) 		{ $meta['og:title'] = $meta['title']; }
		if(!isset($meta['og:description'])) { $meta['og:description'] = $meta['description']; }

		// ensure ascending "/" (Bugfix Cloudfront sharing)
		$meta['canonical'] 	= Str::finish($meta['canonical'], '/');
		$meta['og:url'] 	= Str::finish($meta['og:url'], '/');

		return $meta;
	}


	protected function getSlugFromPath(Request &$request): string {

		$slug = explode('/', $request->path());
		$slug = array_pop($slug);
		return $slug;
	}


	protected function setMetaTagsFromModel(mixed &$model, array &$meta, string $prefix): void {

		if(!$model) { return; }
		$locale = app()->getLocale();

		$meta['title']			= $this->getTranslatedProperty($model,$locale,'meta_title') ?? $meta['title'];
		$meta['description']	= $this->getTranslatedProperty($model,$locale,'meta_description') ?? $meta['description'];
		$meta['og:description']	= $this->getTranslatedProperty($model,$locale,'social_description') ?? $meta['description'];
		$meta['og:image']		= $this->getTranslatedProperty($model,$locale,'social_image') ?? $meta['og:image'];

		if(!Str::contains($meta['title'],$prefix)) { $meta['title'] = $prefix . $meta['title']; }
	}


	protected function setMetaLanguage(array &$meta): void {

		$meta['languages'] = [];

		// get config languages
		$locale = app()->getLocale();
		$languages = config('app.available_locales');

		// iterate all available languages
		foreach($languages as $lang) {

			// set correct locale format
			$val = $lang . '_' . Str::upper($lang);
			if($val=='en_EN') { $val = 'en_US'; }

			// set current language
			if($lang == $locale) { $meta['og:locale'] = $val; }
			// set available language
			else { array_push($meta['languages'], $val); }
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MODEL LIST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getPublicList(ListWithFilterRequest $request, string $modelClass=null): JsonResponse {

		$results = $this->loadPublicList($request, $modelClass);
		return $this->responseList($results);
	}


	public function loadPublicList(ListWithFilterRequest $request, string $modelClass=null, $relations=null): mixed {

		$modelClass = $modelClass ?? $this->modelClass;
		$relations = $relations ?? $this->modelListRelations;

		/** @var \App\Models\BaseModel $modelClass **/
		$usePublished = $modelClass::$usePublished;

		// prepare db statement
		$stmt = $modelClass::with($relations);

		// apply statement filters
		$this->applyFilterWhere($stmt, $request, $usePublished);
		$this->applyFilterOrder($stmt, $request, $usePublished);
		$this->applyFilterTypes($stmt, $request);
		$this->applyFilterTags($stmt, $request);

		// execute db statement
		$results = null;
		if($this->paginator) 	{ $results = $stmt->paginate( $this->getPaginatorRange() );	}
		else 					{ $results = $stmt->get(); }

		// apply result filters
		$this->applyFilterResults($results,$request);

		return $results;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MODEL LIST FILTER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function applyFilterWhere(&$stmt, ListWithFilterRequest &$request, bool $usePublished) {

		$stmt->public();

		if($usePublished) {	$stmt->published(); }

		return $stmt;
	}


	protected function applyFilterOrder(&$stmt, ListWithFilterRequest &$request, bool $usePublished) {

		$validated = $request->validated();

		// order filter in request
		if($validated->order) {
			$stmt->orderBy($usePublished ? 'published_start' : 'created_at', $validated->order == 'newest' ? 'desc' : 'asc' );
		}

		// default order
		else {
			$stmt->orderBy('order','asc');
			$usePublished ? $stmt->orderBy('published_start','desc') : $stmt->orderBy('created_at','desc'); 	// published_start, desc = newest on top
		}

		return $stmt;
	}


	protected function applyFilterTypes(&$stmt, ListWithFilterRequest &$request) {

		$validated = $request->validated();

		if(is_array($validated->types) && count($validated->types)>0) {
			$stmt->whereIn('type',$validated->types);
		}

		return $stmt;
	}


	protected function applyFilterTags(&$stmt, ListWithFilterRequest &$request) {

		$validated = $request->validated();

		// skip if no tags relation
		if(!method_exists($stmt->getModel(),'tags')) { return $stmt; }

		/*
		if(is_array($validated->tags) && count($validated->tags)>0) {
			$stmt->whereHas('tags', function($query) use ($validated) {
				$query->whereIn('slug', $validated->tags);
			});
		}

		return $stmt;
		*/
	}


	protected function applyFilterResults(&$results, ListWithFilterRequest &$request) {

		$validated = $request->validated();

		/* if(!empty($validated->tags)) {

			// iterate all results
			$results = $results->filter(function($res) use ($validated) {
				return $res->tags->contains(function($tag) use ($validated) { return in_array($tag->slug, $validated->tags); });
			})->all();
		}*/

		return $results;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MODEL GET
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getPublic(string $id=null): JsonResponse {

		$item = $this->loadPublic($id);
		return $this->responseGet($item);
	}


	public function loadPublic(string $id=null): mixed {

		$stmt = $this->modelClass::with($this->modelRelations)->whereId($id)->public();
		if($this->modelClass::$usePublished) { $stmt->published(); }

		return $stmt->first();
	}


	public function getPublicBySlug(string $slug=null): JsonResponse {

		$item = $this->loadPublicBySlug($slug);
		return $this->responseGet($item);
	}


	protected function loadPublicBySlug(string $slug=null): mixed {

		$stmt = $this->modelClass::with($this->modelRelations)->whereSlug($slug)->public();
		if($this->modelClass::$usePublished) {	$stmt->published(); }

		return $stmt->first();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
