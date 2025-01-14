<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['success' => false, 'data' => null, 'error' => 'Token not provided'], 403);
        }
        // Check if the token is valid
        $user = Auth::guard('sanctum')->user();
        
        if (!$user) {
            return response()->json([ 'success' => false,  'data' => null, 'error' => 'Token invalid or expired', 'token' => $token], 403);
        }

        return $next($request);
    }
}
