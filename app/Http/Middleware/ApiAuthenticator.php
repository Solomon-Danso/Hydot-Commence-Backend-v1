<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Security;

class ApiAuthenticator
{
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->header('UserId');
        $sessionId = $request->header('SessionId');

        if (!$userId || !$sessionId) {
            return response()->json(["message" => "Missing authentication headers"], 400);
        }

        // Query the sessions table to find the session
        $session =Security::where('SessionId', $sessionId)
            ->where('userId', $userId)
            ->first();

        if (!$session) {
            return response()->json(["message" => "You are not authorised to perform this action"], 401);
        }


        return $next($request);
    }
}
