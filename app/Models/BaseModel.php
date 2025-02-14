<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Models;

	// Laravel
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Builder;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Facades\Auth;

	// Laravel Traits
	use Illuminate\Database\Eloquent\Concerns\HasUuids;
	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Laravel\Scout\Searchable;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class BaseModel extends Model {

	// Traits
	use HasUuids, HasFactory, Searchable;

	// The attributes that are mass assignable.
	protected $fillable = [
		'name', 'slug', 'public', 'order',
		'navi_label','navi_visible',
		'meta_title', 'meta_description',
		'social_description', 'social_image'
	];


	protected $translateFillable = [

	];

	// properties for model features
	public static $useSlug = true;			// a unique and url friendly identifier for the model
	public static $usePublished = false;	// use columns that defines visibility on website
	public static $useSearch = false;		// use search features for this model


	public function __construct(array $attributes = []) {

		parent::__construct($attributes);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INIT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/*
	*	Merge fillable properties from child models with BaseModel.
	*/

	public function mergeFillable(array $fillable, array $translateFillable = []) {

		$this->translateFillable = $translateFillable;
		$this->fillable = array_merge($this->fillable, $fillable);

		// remove duplicates of default properties first
		$this->fillable = array_diff($this->fillable, ['slug', 'published_start', 'published_end']);

		// add slug property
		if(static::$useSlug) { $this->fillable[] = 'slug'; }

		// add published properties
		if(static::$usePublished) {
			$this->fillable = array_merge($this->fillable, ['published_start', 'published_end']);
			$this->casts['published_start'] = 'datetime';
			$this->casts['published_end'] = 'datetime';
		}

		// replace meta and social if with translations
		if(config('app.features.multi_lang')) {

			$langs = config('app.available_locales');

			// remove default properties first
			$this->fillable = array_diff($this->fillable, [
				'navi_label', 'meta_title', 'meta_description', 'social_description', 'social_image'
			]);

			// add default translated properties
			$this->translateFillable = array_merge($this->translateFillable, [
				'navi_label', 'meta_title', 'meta_description', 'social_description', 'social_image',
			]);


			// add translated properties to fillables
			foreach($langs as $lang) {
				foreach($this->translateFillable as $property) {
					$this->fillable[] = $property.'_'.$lang;
				}
			}
		}

		// merge default casts for properties
		$this->casts['public'] = 'boolean';
		$this->casts['order'] = 'integer';
		$this->casts['navi_visible'] = 'boolean';

		return $this;
	}


	public function getTranslationProps(): array {

		return $this->translateFillable;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	STORAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getStorageDiskAttribute() {

		return config('filesystems.default');
	}


	public function getStorageFolderAttribute() {

		$folderParent 	= $this->getTable();
		$folderTarget 	= $this->id;
		$monthFolder 	= $this->created_at->format('Y-m');

		return $folderParent . '/' . $monthFolder . '/' . $folderTarget . '/';
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getManyRelation(string $classPath, string $orderProperty = 'order') {

		$stmt = $this->belongsToMany($classPath)->withTimestamps()->orderByPivot($orderProperty);

		// all items in backend
		if($this->userHasAccessToBackend()) {
			return $stmt->withPivot('order');
		}

		// only public items
		return $stmt->public()->withPivot('order');
	}


	public function getSingleRelation(string $classPath, string $foreignKey = null) {

		return $this->belongsTo($classPath, $foreignKey);
	}


	public function getChildRelation(string $classPath) {

		// all items in backend
		if($this->userHasAccessToBackend()) {
			return $this->morphMany($classPath,'parent')->orderBy('created_at','desc')->orderBy('order','asc');
		}

		// only public items
		return $this->morphMany($classPath,'parent')->published()->public()->orderBy('created_at','desc')->orderBy('order','asc');
	}


	public function getParentRelation(string $relationName = 'parent') {

		if($this->userHasAccessToBackend()) {
			return $this->morphTo($relationName);
		}

		return $this->morphTo($relationName)->public();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	QUERY HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function scopePublic(Builder $builder) {

		// skip if preview request
		if($this->isPreviewRequest()) { return $builder; }

		return $builder->where('public',1);
    }


	public function scopePublished(Builder $builder) {

		// skip if preview request
		if($this->isPreviewRequest()) { return $builder; }

		return $builder->where('published_start', '<=', now())->where(function($query) {
			$query->whereNull('published_end')->orWhere('published_end', '>=', now());
		});
    }



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	AUTH HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function userHasAccessToBackend() {

		// user must be logged in with backend roles
		if(!Auth::check() || !Auth::user()->isBackendUser()) { return false; }

		// request must be sent with correct backend header
		if(request()->header('x-context') == 'backend') { return true; }

		// no access
		return false;
	}


	protected function isPreviewRequest() {

		$me = Auth::user();
		if(!$me) { return false; }

		// request must be sent with correct header
		if(request()->header('X-Preview') == $me->id && $me->isBackendUser()) { return true; }

		// no access
		return false;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SEARCH
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function shouldBeSearchable(): bool {

		if($this->userHasAccessToBackend()) { return true; }

		if(!$this->public || $this->blocked) { return false; }

		if(static::$usePublished) {
			if($this->published_start > now()) { return false; }
			if($this->published_end && $this->published_end < now()) { return false; }
		}

		return true;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	DELETE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function delete() {

		// delete files for this model from store
		if(Storage::disk($this->storageDisk)->exists($this->storageFolder)) {
			Storage::disk($this->storageDisk)->deleteDirectory($this->storageFolder);
		}

		method_exists($this, 'deleteRelations') && $this->deleteRelations();

		return parent::delete();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
