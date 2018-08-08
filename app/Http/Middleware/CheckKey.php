<?php

namespace App\Http\Middleware;

use Closure;

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
        if ($request->key != env('OB_KEY')) {
            return response()->json('Please enter a valid key');
        }

        return $next($request);
    }



}
