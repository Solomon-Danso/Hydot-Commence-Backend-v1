<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Customer;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\AuditTrialController;
use Illuminate\Support\Facades\Config;
use App\Mail\Registration;
use Carbon\Carbon;

class CustomerController extends Controller

{


public function CreateCustomer(Request $req)
    {


        $s = new Customer();


        $s->UserId = $this->IdGenerator();

        $fields = ["Username", 'Phone', 'Email'];
        foreach ($fields as $field) {
            if ($req->filled($field)) {
                $s->$field = $req->$field;
            }
        }

        if ($req->filled("Password")) {
            $s->Password = bcrypt($req->Password);
        }

        $saver = $s->save();
        if($saver){

            return response()->json(["message" => "Successful"], 200);

        }else{
            return response()->json(["message" => "Failed"], 400);
        }



}









function UpdateCustomer(Request $req){

    $s = Customer::where("UserId", $req->UserId)->first();

    if($s==null){
        return response()->json(["message"=>"Customer not found"],400);
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

        return response()->json(["message" => "Updated "], 200);

    }else{
        return response()->json(["message" => "Failed"], 400);
    }




   }


function ViewSingleCustomer(Request $req){
    $s = Customer::where("UserId", $req->UserId)->first();

    if($s==null){
        return response()->json(["message"=>"Customer not found"],400);
    }

   return $s;
}



function BlockCustomer(Request $req){
    $s = Customer::where("UserId", $req->UserId)->first();

    if($s==null){
        return response()->json(["message"=>"Customer not found"],400);
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

function UnBlockCustomer(Request $req){
    $s = Customer::where("UserId", $req->UserId)->first();

    if($s==null){
        return response()->json(["message"=>"Customer not found"],400);
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
        return response()->json(["message"=>"Failed to Unblock ".$s->Name],400);
    }

   }



function SuspendCustomer(Request $req){
    $s = Customer::where("UserId", $req->UserId)->first();

    if($s==null){
        return response()->json(["message"=>"Customer not found"],400);
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

function UnSuspendCustomer(Request $req){
    $s = Customer::where("UserId", $req->UserId)->first();

    if($s==null){
        return response()->json(["message"=>"Customer not found"],400);
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
        return response()->json(["message"=>"Failed to unsuspend ".$s->Name],400);
    }

}






function ViewAllCustomer(Request $req) {
    $s = Customer::get();

    if ($s->isEmpty()) {
        return response()->json(['message' => 'Customer not found'], 400);
    }


    $this->audit->Auditor($req->CustomerId, "Viewed All Customers");


    return response()->json($s);
}





function DeleteCustomer(Request $req){
    $s = Customer::where("UserId", $req->UserId)->first();

    if($s==null){
        return response()->json(["message"=>"Customer not found"],400);
    }

    $saver = $s->delete();
    if($saver){

        $message = $s->Name."  details was deleted";
        $this->audit->Auditor($req->AdminId, $message);

        return response()->json(["message"=>"Deleted Successfully"],200);
    }
    else{
        return response()->json(["message"=>"Deletion Failed"],400);
    }


}







function IdGenerator(): string {
    $randomID = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
    return $randomID;
}


}
