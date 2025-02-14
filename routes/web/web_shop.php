<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// Laravel
	use Illuminate\Support\Facades\Route;

	// App
	use App\Http\Controllers\App\Shop\StripeProductController;
	use App\Http\Controllers\App\Shop\StripeSubscriptionController;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SHOP ROUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	$products = ['product'];
	$subscriptions = array_keys(config('cashier.subscription'));

	//products
	Route::get('/stripe/checkout/{type}/{id}',[StripeProductController::class,'checkoutProduct'])->whereIn('type', $products)->name('stripe.checkout');
	Route::get('/stripe/checkout/success/{type}',[StripeProductController::class,'checkoutProductSuccess'])->whereIn('type', $products)->name('stripe.checkout.success');

	// subscription
	Route::get('/stripe/checkout/subscription/{type}',[StripeSubscriptionController::class,'checkoutSubscription'])->whereIn('type', $subscriptions)->name('stripe.checkout.subscription');
	Route::get('/stripe/checkout/success/subscription/{type}',[StripeSubscriptionController::class,'checkoutSubscriptionSuccess'])->whereIn('type', $subscriptions)->name('stripe.checkout.success.subscription');

