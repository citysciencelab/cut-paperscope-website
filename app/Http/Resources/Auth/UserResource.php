<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Resources\Auth;

	// Laravel
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Auth;

	// App
	use App\Http\Resources\Base\BaseModelResource;
	use App\Http\Resources\Shop\ProductListResource;
	use App\Http\Resources\Shop\SubscriptionResource;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class UserResource extends BaseModelResource {


	public function getProps(Request $request): array {

		$accessProtectedProperties = Auth::check();
		$accessPrivateProperties = Auth::check() && ($this->id == Auth::user()->id || Auth::user()->isAdmin());

		return [

			// default properties
			'public' => 		true,

			// user properties
			'id' => 			$this->id,
			'name' => 			$this->name,
			'surname' => 		$this->surname,
			'fullname' =>		$this->fullname,
			'lang' => 			$this->lang,
			'username' => 		$this->username,

			// protected properties: only for logged in users
			$this->mergeWhen($accessProtectedProperties,[
				'image' => 			$this->image,
			]),

			// private properties: only for myself or admins
			$this->mergeWhen($accessPrivateProperties,[
				'email' => 			$this->email,
				'isVerified' => 	!$this->isGuest(),
				'gender' =>			$this->gender,
				'role' => 			$this->roles()->first()->name,
				'sso_driver' => 	$this->whenHas('sso_driver'),

				'street' => 		$this->street,
				'street_number' => 	$this->street_number,
				'zipcode' => 		$this->zipcode,
				'city' => 			$this->city,
				'country' => 		$this->country,
				'birthday' => 		$this->birthday,

				'products' => 		$this->getManyRelation(ProductListResource::class,'products'),
			]),

			// backend properties
			$this->addBackendProperties([
				'subscriptions' => 		SubscriptionResource::collection($this->subscriptions),
				'free_subscription' => 	$this->hasFreeSubscription(),
				'approved' => 			$this->approved,
				'blocked' => 			$this->blocked,
			]),
		];
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
