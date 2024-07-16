<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Customer;

class CustomerAuthenticator
{
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->header('UserId');

        if (!$userId) {
            return response()->json(["message" => "Missing authentication headers"], 400);
        }

        // Query the sessions table to find the session
        $session = Customer::where('UserId', $userId)->first();

        if (!$session) {
            return response()->json(["message" => "Please login to perform this action"], 401);
        }


        return $next($request);
    }
}
