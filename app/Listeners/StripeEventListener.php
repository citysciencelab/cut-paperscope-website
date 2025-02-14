<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Listeners;

	// Laravel
	use Laravel\Cashier\Events\WebhookReceived;
	use Illuminate\Support\Facades\Log;

	// App
	use App\Http\Controllers\App\Shop\StripeProductController;
	use App\Http\Controllers\App\Shop\StripeSubscriptionController;
	use App\Http\Controllers\App\Shop\StripeCustomerController;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class StripeEventListener {


	public function __construct() {

	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HANDLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function handle(WebhookReceived $event) {

		switch($event->payload['type']) {

			case 'payment_intent.succeeded': 					return (new StripeProductController())->onPaymentSuccess($event);
			case 'checkout.session.async_payment_succeeded': 	return (new StripeProductController())->onAsyncPaymentSuccess($event);
			case 'checkout.session.async_payment_failed': 		return (new StripeProductController())->onAsyncPaymentFailed($event);
			case 'setup_intent.succeeded': 						return (new StripeProductController())->onSetupIntentSucceeded($event);

			// charge
			case 'charge.refunded': 							return (new StripeProductController())->onChargeRefunded($event);

			// product
			case 'product.updated': 							return (new StripeProductController())->onProductUpdated($event);
			case 'product.deleted': 							return (new StripeProductController())->onProductDeleted($event);

			// customer
			case 'customer.updated': 							return (new StripeCustomerController())->onCustomerUpdated($event);
			case 'customer.deleted': 							return (new StripeCustomerController())->onCustomerDeleted($event);
			case 'customer.subscription.updated': 				return (new StripeSubscriptionController())->onSubscriptionUpdated($event);
			case 'customer.subscription.deleted': 				return (new StripeSubscriptionController())->onSubscriptionDeleted($event);
			default: 											Log::info('Unhandeld stripe webhook: ' . $event->payload['type']);
		}
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
