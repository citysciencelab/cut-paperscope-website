<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers;

	// Laravel
	use Illuminate\Routing\Controller as BaseController;
	use Illuminate\Support\Str;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\File;
	use Illuminate\Support\Facades\Redis;
	use Illuminate\Support\Facades\Cache;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Cocur\Slugify\Slugify;

	// Traits
    use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
    use Illuminate\Foundation\Validation\ValidatesRequests;

	// App
	use App\Models\BaseModel;
	use App\Models\Auth\User;
	use App\Models\App\Base\Item;
	use App\Http\Requests\Model\DeleteRequest;
	use App\Http\Resources\Base\ItemResource;
	use App\Http\Resources\Base\ItemListResource;
	use App\Traits\LangKeysTrait;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class Controller extends BaseController {

	// Traits
	use AuthorizesRequests, ValidatesRequests, LangKeysTrait;

	// model classes
	protected $modelClass = Item::class;
	protected $modelResourceClass = ItemResource::class;
	protected $modelListResourceClass = ItemListResource::class;

	// model relations
	protected $modelRelations = [];
	protected $modelListRelations = [];

	// return model list with pagination
	protected $paginator = false;

	// every available language key with a prefix "_" for dynamic model properties
	protected $langKeys = [];


	public function __construct() {

		$this->langKeys = $this->getLangKeys();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CONFIG
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * Get config data for Vue frontend to be included in the initial response from the webserver.
	 *
	 * Data is available in frontend as window.config.
	 * If a logged in user is available, minimal user data is included to provide a basic user context.
	 *
	 * @return array
	 */

	public function getConfig(): array {

		$path = parse_url(config('app.url'))['path'];

		$config = [

			'app_name' => 				config('app.name') ,
			'app_user' => 				null,

			'active_locale' =>			app()->getLocale(),
			'available_locales' =>		config('app.features.multi_lang') ? config('app.available_locales') : [config('app.fallback_locale')],
			'fallback_locale' => 		config('app.fallback_locale'),

			'base_url' => 				config("app.url"),
			'base_path' => 				$path,

			'storage_default' => 		config('filesystems.default'),
			'storage_url_public' =>		config('filesystems.disks.public.url'),
			'storage_url_s3' => 		config('filesystems.disks.s3.url'),

			'cookie_enabled' => 		config('cookie-consent.enabled'),
			'cookie_name' => 			config('cookie-consent.cookie_name'),
			'cookie_categories' => 		config('cookie-consent.cookie_categories'),
			'cookie_expires' => 		config('cookie-consent.cookie_lifetime'),
			'cookie_domain' => 			config('session.domain'),
			'cookie_path' => 			$path,

			'hash' => 					$this->getViteHash(),
		];

		// if authenticated, include minimal user data
		if($user = Auth::user()) {

			$config['app_user'] = [
				'id' => 			$user->id,
				'name' => 			$user->name,
				'surname' => 		$user->surname,
				'fullname' => 		$user->fullname,
				'username' => 		$user->username,
				'lang' => 			$user->lang,
				'image' => 			$user->image,
				'isVerified' => 	$user->isVerified(),
				'sso_driver' => 	$user->sso_driver,
				'role' =>  			$user->roles->first()->name ?? 'guest',
			];
		}

		// enable broadcasting
		$reverb = config('reverb.apps.apps')[0];
		if($reverb['key']) {
			$config['reverb'] = [
				'key' => $reverb['key'],
				'host' => $reverb['options']['host'],
				'port' => $reverb['options']['port'],
				'forceTLS' =>$reverb['options']['useTLS'],
			];
		}

		return $config;
	}


	/**
	 * Get a unique hash string from the manifest file.
	 *
	 * Used to invalidate cache for frontend assets on a new production build.
	 *
	 * @return string
	 */

	private function getViteHash() {

		return Cache::rememberForever('vite_hash', function () {

			return md5_file(public_path('build/manifest.json'));
		});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	RESPONSE LIST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * Return a json response for api requests with a list of items. Optionally include pagination data.
	 *
	 * Structure of the response: {status: 'success', data: [items]}
	 *
	 * @param mixed $items
	 * @param string $modelListResourceClass	Override the default Controller::modelListResourceClass
	 *
	 * @return JsonResponse
	 */

	public function responseList(mixed &$items, string $modelListResourceClass=null): JsonResponse {

		$response = [
			'status' => 'success',
			'data' => $modelListResourceClass ?
				$modelListResourceClass::collection($items)
				: $this->modelListResourceClass::collection($items),
		];

		// check for a paginated collection	(Illuminate\Pagination\Paginator)
		if($this->paginator && isset($items->onEachSide)) {
			$response['paginator'] 		= true;
			$response['currentPage'] 	= $items->currentPage();
			$response['nextPageUrl'] 	= $items->nextPageUrl();
			$response['prevPageUrl'] 	= $items->previousPageUrl();
			$response['pages'] 			= $items->lastPage();
		}

		return response()->json($response, 200);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	RESPONSE GET
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * Return a json response for api requests with a single item. Return an api error response if no item is found.
	 *
	 * Structure of the response: {status: 'success', data: item}
	 *
	 * @param mixed $item
	 * @param string $modelResourceClass	Override the default Controller::modelResourceClass
	 *
	 * @return JsonResponse
	 */

	public function responseGet(mixed $item=null, string $modelResourceClass=null): JsonResponse {

		if($item) {
			return $this->responseData($modelResourceClass ? new $modelResourceClass($item) : new $this->modelResourceClass($item));
		}
		else {
			return $this->responseError();
		}
	}


	/**
	 * Return a json response for api requests with custom data.
	 *
	 * Structure of the response: {status: 'success', data: item}
	 *
	 * @param mixed $data
	 * @param string $property	Override the default 'data' property
	 * @param array $mergeData	Additional data to merge with the response (root level)
	 *
	 * @return JsonResponse
	 */

	public function responseData(mixed $data=null, string $property='data', array $mergeData=null): JsonResponse {

		$response = ['status' => 'success'];

		if($data) { $response[$property] = $data; }
		if($mergeData) { $response = array_merge($response, $mergeData); }

		return response()->json($response, 200);
	}


	public function responseSuccess(): JsonResponse {

		return $this->responseData();
	}


	/**
	 * Return a json response for api requests with an error message.
	 *
	 * Structure of the response: {status: 'error', message: 'error message'}
	 *
	 * @param int $errorCode		HTTP error code
	 * @param string $message		Custom error message
	 *
	 * @return JsonResponse
	 */

	public function responseError(int $errorCode = 404, string $message = null): JsonResponse {

		$message = $message ?? $this->mapErrorWithMessages($errorCode);

		return response()->json(['status' => 'error', 'message' => trans($message)], $errorCode);
	}


	protected function mapErrorWithMessages(int $errorCode): string {

		switch($errorCode) {
			case 401: 	return 'api.unauthorized';
			case 403: 	return 'api.forbidden';
			case 422: 	return 'api.unauthorized';
			case 502: 	return 'api.bad_gateway';
			case 503: 	return 'api.service_unavailable';
			default: 	return 'api.not_found';
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	PAGINATOR
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * Get the count of items to be returned in a paginated list.
	 *
	 * The value is taken from the request parameter 'range' and is limited to a range of 1-100.
	 *
	 * @return int
	 */

	public function getPaginatorRange(): int {

		$value = request('range',25);

		// validate input
		$value = intval($value);
		if($value<1 || $value>100) { $value = 25;}

		return $value;
	}


	public function enablePaginator(): void { $this->paginator = true; }



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MODEL SAVE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function saveBaseModel(Request $request, array $additionalRules = []): BaseModel {

		$validated = $request->validated();

		// get model or make a new one
		$model = $this->modelClass::firstOrNew(['id'=>$validated->id]);

		// save base properties
		$model->name	= $validated->name;
		$model->public	= $validated->public ?? false;

		// save slug property
		if($this->modelClass::$useSlug)	{
			$model->slug = $this->createSlug($validated->slug ?? '',$validated->name);
		}

		// save published properties
		if($this->modelClass::$usePublished) {
			$model->published_start	= $validated->published_start;
			$model->published_end	= $validated->published_end;
		}

		// save page properties with multi lang support
		if(in_array('page',$additionalRules)) {

			$model->navi_visible = $validated->navi_visible;

			foreach($this->langKeys as $lang) {
				$model['navi_label'.$lang] = $validated->{'navi_label'.$lang};
			}
		}

		// save sharing properties with multi lang support
		if(in_array('page',$additionalRules) || in_array('sharing',$additionalRules)) {

			foreach($this->langKeys as $lang) {
				$model['meta_title'.$lang]			= $validated->{'meta_title'.$lang};
				$model['meta_description'.$lang]	= $validated->{'meta_description'.$lang};
				$model['social_description'.$lang]	= $validated->{'social_description'.$lang};
				$model['social_image'.$lang]		= $validated->{'social_image'.$lang};
			}
		}

		// reset cache after save
		$model->saved(fn($model) => Cache::flush());

		return $model;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MODEL DELETE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function deleteModel(DeleteRequest $request): JsonResponse {

		$validated = $request->validated();

		$item = $this->modelClass::find($validated->id);
		if(!$item) { return $this->responseError();	}

		// delete model
		$item->delete();

		Cache::flush();

		return $this->responseData(trans('api.deleted'), 'message');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MODEL HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function createSlug(string $slugInput, string $default = ''): string {

		$slugify = new Slugify();

		// throw error if no input is given
		if(empty($slugInput) && empty($default)) {
			throw new \Exception('No slug input given');
		}

		return $slugify->slugify( empty($slugInput) ? $default : $slugInput );
	}


	protected function isPreviewRequest(): bool {

		$me = Auth::user();
		if(!$me) { return false; }

		// request must be sent with correct header
		if(request()->header('X-Preview') == $me->id && $me->isBackendUser()) { return true; }

		// no access
		return false;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	TRANSLATION HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * Return a property value in the active language if available (title_en) or in fallback language (title_de).
	 *
	 * @param mixed $model			Model instance
	 * @param string $locale		language code
	 * @param string $property		property name of model without language code (title)
	 *
	 * @return mixed
	 */

	protected function getTranslatedProperty(mixed &$model, string $locale, string $property): mixed {

		// skip if property is not translatable
		if(!$this->hasTranslatedProperty($model, $property)) {
			return $model[$property];
		}

		$valKey = '';
		$fallbackLocale = config('app.fallback_locale');

		// get property name in active language if available
		if($locale != $fallbackLocale && !empty($model[$property.'_'.$locale])) {
			$valKey = $property . '_' . $locale;
		}
		// fallback to default language if missing translation
		else {
			$valKey = $property. '_'.$fallbackLocale;
		}

		return $model[$valKey];
	}


	/**
	 * Check if a property has a translation by testing property with all language suffixes (title_de, title_en).
	 *
	 * @param mixed $model			Model instance
	 * @param string $property		property name of model without language code (title)
	 *
	 * @return bool
	 */

	protected function hasTranslatedProperty(mixed &$model, string $property): bool {

		$availableLocales = config('app.available_locales');

		foreach($availableLocales as $locale) {
			if(!empty($model[$property.'_'.$locale])) { return true;}
		}

		return false;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	NATIVE APP
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function isNativeApp(Request $request): bool {

		return $request->header('X-Native-App', null) != null;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	LOGOUT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function logoutFromAllDevices(User $user): void {

		// remove user from session driver
		if(config('session.driver')=='file') { $this->logoutDriverFile($user);	}
		else if(config('session.driver')=='redis') { $this->logoutDriverRedis($user); }

		// remove app tokens
		$user->tokens()->delete();

		// update user properties
		$user->remember_token = null;
		$user->save();

		// logout local user
		$me = Auth::user();
		if($me && $me->id == $user->id) { Auth::logout(); }
	}


	private function logoutDriverFile(User $user): void {

		// init search properties
		$path = config('session.files');
		$search = $user->getAuthPassword();
		$files = scandir($path,1);

		// find active session file for user
		foreach ($files as $lines){
			if(strlen($lines) > 20){

				$readfile = fopen($path.'/'.$lines, 'r');
				while(!feof($readfile)) {
					$contents = fgets($readfile);
					if(strpos($contents, $search) !== false) {
						File::delete($path.'/'.$lines);
					}
				}
				fclose($readfile);
			}
		}
	}


	private function logoutDriverRedis(User $user): void {

		$slug = Str::slug(config('app.name'),'_');
		$search = $user->getAuthPassword();

		// get all redis session keys
		$keys = array_map(fn($key) => str_replace($slug.'_database_', '', $key), Redis::keys('*'));

		// iterate redis session keys
		foreach($keys as $key) {

			$session = Redis::get($key);

			if(strpos($session, $search) !== false) {
				Redis::del($key);
			}
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}	// end class


