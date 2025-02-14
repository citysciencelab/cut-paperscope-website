<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit;

	// Laravel
	use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
	use Illuminate\Routing\Middleware\ThrottleRequests;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Facades\Config;
	use Illuminate\Support\Str;
	use Intervention\Image\Laravel\Facades\Image;
	use Illuminate\Contracts\Auth\Authenticatable;

	// Models
	use App\Models\Auth\User;

	// Traits
	use Tests\PHPUnit\Helper\Traits\PermissionAssertions;
	use Tests\PHPUnit\Helper\Traits\ArrayAssertions;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


abstract class TestCase extends BaseTestCase
{

	// Traits
	use CreatesApplication, PermissionAssertions, ArrayAssertions;

	protected $storage = null;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SETUP
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function setUp(): void {

		parent::setUp();

		$this->withoutMiddleware( ThrottleRequests::class );

		// add headers to all requests
		$this->withHeaders([
			'Accept-Language' => config('app.fallback_locale'),
		]);

		// bugfix: wrong baseUrl when comparing route list against request uri
		$components = parse_url(Config::get('app.url'));
		$this->withServerVariables([
			'SCRIPT_FILENAME' => Str::finish(base_path(),'/').'public/index.php',
			'SCRIPT_NAME' => Str::finish($components['path'],'/').'index.php',
			'REQUEST_URI' => '/hcu/paperscope-website/public/'
		]);

	}


