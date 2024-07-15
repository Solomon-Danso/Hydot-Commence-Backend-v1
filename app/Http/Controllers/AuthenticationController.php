<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Mail;
use App\Mail\Authentication;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Security;


class AuthenticationController extends Controller
{



public function LogIn(Request $req)
    {
        $this->audit->RateLimit($req->ip());
        // Use your custom Authentication model to authenticate
        $user = AdminUser::where('Email', $req->Email)->first();
        $user->TokenId = $this->IdGenerator();
        $user->TokenExpire = Carbon::now()->addMinutes(10);
        $user->save();

        $Attempt = 0;


        if ($user && Hash::check($req->Password, $user->Password)) {

            $this-> Securities($user->UserId);

            if($user->IsBlocked==true){
                return response()->json(['message' => 'Sorry, your account has been blocked by the administrators'], 401);
            }

            if($user->IsSuspended==true){

                if( $user->SuspensionExpire<Carbon::now() ){
                    $user->IsSuspended = false;
                    $user->SuspensionExpire = null;
                    $user->save();

                    try {
                        Mail::to($user->Email)->send(new Authentication( $user->TokenId ));
                        return response()->json(['message' => $user->Email], 200);
                    } catch (\Exception $e) {

                        return response()->json(['message' => 'Email Request Failed'], 400);
                    }



                }


                return response()->json(['message' => 'Sorry, your account has been suspended by the administrators'], 401);
            }


            try {
                Mail::to($user->Email)->send(new Authentication( $user->TokenId ));
                return response()->json(['message' => $user->Email], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'Email Request Failed'], 400);
            }



        } else {






            $user->LoginLimit += 1;
            $user->save();

            $AttemptLeft = 3- $user->LoginLimit;

            if($user->LoginLimit>2){
                $user->IsBlocked=true;
                $user->save();
                return response()->json(['message' => 'Sorry, your account has been blocked by the administrators'], 401);

            }




            return response()->json(['message' => "Invalid credentials. You have ".$AttemptLeft." attempt(s) remaining before your account is locked."], 401);
        }
}







    public function ForgetPasswordStep1(Request $req)
    {
        $this->audit->RateLimit($req->ip());

        // Use your custom Authentication model to authenticate
        $user = AdminUser::where('Email', $req->Email)->first();

        if ($user) {

            if($user->IsBlocked==true){
                return response()->json(['message' => 'Your Account Has Been Blocked, Contact Site Administrator For Further Instruction '], 500);
            }
            else{

                $user->TokenId = $this->IdGenerator();
                $user->TokenExpire = Carbon::now()->addMinutes(10);

                $saver = $user->save();
                if ($saver) {
                    // Send email if the request is successful
                    try {
                        Mail::to($user->Email)->send(new Authentication( $user->Token));
                        return response()->json(['message' => "A verification token has been sent to ".$user->Email], 200);
                    } catch (\Exception $e) {

                        return response()->json(['message' => 'Email Request Failed'], 400);
                    }



                } else {
                    return response()->json(['message' => 'Could not save the Token'], 500);
                }


            }


        } else {

            return response()->json(['message' => 'Invalid credentials'], 401);
        }
    }






function ForgetPasswordStep2(Request $req){
    $this->audit->RateLimit($req->ip());
        $user = AdminUser::where('Email', $req->Email)->first();

        if ($user == null) {
            return response()->json(["message" => "User does not exist"], 400);
        }

        if ($user->TokenId === $req->token && Carbon::now() <= $user->TokenExpire) {
            // Invalidate the token and update the user attributes
            $user->TokenId = null;
            $user->TokenExpire = null;
            $user->LoginLimit = 0;
            $user->IsBlocked = false;
            $user->Password = bcrypt($req->Password);

            $this-> Securities($user->UserId);

            // Save the user
            $user->save();


            // Return the response
            return response()->json(["message" => "Password Updated Successfully"], 200);
        } else if (Carbon::now() > $user->TokenExpire) {
            return response()->json(["message" => "Your Token Has Expired"], 400);
        } else {
            return response()->json(["message" => "Invalid Token"], 400);
        }
    }











    function VerifyToken(Request $req){
        $this->audit->RateLimit($req->ip());
    $user = AdminUser::where('Email', $req->Email)->first();

    if ($user == null) {
        return response()->json(["message" => "User does not exist"], 400);
    }

    if ($user->TokenId === $req->token && Carbon::now() <= $user->TokenExpire) {
        // Invalidate the token and update the user attributes
        $user->TokenId = null;
        $user->TokenExpire = null;
        $user->LoginLimit = 0;
        $user->IsBlocked = false;
        $user->IsSuspended = false;

        $this-> Securities($user->UserId);

        // Save the user
        $user->save();

        $s = Security::where('UserId', $user->UserId)->orderBy('created_at', 'desc')->first();

        // Prepare the response data
        $responseData = [
            "FullName" => $user->Username,
            "UserId" => $user->UserId,
            "profilePic" => $user->Picture,
            "Role" => $user->Role,
            "SessionId"=>$s->SessionId
        ];

        // Return the response
        return response()->json(["message" => $responseData], 200);
    } else if (Carbon::now() > $user->TokenExpire) {
        return response()->json(["message" => "Your Token Has Expired"], 400);
    } else {
        return response()->json(["message" => "Invalid Token"], 400);
    }
}


function Securities($UserId) {

    $user = AdminUser::where('UserId', $UserId)->first();


    if ($user == null) {
        return response()->json(["message" => "User does not exist"], 400);
    }

    $checker = Security::where('UserId', $UserId)->first();
    if($checker){
        $checker->SessionId = $this->TokenGenerator();
        $checker->save();
    }
    else{
        $auditTrail = new Security();

        $auditTrail ->UserId = $user->UserId??" ";
        $auditTrail->SessionId = $this->TokenGenerator();

        $auditTrail->save();
    }



}


function detectDevice($userAgent) {
    $isMobile = false;
    $mobileKeywords = ['Android', 'webOS', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 'Windows Phone'];

    foreach ($mobileKeywords as $keyword) {
        if (stripos($userAgent, $keyword) !== false) {
            $isMobile = true;
            break;
        }
    }

    return $isMobile ? 'Mobile' : 'Desktop';
}

// Function to detect operating system from User-Agent string
function detectOperatingSystem($userAgent) {
    $os = 'Unknown';

    $osKeywords = ['Windows', 'Linux', 'Macintosh', 'iOS', 'Android'];

    foreach ($osKeywords as $keyword) {
        if (stripos($userAgent, $keyword) !== false) {
            $os = $keyword;
            break;
        }
    }

    return $os;
}



function TokenGenerator(): string {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$^&*()_+{}|<>-=[],.';
        $length = 30;
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
}



    function IdGenerator(): string {
        $randomID = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        return $randomID;
        }







}
