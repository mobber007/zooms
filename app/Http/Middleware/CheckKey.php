<?php

namespace App\Http\Middleware;

use Closure;
use App\Request;

class CheckKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $log_request = new Request();
        $log_request->ip = $request->ip();
        $log_request->url = $request->url;
        $log_request->fingerprint = $request->fingerprint();
        $log_request->key = $request->key;
        $log_request->save();


        if ($request->key != env('OB_KEY')) {
            return response()->json('Please enter a valid key');
        }

        return $next($request);
    }



}