	// bugfix: keep trailing slash on route "/" for correct baseUrl in PHPUnit
	protected function prepareUrlForRequest($uri) {

		$uri = ltrim($uri, '/');
		return $uri == '' ? url($uri).'/' : trim(url($uri), '/');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	DATABASE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function seed($class = 'Database\\Seeders\\DatabaseSeeder') {

		// prevent writing data to storage
		$this->storage = Storage::fake(config('filesystems.default'));

		// seed database
		return parent::seed($class);
	}


	public function seedWithStorage($class = 'Database\\Seeders\\DatabaseSeeder') {

		// seed database
		return parent::seed($class);
	}


	protected function getPageProps() {

		return config('app.features.multi_lang') ?
			['meta_title_de', 'meta_description_de', 'social_description_de', 'social_image_de']
		: 	['meta_title', 'meta_description', 'social_description', 'social_image'];
	}


	protected function getBaseProps() 				{ return ['id', 'name', 'public', 'order', 'slug']; }
	protected function getBasePropsNoSlug() 		{ return ['id', 'name', 'public', 'order']; }
	protected function getPublishedProps() 			{ return ['published_start', 'published_end']; }
	protected function getParentModelProps() 		{ return ['parent_id', 'parent_type']; }
	protected function getStripeProps() 			{ return ['stripe_id', 'stripe_synced', 'stripe_name', 'stripe_description', 'stripe_price_id', 'stripe_price_value', 'stripe_price_amount']; }



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FEATURES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function hasFeatureBackend()		{ return config('app.features.backend'); }
	protected function hasFeatureAppAccounts()	{ return config('app.features.app_accounts'); }
	protected function hasFeatureShop()			{ return config('app.features.shop'); }
	protected function hasFeatureMultiLang()	{ return config('app.features.multi_lang'); }


	protected function skipIfNoBackend() {

		if($this->hasFeatureBackend()) { return; }
		$this->markTestSkipped('Feature "Backend" is disabled.');
	}

	protected function skipIfNoAppAccounts() {

		if($this->hasFeatureAppAccounts()) { return; }
		$this->markTestSkipped('Feature "App Accounts" is disabled.');
	}

	protected function skipIfNoShop() {

		if($this->hasFeatureShop()) { return; }
		$this->markTestSkipped('Feature "Shop" is disabled.');
	}


	protected function getBackendHeaders() {

		return [
			'X-Context' => 'backend',
			'referer' => 'backend/',
			'Accept' => 'application/json',
		];
	}


	protected function translateProp(string $prop, string $lang = null) {

		if($lang === null) { $lang = config('app.fallback_locale'); }

		return config('app.features.multi_lang') ? $prop.'_'.$lang : $prop;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	AUTH
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createGuest(array $attributes = []): User {

		$user = User::factory()->createOne([
			'email_verified_at' => null,
			...$attributes
		]);

		return $user;
	}


	protected function createUser(array $attributes = []): User {

		$user = User::factory()->createOne($attributes);
		$user->changeRole('guest', 'user');

		return $user;
	}


	protected function createMember(array $attributes = []): User {

		$member = User::factory()->createOne($attributes);
		$member->changeRole('guest', 'member');

		return $member;
	}


	protected function createEditor(array $attributes = []): User {

		$editor = User::factory()->createOne($attributes);
		$editor->changeRole('guest', 'editor');

		return $editor;
	}


	protected function createAdmin(array $attributes = []): User {

		$admin = User::factory()->createOne($attributes);
		$admin->changeRole('guest', 'admin');

		return $admin;
	}


	protected function loginAsUser(array $attributes = []): Authenticatable {

		/** @var Authenticatable $user **/
		$user = $this->createUser($attributes);
		$this->actingAs($user);

		return $user;
	}


	protected function loginAsMember(array $attributes = []): Authenticatable {

		/** @var Authenticatable $member **/
		$member = $this->createMember($attributes);
		$this->actingAs($member);

		return $member;
	}


	protected function loginAsEditor(array $attributes = []): Authenticatable {

		/** @var Authenticatable $editor **/
		$editor = $this->createEditor($attributes);
		$this->actingAs($editor);

		return $editor;
	}


	protected function loginAsAdmin(array $attributes = []): Authenticatable {

		/** @var Authenticatable $admin **/
		$admin = $this->createAdmin($attributes);
		$this->actingAs($admin);

		return $admin;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	API HELPERS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function getWithParams(string $uri, array $params = [], array $headers = []) {

		// add params to uri
		if(count($params) > 0) { $uri .= '?'.http_build_query($params); }

		// get response
		$response = $this->get($uri, $headers);
		$response->assertJson(['status'=>'success']);

		return $response;
	}


	protected function getData(string $uri, array $params = [], array $headers = []) {

		// get response
		$response = $this->getWithParams($uri, $params, $headers);

		$data = $response->json('data');
		$this->assertNotEmpty($data);

		return $data;
	}


	protected function getError(string $uri, string $errorMessage = 'api.not_found') {

		// get response
		$response = $this->get($uri);
		$response->assertJson(['status'=>'error', 'message'=>trans($errorMessage)]);

		return $response;
	}


	protected function postData(string $uri, array $data = [], array $headers = []) {

		// get response
		$response = $this->post($uri, $data, $headers);
		$response->assertJson(['status'=>'success']);

		$data = $response->json('data');
		$this->assertNotEmpty($data);

		return $data;
	}


	protected function postError(string $uri, array $data = [], array $headers = [], string $errorMessage = 'api.not_found') {

		// get response
		$response = $this->post($uri, $data, $headers);
		$response->assertJson(['status'=>'error', 'message'=>trans($errorMessage)]);

		return $response;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HELPERS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function storageUrlToPath($storageUrl) {

		$storage = $this->storage->url('');

		$storageUrl = str_replace($storage, '', $storageUrl);
		$storageUrl = explode('?', $storageUrl)[0];

		return $storageUrl;
	}


	protected function createImageFile(string $filename = 'test.jpg', string $imgType = 'jpg') {

		$this->storage = $this->storage ?? Storage::disk(config('filesystems.default'));

		// create image file in storage
		$image = Image::create(100, 100)->fill('#ff0000');
		$this->storage->put('pages/_upload/'.$filename, $image->encodeByExtension($imgType));

		return $this->storage->url('pages/_upload/'.$filename);
	}


	public function createFile(string $filename = 'test.txt', string $content = 'test content') {

		$this->storage = $this->storage ?? Storage::disk(config('filesystems.default'));

		// create file in storage
		$this->storage->put('pages/_upload/'.$filename, $content);

		return $this->storage->url('pages/_upload/'.$filename);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
