<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\Backend\Shop;

	// Laravel
	use App\Http\Controllers\Backend\BackendController;
	use Illuminate\Http\JsonResponse;

	// App
	use App\Models\Shop\Product;
	use App\Http\Resources\Shop\ProductResource;
	use App\Http\Resources\Shop\ProductListResource;
	use App\Http\Requests\Backend\Shop\ProductSaveRequest;
	use App\Jobs\Shop\SyncStripeProduct;
	use App\Jobs\Shop\ProcessProductUpload;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ProductController extends BackendController {

	// model classes
	protected $modelClass = Product::class;
	protected $modelResourceClass = ProductResource::class;
	protected $modelListResourceClass = ProductListResource::class;

	// model relations
	protected $modelRelations = [];
	protected $modelListRelations = [];

	// return model list with pagination
	protected $paginator = false;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SAVE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function save(ProductSaveRequest $request): JsonResponse {

		$validated = $request->validated();

		$product  = $this->saveBaseModel($request);

		// save product properties
		foreach($this->langKeys as $lang) {
			$product['title'.$lang]					= $validated->{'title'.$lang};
			$product['teaser_description'.$lang]	= $validated->{'teaser_description'.$lang};	// InputRichText
			$product['teaser_image'.$lang]			= $validated->{'teaser_image'.$lang};
		}

		// save paid properties
		foreach($this->langKeys as $lang) {
			$product['content'.$lang] = $validated->{'content'.$lang};
		}

		// save stripe properties
		$product->stripe_id 	= $validated->stripe_id;
		$product->stripe_synced = false;

		$product->save();

		// sync product data from stripe with model
		if(!$validated->preview) {
			SyncStripeProduct::dispatch($product);
			ProcessProductUpload::dispatch($product);
		}

		return $this->getBackend($product->id);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
