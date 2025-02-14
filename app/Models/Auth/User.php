<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Models\Auth;

	// Laravel
	use Illuminate\Contracts\Auth\MustVerifyEmail;
	use Illuminate\Foundation\Auth\User as Authenticatable;
	use Intervention\Image\Laravel\Facades\Image;
	use Illuminate\Support\Facades\Storage;

	// Laravel Traits
	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Concerns\HasUuids;
	use Illuminate\Notifications\Notifiable;
	use Laravel\Cashier\Billable;
	use Laravel\Scout\Searchable;
	use Laravel\Sanctum\HasApiTokens;

	// App Traits
	use Spatie\Permission\Traits\HasRoles;

	// App Events
	use App\Events\UserCreated;
	use App\Events\UserUpdated;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class User extends Authenticatable implements MustVerifyEmail
{

	// Traits
	use HasUuids, HasFactory, Notifiable, HasRoles, Searchable, Billable, HasApiTokens;

	// The attributes that are mass assignable.
	protected $fillable = [
		'name', 'surname', 'fullname', 'lang',
		'email', 'username',
		'street', 'street_number', 'zipcode', 'city', 'country', 'birthday',
		'gender', 'image', 'password', 'newsletter',
		'approved', 'blocked',
	];

	// The attributes that should be hidden for arrays.
	protected $hidden = [
		'password', 'remember_token', 'created_at', 'updated_at', 'email_verified_at', 'deleted_at'
	];

	// Map model events
	protected $dispatchesEvents = [
		'created' => UserCreated::class,
		'updated' => UserUpdated::class,
	];

	// cast properties to correct type
	protected $casts = [
		'birthday' => 'date',
		'newsletter' => 'boolean',
		'email_verified_at' => 'datetime',
		'approved' => 'boolean',
		'blocked' => 'boolean',
		'trial_used' => 'boolean',
	];

	// property features
	public static $useSlug = false;
	public static $usePublished = false;
	public static $useSearch = true;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  INIT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public static function boot() {

		parent::boot();

		self::created(function ($model) {
			$model->assignRole('guest');
			$model->createDefaultImage();
			$model->save();
		});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SEARCH
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// Define the searchable fields
	public function toSearchableArray() {

		return [
			'name' => 		$this->name,
			'surname' => 	$this->surname,
			'email' => 		$this->email,
			'username' => 	$this->username,
		];
	}


	public function shouldBeSearchable() {

		return self::$useSearch && $this->email_verified_at ? true : false;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  GETTER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// Gender
	public function isMale()    			{ return $this->gender === 'm' ? true : false; }
	public function isFemale()  			{ return $this->gender === 'f' ? true : false; }

	// Roles App
	public function isGuest() 				{ return $this->hasRole('guest'); }
	public function isVerified() 			{ return !$this->isGuest(); }
	public function isUser() 				{ return $this->hasRole('user'); }
	public function isMember() 				{ return $this->hasRole('member'); }

	// Roles Backend
	public function isEditor() 				{ return $this->hasRole('editor'); }
	public function isAdmin() 				{ return $this->hasRole('admin'); }
	public function isBackendUser() 		{ return $this->hasAnyRole(['editor','admin']) ; }



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	STORAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getStorageDiskAttribute() {

		return config('filesystems.default');
	}


	public function getStorageFolderAttribute() {

		$monthFolder 	= $this->created_at->format('Y-m');

		return 'users/' . $monthFolder . '/' . $this->id . '/';
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  PROFILE IMAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function createDefaultImage() {

		$storage = Storage::disk($this->storageDisk);

		// create highres image with initials
		$initials = strtoupper($this->name[0] . $this->surname[0]);
		$highres = Image::create(400, 400)->fill('#bebebe');
		$highres->text($initials, 200, 200,function($font) { $this->getDefaultImageFont($font); });

		// save highres image
		$file = $this->storageFolder.'default-hr.jpg';
		$img = $highres->toJpeg(90);
		$storage->put($file, $img);

		// create midres image
		$file = $this->storageFolder.'default-mr.jpg';
		$img = Image::read($highres)->resize(200,200)->toJpeg(90);
		$storage->put($file, $img);

		// create lowres image
		$file = $this->storageFolder.'default-lr.jpg';
		$img = Image::read($highres)->resize(50,50)->toJpeg(90);
		$storage->put($file, $img);

		// update property
		$this->image = $storage->url($this->storageFolder.'default-hr.jpg') . '?id='.time();
	}


	protected function getDefaultImageFont(&$font) {

		$font->file(public_path('fonts/opensans/OpenSans-SemiBold.woff'));
		$font->size(150);
		$font->color('#ffffff');
		$font->align('center');
		$font->valign('center');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function products() {

		// get all products
		return $this->belongsToMany('App\Models\Shop\Product')->orderBy('updated_at')->withPivot(['receipt', 'status']);
	}


	public function deleteRelations() {

		$this->products()->detach();
		$this->tokens()->delete();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	AUTH
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// overwrite verification email for queue
	public function sendEmailVerificationNotification() {

		$this->notify(new \App\Notifications\VerifyRegisterNotification($this->lang));
	}


	// overwrite php passwort reset function for jwt based password reset
	public function sendPasswordResetNotification($token) {

		$this->notify(new \App\Notifications\PasswordResetNotification($token,$this->lang));
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	ROLES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function changeRole(string $oldRole, string $newRole) {

		$newRoleNames = [];

		// get all existing roles without old and new role
		$this->roles->each(function($role) use (&$newRoleNames, $oldRole, $newRole) {
			!in_array($role->name, [$oldRole, $newRole]) ? $newRoleNames[] = $role->name : null;
		});

		// add new role
		$newRoleNames[] = $newRole;
		$this->syncRoles($newRoleNames);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	STRIPE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// getter
	public function stripeName() 			{ return $this->fullname; }
	public function hasSubcription() 		{ return $this->subscribed('default'); }
	public function hasFreeSubscription() 	{ return $this->subscribed('free'); }


	public function stripeAddress() {

		// skip address if not available
		if (!isset($this->street)) { return null; }

		return [
			'line1' => 			$this->street . ' ' . $this->street_number,
			'postal_code' => 	$this->zipcode,
			'city' => 			$this->city,
			'country' => 		$this->country,			// stripe needs iso code instead of country name
		];
	}


	public function deleteStripeSubscriptions() {

		// iterate all subscriptions
		foreach ($this->subscriptions as $subscription) {

			// adapt on free subscription
			if($subscription->type == 'free') {
				$subscription->delete();
				continue;
			}

			// cancel stripe subscription immediately
			$subscription->cancelNow();

			// remove subscription items
			$subscription->items()->delete();
		}

		$this->subscriptions()->delete();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	DELETE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function delete() {

		Storage::disk($this->storageDisk)->deleteDirectory($this->storageFolder);

		$this->deleteRelations();
		$this->deleteStripeSubscriptions();

		return parent::delete();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
