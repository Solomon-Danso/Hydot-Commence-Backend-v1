<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\AuditTrialController;
use Illuminate\Support\Facades\Config;
use App\Mail\Registration;
use Carbon\Carbon;
use App\Models\UserFunctions;

class AdminUserController extends Controller
{

    protected $audit;


    public function __construct(AuditTrialController $auditTrialController)
    {
        $this->audit = $auditTrialController;

    }



public function SetUpCreateAdmin(Request $req)
    {
        $this->audit->RateLimit($req->ip());
        if (Config::get('app.setup_completed')) {
            return response()->json(["message" => "Admin setup has already been completed"], 400);
        }

        if (AdminUser::count() > 0) {
            return response()->json([
                "message" => "Warning: An Admin account already exists. This action is not permitted and could have serious consequences. Do not attempt this again."
            ], 400);
        }


        $s = new AdminUser();

        if ($req->hasFile("Picture")) {
            $s->Picture = $req->file("Picture")->store("", "public");
        }

        $s->UserId = $this->IdGenerator();

        $fields = ["Username", 'Phone', 'Email'];
        foreach ($fields as $field) {
            if ($req->filled($field)) {
                $s->$field = $req->$field;
            }
        }

        $rawPassword = $this->IdGenerator();
        $s->Password = bcrypt($rawPassword);
        $s->Role = AdminUser::count() < 1 ? "SuperAdmin" : "StaffMember";

        if ($s->save()) {

            $role = new UserFunctions();
            $role->UserId = $s->UserId;
            $role->Function = "SuperAdmin";
            $role->save();


            try {
                Mail::to($s->Email)->send(new Registration($s, $rawPassword));
                 // Set the flag to true after successful setup
                 Config::set('app.setup_completed', true);
                 // Update .env file (optional but recommended)
                 $this->updateEnv(['ADMIN_SETUP_COMPLETED' => 'true']);

                return response()->json(["message" => "Success"], 200);
            } catch (\Exception $e) {
                return response()->json(["message" => "Email Failed"], 400);
            }
        } else {
            return response()->json(["message" => "Could not add Admin"], 400);
        }
}

public function CreateAdmin(Request $req)
    {
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Create_Admin");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
        $checker = AdminUser::where("Email",$req->Email)->first();
        if($checker){
            return response()->json(["message" => "Email already exist"], 400);
        }

        $s = new AdminUser();

        if ($req->hasFile("Picture")) {
            $s->Picture = $req->file("Picture")->store("", "public");
        }

        $s->UserId = $this->IdGenerator();

        $fields = ["Username", 'Phone', 'Email'];
        foreach ($fields as $field) {
            if ($req->filled($field)) {
                $s->$field = $req->$field;
            }
        }

        $rawPassword = $this->IdGenerator();
        $s->Password = bcrypt($rawPassword);
        $s->Role = "StaffMember";

        $saver = $s->save();
        if($saver){



            $message = $s->Username."  was added as a staff member";
            $message2 = $s->Username."  is added as a staff member";
            $this->audit->Auditor($req->AdminId, $message);

            try {
                Mail::to($s->Email)->send(new Registration($s, $rawPassword));
                return response()->json(["message" => $message2], 200);
            } catch (\Exception $e) {

                return response()->json(["message" => "Email Failed"], 400);
            }


        }else{
            return response()->json(["message" => "Could not add Staff member"], 400);
        }



}


protected function updateEnv($data = array())
    {
        if (count($data) > 0) {
            // Read .env-file
            $env = file_get_contents(base_path() . '/.env');
            // Split string on every " " and write into array
            $env = explode("\n", $env);
            // Loop through given data
            foreach ((array)$data as $key => $value) {
                // Loop through .env-data
                foreach ($env as $env_key => $env_value) {
                    // Turn the value into an array and stop after the first split
                    $entry = explode("=", $env_value, 2);
                    // Check, if new key fits the actual .env-key
                    if ($entry[0] == $key) {
                        // If yes, overwrite it
                        $env[$env_key] = $key . "=" . $value;
                    } else {
                        // If not, keep it
                        $env[$env_key] = $env_value;
                    }
                }
            }
            // Turn the array back to a string
            $env = implode("\n", $env);
            // And overwrite the .env with the new data
            file_put_contents(base_path() . '/.env', $env);
        }
}





function MyProfileUpdateAdmin(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Update_Personal_Profile");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
    }
    $s = AdminUser::where("UserId", $req->AdminId)->first();

    if(!$s){
        return response()->json(["message"=>"Admin not found"],400);
    }

    if($req->hasFile("Picture")){
        $s->Picture = $req->file("Picture")->store("","public");
    }





    if($req->filled("Username")){
        $s->Username = $req->Username;
    }


    if($req->filled("Phone")){
        $s->Phone = $req->Phone;
    }

    if($req->filled("Email")){
        $s->Email = $req->Email;
    }

    if($req->filled("Password")){
        $s->Password = bcrypt($req->Password);
    }







    $saver = $s->save();
    if($saver){

        $message = $s->Username."  details was updated";
        $this->audit->Auditor($req->AdminId, $message);


        return response()->json(["message" => "User Information Updated "], 200);

    }else{
        return response()->json(["message" => "Could not update Admin"], 400);
    }




}



