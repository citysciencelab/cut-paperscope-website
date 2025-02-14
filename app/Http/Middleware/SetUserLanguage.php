<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Middleware;

	// Laravel
	use Closure;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Config;
	use Illuminate\Support\Facades\Auth;
	use Symfony\Component\HttpFoundation\Response;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class SetUserLanguage {


	public function handle(Request $request, Closure $next): Response {

		// get locale from http header
		$locale = $request->headers->get('accept-language');
		if($locale) { $locale = explode('-',$locale)[0]; }  // 'de-de' to 'de'

		$availableLocales = Config::get('app.available_locales');
		$fallbackLocale = Config::get('app.fallback_locale');

		// force locale if part of url
		$urlLocale = $request->segment(1);
		if(in_array($urlLocale, $availableLocales)) { $locale = $urlLocale; }

		// is language available?
		if(!in_array($locale, $availableLocales)) { $locale = $fallbackLocale; }

		// save language for session
		$this->saveLanguageForUser($request, $locale);
		\Session::put('locale',$locale);
		app()->setLocale($locale);

		return $next($request);
	}


	protected function saveLanguageForUser(Request $request, string $locale): void {

		$route = $request->route()->getName();
		if($route == 'index' || $route == 'index.vue' || $route == 'backend.vue' ) { return; }

		$user = Auth::user();
		if(!$user) { return; }

		if(\Session::get('locale') == $locale) { return; }

		if($user->lang != $locale) {
			$user->lang = $locale;
			$user->save();
		}
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
