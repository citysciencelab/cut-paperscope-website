<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\Backend;

	// Laravel
	use App\Http\Controllers\Controller;
	use Mavinoo\Batch\BatchFacade as Batch;
	use Illuminate\Http\Request;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Support\Str;
	use Illuminate\Support\Carbon;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Cache;
	use Illuminate\Database\Eloquent\Model;

	// App
	use App\Models\BaseModel;
	use App\Models\App\Base\Item;
	use App\Http\Resources\Base\ItemResource;
	use App\Http\Requests\Model\SortRequest;
	use App\Http\Requests\Model\SearchRequest;
	use App\Http\Requests\App\Base\ListWithFilterRequest;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class BackendController extends Controller {

	// model classes
	protected $modelClass = Item::class;
	protected $modelResourceClass = ItemResource::class;
	protected $modelListResourceClass = ItemResource::class;

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


	public function showIndex(Request $request): mixed {

		// only for backend users
		if(Auth::user() && !Auth::user()->isBackendUser()) {
			return redirect('/');
		}

		$config = $this->getConfig();

		return view('backend.pages.index',['config'=>$config]);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MODEL LIST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getBackendList(ListWithFilterRequest $request, string $modelClass=null): JsonResponse {

		$results = $this->loadBackendList($request, $modelClass);
		return $this->responseList($results);
	}


	public function loadBackendList(ListWithFilterRequest $request, string $modelClass=null, string $relations=null): mixed {

		$modelClass 	= $modelClass ?? $this->modelClass;
		$relations		= $relations ?? $this->modelListRelations;

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


	public function getBackendChildListSorted(string $parent=null): JsonResponse {

		$relations = $this->modelListRelations;

		$results = $this->modelClass::with($relations)->where('parent_id',$parent)->orderBy('order','asc')->orderBy('created_at','desc')->get();
		return $this->responseList($results);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MODEL LIST FILTER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function applyFilterWhere(&$stmt, ListWithFilterRequest &$request, bool $usePublished) {

		return $stmt;
	}


	protected function applyFilterOrder(&$stmt, ListWithFilterRequest &$request, bool $usePublished) {

		$validated = $request->validated();

		// direction filter in request
		if($validated->direction && $validated->direction_property) {
			$stmt->orderBy($validated->direction_property, $validated->direction);
		}

		// order filter in request
		elseif($validated->order) {
			$stmt->orderBy($usePublished ? 'published_start' : 'created_at', $validated->order == 'newest' ? 'desc' : 'asc' );
		}

		// default order
		else {
			$stmt->orderBy('order','asc');
			$stmt->orderBy($usePublished ? 'published_start' : 'created_at','desc'); 	// published_start, desc = newest on top
		}

		return $stmt;
	}


	protected function applyFilterTypes(&$stmt, ListWithFilterRequest &$request) {

		$validated = $request->validated();

		/*
		if(is_array($validated->types) && count($validated->types)>0) {
			$stmt->whereIn('type',$validated->types);
		}

		return $stmt;
		*/
	}


	protected function applyFilterTags(&$stmt, ListWithFilterRequest &$request) {

		$validated = $request->validated();

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

		/* if(is_array($validated->tags) && count($validated->tags)>0) {

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


	public function getBackend(string $id=null): JsonResponse {

		$item = $this->modelClass::with($this->modelRelations)->whereId($id)->first();
		return $this->responseGet($item);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MODEL SEARCH
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function search(SearchRequest $request): JsonResponse {

		$validated = $request->validated();

		$stmt = $this->modelClass::search($validated->value);

		if($validated->direction && $validated->direction_property) {
			$stmt->orderBy($validated->direction_property, $validated->direction);
		}

		if($this->paginator) {
			$items = $stmt->paginate($this->getPaginatorRange());
		}
		else {
			$items = $stmt->get();
		}

		return $this->responseList($items);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MODEL SORT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function sortModel(SortRequest $request): JsonResponse {

		$validated = $request->validated();

		// process data
		$items = $validated->items;
		$ids = array_keys($items);

		// create update model data
		$update = [];
		foreach($ids as $i) {
			array_push($update, ['id' => $i, 'order' => $items[$i]] );
		}

		// update all items in one db query
		Batch::update(new $this->modelClass, $update, 'id');

		Cache::flush();

		return $this->responseData(trans('api.sorted'), 'message');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	AUTH
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function isBackendRequest(Request $request): bool {

		// request must contain backend header
		if($request->header('X-Context') !== 'backend') { return false; }

		// referrer must contains "backend"
		if(!Str::contains($request->header('referer',''),'backend/')) { return false; }

		return true;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SAVE HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getInputJson(mixed $json): string|bool {

		if(empty($json) || $json=='[]' || (is_array($json) && count($json)==0)) { return '{}'; }

		return json_encode($json);
	}


	public function saveManyRelation(string $relationName, mixed $validated, BaseModel $target, array $pivotAttrs = []): void {

		// wait for saved model to always have an id
		$target->saved(function($model) use ($relationName, $validated, $pivotAttrs) {

			if(empty($validated->{$relationName})) {return$model->$relationName()->sync([]); }

			// create relation data
			$attach = [];
			for($i=0; $i<count($validated->{$relationName}); $i++) {
				$item = $validated->{$relationName}[$i];
				$attach[$item['id']] = [
					'order' => $i,
					...array_intersect_key($item, array_flip($pivotAttrs)),
				];
			}

			$model->$relationName()->sync($attach);
		});
	}


	public function saveParentRelation(mixed $validated, Model $target): void {

		// find class identifier
		$modelName = match($validated->parent_type) {
			'item' =>		'App\\Models\\App\\Base\\Item',
			'page' =>		'App\\Models\\App\\Base\\Page',
			'fragment' =>	'App\\Models\\App\\Base\\Fragment',
			'product' =>	'App\\Models\\Shop\\Product',
			default =>		'App\\Models\\App\\' . Str::ucfirst($validated->parent_type),
		};

		$target->parent_type = $modelName;
		$target->parent_id = $validated->parent_id;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