function UpdateAdmin(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Update_Admin");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
    }
    $s = AdminUser::where("UserId", $req->AdminId)->first();

    if(!$s){
        return response()->json(["message"=>"Admin not found"],400);
    }

    if($req->hasFile("Picture")){
        $s->Picture = $req->file("Picture")->store("","public");
    }





    if($req->filled("Username")){
        $s->Username = $req->Username;
    }


    if($req->filled("Phone")){
        $s->Phone = $req->Phone;
    }

    if($req->filled("Email")){
        $s->Email = $req->Email;
    }

    if($req->filled("Password")){
        $s->Password = bcrypt($req->Password);
    }







    $saver = $s->save();
    if($saver){

        $message = $s->Username."  details was updated";
        $this->audit->Auditor($req->AdminId, $message);


        return response()->json(["message" => "User Information Updated "], 200);

    }else{
        return response()->json(["message" => "Could not update Admin"], 400);
    }




}


function ViewSingleAdmin(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Single_Admin");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
    }
   $s = AdminUser::where("UserId", $req->UserId)->first();

    if($s==null){
        return response()->json(["message"=>"Admin not found"],400);
    }

    $message = $s->Username."  details was viewed";
    $this->audit->Auditor($req->AdminId, $message);


   return $s;
}



function BlockAdmin(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Block_Admin");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
 }


   $s = AdminUser::where("UserId", $req->UserId)->where('Role', '!=', 'SuperAdmin')->first();

    if($s==null){
        return response()->json(["message"=>"Admin not found"],400);
    }

    $s->IsBlocked=true;
    $s->LoginLimit=3;



    $saver = $s->save();
    if($saver){
        $message = $s->Username."  has been blocked";
        $this->audit->Auditor($req->AdminId, $message);
        return response()->json(["message"=>$message],200);
    }
    else{
        return response()->json(["message"=>"Failed to block ".$s->Username],400);
    }

}

function UnBlockAdmin(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_UnBlock_Admin");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
 }

   $s = AdminUser::where("UserId", $req->UserId)->first();

    if($s==null){
        return response()->json(["message"=>"Admin not found"],400);
    }

    $s->IsBlocked=false;
    $s->LoginLimit=0;



    $saver = $s->save();
    if($saver){
        $message = $s->Username."  has been Unblocked";
        $this->audit->Auditor($req->AdminId, $message);
        return response()->json(["message"=>$message],200);
    }
    else{
        return response()->json(["message"=>"Failed to Unblock ".$s->Username],400);
    }

   }



function SuspendAdmin(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Suspend_Admin");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
 }

   $s = AdminUser::where("UserId", $req->UserId)->where('Role', '!=', 'SuperAdmin')->first();

    if($s==null){
        return response()->json(["message"=>"Admin not found"],400);
    }

    $s->IsSuspended=true;
    $s->SuspensionExpire=$req->SuspensionExpire;



    $saver = $s->save();
    if($saver){
        $message = $s->Username."  has been suspended";
        $this->audit->Auditor($req->AdminId, $message);
        return response()->json(["message"=>$message],200);
    }
    else{
        return response()->json(["message"=>"Failed to suspend ".$s->Username],400);
    }

}

function UnSuspendAdmin(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_UnSuspend_Admin");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
 }

   $s = AdminUser::where("UserId", $req->UserId)->first();

    if($s==null){
        return response()->json(["message"=>"Admin not found"],400);
    }

    $s->IsSuspended=false;
    $s->SuspensionExpire=Carbon::now();



    $saver = $s->save();
    if($saver){
        $message = $s->Username."  has been unsuspended";
        $this->audit->Auditor($req->AdminId, $message);
        return response()->json(["message"=>$message],200);
    }
    else{
        return response()->json(["message"=>"Failed to unsuspend ".$s->Username],400);
    }

}






function UnLocker(Request $req){
    $this->audit->RateLimit($req->ip());
    $s = AdminUser::where("Email", $req->Email)->where('Role', 'SuperAdmin')->first();

    if($s==null){
        return response()->json(["message"=>"Admin not found"],400);
    }

    $s->IsBlocked=false;
    $s->LoginLimit=0;
    $s->TokenId = null;
    $s->TokenExpire = null;
    $s->IsSuspended=false;
    $s->SuspensionExpire=Carbon::now();





    $saver = $s->save();
    if($saver){
        $message = $s->Username."  has been Unlocked";
        $this->audit->Auditor($req->AdminId, $message);
        return response()->json(["message"=>$message],200);
    }
    else{
        return response()->json(["message"=>"Failed to Unblock ".$s->Username],400);
    }

}




function ViewAllAdmin(Request $req) {
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_All_Admin");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
 }

   $s = AdminUser::where('Role', '!=', 'SuperAdmin')->get();

    if ($s->isEmpty()) {
        return response()->json(['message' => 'Admin not found'], 400);
    }


    $this->audit->Auditor($req->AdminId, "Viewed All Administrators");


    return response()->json($s);
}





function DeleteAdmin(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Delete_Admin");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
 }

   $s = AdminUser::where("UserId", $req->UserId)->first();

    if($s==null){
        return response()->json(["message"=>"Admin not found"],400);
    }

    $saver = $s->delete();
    if($saver){

        $message = $s->Username."  details was deleted";
        $this->audit->Auditor($req->AdminId, $message);

        return response()->json(["message"=>"Deleted Successfully"],200);
    }
    else{
        return response()->json(["message"=>"Deletion Failed"],400);
    }


}







function IdGenerator(): string {
    $randomID = str_pad(mt_rand(1, 99999999), 5, '0', STR_PAD_LEFT);
    return $randomID;
}


}
