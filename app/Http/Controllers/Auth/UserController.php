<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\Auth;

	// Laravel
	use Illuminate\Http\JsonResponse;
	use Illuminate\Support\Str;
	use Illuminate\Support\Carbon;
	use Illuminate\Support\Facades\Hash;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Facades\Notification;
	use Intervention\Image\Laravel\Facades\Image;
	use Spatie\Permission\Models\Role;

	// App
	use App\Http\Requests\App\Base\ListWithFilterRequest;
	use App\Http\Requests\Auth\UserRequest;
	use App\Http\Requests\Auth\UserSaveRequest;
	use App\Http\Requests\Auth\UserDeleteRequest;
	use App\Http\Requests\Auth\PasswordUpdateRequest;
	use App\Http\Requests\Auth\UserImageRequest;
	use App\Http\Requests\Auth\UserImageDeleteRequest;
	use App\Http\Resources\Auth\UserResource;
	use App\Http\Resources\Auth\RoleResource;
	use App\Http\Controllers\Backend\BackendController;
	use App\Http\Controllers\App\Shop\StripeSubscriptionController;
	use App\Notifications\UserDeletedNotification;

	// App Jobs
	use App\Jobs\Newsletter\RemoveUserFromNewsletter;

	// App Models
	use App\Models\Auth\User;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class UserController extends BackendController {


	// model classes
	protected $modelClass = User::class;
	protected $modelResourceClass = UserResource::class;
	protected $modelListResourceClass = UserResource::class;

	// model relations
	protected $modelRelations = ['products'];
	protected $modelListRelations = [];

	// return model list with pagination
	protected $paginator = true;


	public function __construct() {

		parent::__construct();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	USER LIST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getList(ListWithFilterRequest $request) {

		$me = Auth::user();

		// list only available for admins
		if(!$me->isAdmin()) { return $this->responseError(403,'api.user_not_allowed');	}

		return $this->getBackendList($request, User::class,['roles']);
	}


	protected function applyFilterOrder(&$stmt, ListWithFilterRequest &$request, $usePublished) {

		$validated = $request->validated();

		// ordering by role relation
		if($validated->direction_property == 'role') {
			$stmt->leftJoin('model_has_roles','model_has_roles.model_id','=','users.id');
			$stmt->leftJoin('roles','roles.id','=','model_has_roles.role_id');
			$stmt->orderBy('roles.name',$validated->direction);
			$stmt->select('users.*');
		}
		// default ordering
		elseif($validated->direction && $validated->direction_property) {
			$stmt->orderBy($validated->direction_property, $validated->direction);
		}
		else {
			$stmt->orderBy('surname','desc');
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	USER GET
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function get(UserRequest $request): JsonResponse {

		$id = $request->validated('id');

		$me = Auth::user();
		if(!$me) { return $this->responseGet(null); }

		// access check for backend
		if($this->isBackendRequest($request) && !$me->isBackendUser()) {
			return $this->responseGet(null);
		}

		// other user only for admins
		if($me->isAdmin() && $id) {
			$user = User::with($this->modelRelations)->find($id);
			return $this->responseGet($user);
		}

		$me->load($this->modelRelations);
		return $this->responseGet($me);
	}


	public function getMe(): User {

		if(!Auth::user()) { return null; }
		return User::with($this->modelRelations)->find(Auth::id());
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	USER ROLES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getRolesList() {

		$me = Auth::user();

		// list only available for admins
		if(!$me->isAdmin()) {
			return $this->responseError(403,'api.user_not_allowed');
		}

		// get roles
		$roles = Role::all();
		if(!config('app.features.shop')) {
			$roles = $roles->filter(fn($role) => $role->name != 'member');
		}

		return $this->responseData(RoleResource::collection($roles));
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SAVE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function save(UserSaveRequest $request) {

		// request from backend
		if($this->isBackendRequest($request)) {
			return $this->saveBackendUser($request);
		}

		// request from app
		return $this->saveAppUser($request);
	}


	public function saveAppUser(UserSaveRequest $request) {

		$validated = $request->validated();

		// get my user model
		$me = Auth::user();
		if(!$me || $me->id !== $validated->id || !$me->can('edit users')) {
			return $this->responseError(403,'api.user_not_allowed');
		}

		// need update of default image?
		$oldFullname = $me->fullname;

		// Save user
		$me->name 			= $validated->name;
		$me->surname 		= $validated->surname;
		$me->fullname 		= $validated->name . " " . $validated->surname;
		$me->username 		= $validated->username;

		$me->street 		= $validated->street;
		$me->street_number 	= $validated->street_number;
		$me->zipcode 		= $validated->zipcode;
		$me->city 			= $validated->city;
		$me->country 		= $validated->country;
		$me->birthday 		= $this->getBirthday($validated->birthday);
		$me->gender 		= $validated->gender;

		if(!$me->isBackendUser()) {
			$me->approved = false;
			$me->blocked = false;
		}

		$me->save();

		// update default image
		if($me->fullname != $oldFullname && strpos($me->image, 'default-hr.jpg') !== false ) {
			$me->createDefaultImage();
		}

		$me->load($this->modelRelations);
		return $this->responseGet($me);
	}


	public function saveBackendUser(UserSaveRequest $request) {

		$validated = $request->validated();

		// get user
		$me 	= Auth::user();
		$user 	= User::with($this->modelRelations)->where('id',$validated->id)->first();

		// abort on invalid user
		if(!$user && !$me->can('create users')) {
			return $this->responseError(403,'api.user_not_allowed');
		}

		// only admins can create new users
		elseif(!$user && $me->can('create users') && $me->isAdmin()) {

			$user = User::forceCreate([
				'name' => 		$validated->name,
				'surname' => 	$validated->surname,
				'email' => 		$validated->email,
				'username' => 	$validated->username,
				'fullname' => 	$validated->name . " " . $validated->surname,
				'password' => 	Hash::make($validated->password),
			]);
			$user->markEmailAsVerified();
		}

		// abort if user is not allowed to edit other users
		if($user && !$me->isAdmin() && ( $me->id != $user->id || !$me->can('edit users') ) )  {
			return $this->responseError(403,'api.user_not_allowed');
		}

		// need to update default image?
		$oldFullname = $user->fullname;

		// Save user
		$user->name 			= $validated->name;
		$user->surname 			= $validated->surname;
		$user->fullname 		= $validated->name . " " . $validated->surname;

		$user->email 			= $validated->email;
		$user->username 		= $validated->username;

		$user->street 			= $validated->street;
		$user->street_number 	= $validated->street_number;
		$user->zipcode 			= $validated->zipcode;
		$user->city 			= $validated->city;
		$user->country 			= $validated->country;
		$user->birthday 		= $this->getBirthday($validated->birthday);
		$user->gender 			= $validated->gender;
		$user->sso_driver 		= $validated->sso_driver;

		$user->approved			= $validated->approved ?? false;
		$user->blocked			= $validated->blocked ?? false;

		// only admins can change model roles
		if($me->isAdmin() && isset($validated->role)) {
			$user->syncRoles([$validated->role]);

			// set email as verified if not guest user
			if(!$user->hasRole('guest') && !$user->hasVerifiedEmail()) { $user->markEmailAsVerified(); }
		}

		// update sso user
		if($user->sso_driver && !$user->email_verified_at) {
			$user->markEmailAsVerified();
		}
		if($user->sso_driver && $user->hasRole('guest')) {
			$user->syncRoles(['user']);
		}

		if($user->blocked) {
			$this->logoutFromAllDevices($user);
		}

		// update password if set
		if($validated->password) {
			$user->password = Hash::make($validated->password);
		}

		$user->save();


		// update default image
		if($user->fullname != $oldFullname && strpos($user->image, 'default-hr.jpg') !== false ) {
			$user->createDefaultImage();
		}

		// only admins can apply a free subscription
		if($me->isAdmin() && $validated->free_subscription) {
			(new StripeSubscriptionController)->applyFreeSubscription($user);
		}
		// remove existing free subscription
		elseif($me->isAdmin() && $user->hasFreeSubscription() && !$validated->free_subscription) {
			(new StripeSubscriptionController)->deleteFreeSubscription($user);
		}

		// Update model roles
		return $this->responseGet($user);
	}


	protected function getBirthday(string $birthday = null) {

		return empty($birthday) ? null : Carbon::createFromFormat('j.n.Y H:i', $birthday . ' 12:00');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	DELETE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function delete(UserDeleteRequest $request) {

		// request from backend
		if($this->isBackendRequest($request)) {
			return $this->deleteBackendUser($request);
		}

		// request from app
		return $this->deleteAppUser($request);
	}


	public function deleteAppUser(UserDeleteRequest $request) {

		$me = Auth::user();
		$id = $request->validated('id');

		// validate user
		if(!$me || $me->id !== $id) {
			return $this->responseError(403,'api.user_not_allowed');
		}

		// remove user from newsletter service
		if($me->newsletter) { RemoveUserFromNewsletter::dispatch($me->email); }

		// confirm delete with notification (on demand notification)
		Notification::route('mail', $me->email)->notify(new UserDeletedNotification($me->name, $me->lang));

		$me->delete();

		return $this->responseSuccess();
	}


	public function deleteBackendUser(UserDeleteRequest $request) {

		$me = Auth::user();
		$id = $request->validated('id');

		// only admins can delete users
		if(!$me->isAdmin()) {
			return $this->responseError(403,'api.user_not_allowed');
		}

		$user = User::find($id);

		// root user cannot be deleted
		if($user->email == config('auth.root.email')) {
			return $this->responseError(403,'api.user_not_allowed');
		}

		// remove user from Brevo newsletter
		if($user->newsletter) { RemoveUserFromNewsletter::dispatch($user->email); }

		// confirm delete with notification (on demand notification)
		Notification::route('mail', $user->email)->notify(new UserDeletedNotification($user->name, $me->lang));

		$user->delete();

		return $this->responseSuccess();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	PASSWORD
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function updatePassword(PasswordUpdateRequest $request) {

		$validated = $request->validated();

		// get user
		$me = Auth::user();

		// update not allowed for sso users
		if(!empty($me->sso_driver)) {
			return $this->responseError(403,'api.password_update_not_allowed');
		}

		// additional password validation of user input
		if(!Hash::check($validated->old, $me->password) || $me->id != $validated->id) {
			return $this->responseError(403,'api.password_update_not_allowed');
		}

		// update password
		$me->update(['password' => Hash::make($validated->password) ]);

		return $this->responseSuccess();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	USER IMAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function updateImage(UserImageRequest $request) {

		$me = Auth::user();
		$id = $request->validated('id');
		$user = User::find($id);

		// other user only for admins
		if($user->id != $me->id && !$me->isAdmin()) {
			return $this->responseError(403,'api.user_not_allowed');
		}

		// save new file to storage
		$storage = Storage::disk($user->storageDisk);
		$target = $user->storageFolder.'image.jpg';
		$img = Image::read($request->file('file'))->cover(400, 400)->toJpeg(90);
		$storage->put($target, $img);

		// update user model
		$user->update(['image' => $storage->url($target) . '?id='.time() ]);

		return $this->responseData($user->image);
	}


	public function deleteImage(UserImageDeleteRequest $request) {

		$me = Auth::user();
		$id = $request->validated('id');
		$user = User::find($id);

		// other user only for admins
		if($user->id != $me->id && !$me->isAdmin()) {
			return $this->responseError(403,'api.user_not_allowed');
		}

		// has custom image in store?
		$storage = Storage::disk($user->storageDisk);
		$target = $user->storageFolder.'image.jpg';
		if(!Str::contains($user->image,'default') && $storage->exists($target)) {

			// delete file
			$storage->delete($target);
		}

		// set user back to default image
		$defaultImage = $user->storageFolder.'default-hr.jpg';
		$user->update(['image' => $storage->url($defaultImage) . '?id='.time() ]);

		return $this->responseData($user->image);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

} // end class
