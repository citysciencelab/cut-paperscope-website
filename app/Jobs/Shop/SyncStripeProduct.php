<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Jobs\Shop;

	// Laravel
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Foundation\Bus\Dispatchable;
	use Illuminate\Queue\InteractsWithQueue;
	use Illuminate\Queue\SerializesModels;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\Cache;
	use Throwable;

	// Stripe
	use Stripe\StripeClient;

	// App
	use App\Models\Shop\Product;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class SyncStripeProduct implements ShouldQueue {

	// Traits
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	// target model
	public $product;

	// stripe
	private $stripe;


	public function __construct(Product $product) {

		$this->product = $product;
	}


	public function failed(Throwable $exception): void {

		Log::critical("Job failed: SyncStripeProduct. Model name: " . ($this->product ? $this->product->name : 'Undefined'));
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HANDLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function handle() {

		$this->initStripe();
		$this->getStripeProduct();

		Cache::flush();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	STRIPE API
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	private function initStripe() {

		$this->stripe = new StripeClient(config('cashier.secret'));
	}


	private function getStripeProduct() {

		$stripeProduct = $this->stripe->products->retrieve($this->product->stripe_id);

		// update product
		if($stripeProduct) {
			$this->product->stripe_name = $stripeProduct->name;
			$this->product->stripe_description = $stripeProduct->description;
		}
		else {
			Log::critical("Job failed: failed to load stripe product in SyncStripeProduct. Model name: " . $this->product->name);
			return;
		}

		// update price
		$stripePrice = $this->stripe->prices->retrieve($stripeProduct->default_price);
		if($stripePrice) {
			$this->product->stripe_price_id = $stripePrice->id;
			$this->product->stripe_price_amount = $stripePrice->unit_amount;
			$this->product->stripe_price_value = number_format($stripePrice->unit_amount/100, 2, ',', '.' ) . ' €';
		}
		else {
			Log::critical("Job failed: failed to load stripe price in SyncStripeProduct. Model name: " . $this->product->name);
			return;
		}

		// save model
		$this->product->stripe_synced = true;
		$this->product->save();
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
