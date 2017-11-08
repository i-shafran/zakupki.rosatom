<?php namespace App\Http\Middleware;

use App\Http\Models\Users\vtiger_users;
use Closure;
use Illuminate\Support\Facades\Redirect;

class Authenticate {

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$user_key = false;
		if(isset($_COOKIE["user_key"])){
			$user_key = vtiger_users::query()->where("accesskey", $_COOKIE["user_key"])->first();
		}
		
		if(!$user_key){
			return redirect()->guest("/error/", 302);
		}
				
		return $next($request);
	}

}
