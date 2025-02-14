<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Middleware;

	// Laravel
	use Closure;
	use Illuminate\Routing\Middleware\ValidateSignature as Middleware;
	use Illuminate\Support\Carbon;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Auth;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ValidateSignature extends Middleware {

	// The names of the query string parameters that should be ignored.
    protected $except = [
        // 'fbclid',
        // 'utm_campaign',
        // 'utm_content',
        // 'utm_medium',
        // 'utm_source',
        // 'utm_term',
    ];


    public function handle($request, Closure $next, ...$args) {

		[$relative, $ignore] = $this->parseArguments($args);

		// redirect if signature is expired
		if(!$this->signatureHasNotExpired($request)) {
			Auth::logout();
			return redirect(config('fortify.login') . '?error=signature-expired');
		}

		// signature is valid
        else if ($request->hasValidSignatureWhileIgnoring($ignore, $relative !== 'relative')) {
			return $next($request);
		}

		// signature is invalid
		else {
			Auth::logout();
			return redirect(config('fortify.login') . '?error=signature-invalid');
		}
	}


	public function signatureHasNotExpired(Request $request): bool {

		$expires = $request->query('expires');
		return ! ($expires && Carbon::now()->getTimestamp() > $expires);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
