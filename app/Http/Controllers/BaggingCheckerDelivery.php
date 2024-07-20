<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bagging;
use Carbon\Carbon;
use App\Http\Controllers\AuditTrialController;
use App\Models\Checker;
use App\Models\Delivery;
use App\Models\Notification;
use App\Models\AdminUser;
use App\Models\MasterRepo;
use Illuminate\Support\Facades\Hash;

class BaggingCheckerDelivery extends Controller
{

    protected $audit;


    public function __construct(AuditTrialController $auditTrialController)
    {
        $this->audit = $auditTrialController;

    }

function CheckBagging(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Access_Bagging");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
 }
    $a = AdminUser::where("UserId", $req->AdminId)->first();

    if(!$a){
        return response()->json(["message"=>"Admin not found"],400);
    }

    $s = Bagging::where("BaggingId",$req->BaggingId)->first();

    if (!$s) {
        return response()->json(["message" => "No Order Found"], 400);
    }

    $m = MasterRepo::where("BaggingId",$req->BaggingId)->first();
    if (!$m) {
        return response()->json(["message" => "No Record Found"], 400);
    }



    $s->BAdminId = $a->UserId;
    $s->BAdminName = $a->Username;
    $s->BAdminPicture = $a->Picture;
    $s->BAdminDate = Carbon::now();
    $s->Status="Bagged";

    $saver = $s->save();
    if($saver){

        $c = new Checker();
        $c->MasterId = $s->MasterId;
        $c->UserId = $s->UserId;
        $c->OrderId = $s->OrderId;
        $c->BaggingId = $s->BaggingId;
        $c->PaymentId = $s->PaymentId;
        $c->BAdminId = $s->UserId;
        $c->BAdminName = $s->BAdminName;
        $c->BAdminPicture = $s->BAdminPicture;
        $c->BAdminDate = $s->BAdminDate;
        $c->CheckerId = $this->IdGenerator();
        $c->save();

        $m->CheckerId =  $c->CheckerId;
        $m->save();


        $message = "The Order Id: ".$s->OrderId." was bagged";
        $this->audit->Auditor($req->AdminId, $message);





        return response()->json(["message"=>"Bagged successfully, awaiting checking "],200);
    }else{
        return response()->json(["message"=>"Failed to bag order"],400);
    }

}

function ViewBaggingList(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Bagging");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}

   $message = "Viewed Unconfirmed Bagging List";
        $this->audit->Auditor($req->AdminId, $message);

    $s = Bagging::where("Status",null)->get();
    return $s;

}

function ViewConfirmedBaggingList(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Bagging");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}

    $message = "Viewed Confirmed Bagging List";
    $this->audit->Auditor($req->AdminId, $message);
    $s = Bagging::where("Status","Bagged")->get();
    return $s;

}





function CheckChecker(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Check_Checking");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}

    $a = AdminUser::where("UserId", $req->AdminId)->first();

    if(!$a){
        return response()->json(["message"=>"Admin not found"],400);
    }

    $s = Checker::where("CheckerId",$req->CheckerId)->first();

    if (!$s) {
        return response()->json(["message" => "No Order Found"], 400);
    }

    $m = MasterRepo::where("CheckerId",$req->CheckerId)->first();
    if (!$m) {
        return response()->json(["message" => "No Record Found"], 400);
    }


    $s->CAdminId = $a->UserId;
    $s->CAdminName = $a->Username;
    $s->CAdminPicture = $a->Picture;
    $s->CAdminDate = Carbon::now();
    $s->Status="Checked";

    $saver = $s->save();
    if($saver){

        $d = new Delivery();
        $d->MasterId = $s->MasterId;
        $d->UserId = $s->UserId;
        $d->OrderId = $s->OrderId;
        $d->BaggingId = $s->BaggingId;
        $d->PaymentId = $s->PaymentId;
        $d->BAdminId = $s->UserId;
        $d->BAdminName = $s->BAdminName;
        $d->BAdminPicture = $s->BAdminPicture;
        $d->BAdminDate = $s->BAdminDate;
        $d->CheckerId = $s->CheckerId;
        $d->CAdminId = $s->CAdminId;
        $d->CAdminName = $s->CAdminName;
        $d->CAdminPicture = $s->CAdminPicture;
        $d->CAdminDate = $s->CAdminDate;
        $d->save();

        $m->DeliveryId =  $s->DeliveryId;
        $m->save();

        $message = "The Order Id: ".$s->OrderId." was Checked";
        $this->audit->Auditor($req->AdminId, $message);







        return response()->json(["message"=>"Checked successfully, awaiting delivery "],200);
    }else{
        return response()->json(["message"=>"Failed to check order"],400);
    }

}

function ViewCheckerList(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Checking");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}

   $message = "Viewed Unconfirmed Checked List";
        $this->audit->Auditor($req->AdminId, $message);

    $s = Checker::where("Status",null)->get();
    return $s;

}
function ViewConfirmedCheckerList(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Checking");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}

   $message = "Viewed Confirmed Checked List";
        $this->audit->Auditor($req->AdminId, $message);

    $s = Checker::where("Status","Checked")->get();
    return $s;

}

function AssignForDelivery(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Assign_Delivery");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}

    $a = AdminUser::where("UserId", $req->UserId)->first();

    if(!$a){
        return response()->json(["message"=>"Admin not found"],400);
    }

    $s = Delivery::where("DeliveryId",$req->DeliveryId)->first();

    if (!$s) {
        return response()->json(["message" => "No Order Found"], 400);
    }




    $s->DAdminId = $a->UserId;
    $s->DAdminName = $a->Username;
    $s->DAdminPicture = $a->Picture;
    $rawPassword = $this->IdGenerator();
    $s->Password = bcrypt($rawPassword);

    $s->Status = "Assigned";

    $saver = $s->save();
    if($saver){

      //Notification to Customer Here
      $n = new Notification();
      $n->UserId = $s->UserId;
      $n->OrderId = $s->OrderId;
      $n->Subject = "Your Delivery is Ready";
      $n->Message = "We are pleased to inform you that your order is now ready for delivery.
                    Our delivery agent will contact you shortly to confirm the details and schedule a convenient time.
                    Please provide the following password to the agent when they are physically present for delivery:  ". $rawPassword."
                    Do not share this password with anyone else.
                    ";

      $n->save();

      $message = "The Order Id: ".$s->OrderId." was Assigned For Delivery";
      $this->audit->Auditor($req->AdminId, $message);







        return response()->json(["message"=>"Delivery Assigned Successfully "],200);
    }else{
        return response()->json(["message"=>"Failed to Assign"],400);
    }

}




function ViewUnAssignedDelivery(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId,  "Can_Assign_Delivery");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}

   $s = Delivery::where("Status","!=","Assigned")->get();
    $message = "Viewed UnAssigned Orders";
        $this->audit->Auditor($req->AdminId, $message);

    return $s;

}

function ViewAssignedDelivery(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId,  "Can_Assign_Delivery");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}

   $s = Delivery::where("Status","Assigned")->get();
    $message = "Viewed Universal Assigned Orders";
    $this->audit->Auditor($req->AdminId, $message);

    return $s;

}



function ViewSingleOrdersToDeliver(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Do_Delivery");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}

   $s = Delivery::where("DAdminId",$req->AdminId)->where("Status","Assigned")->get();
    $message = "Viewed Assigned Orders";
    $this->audit->Auditor($req->AdminId, $message);

    return $s;

}

function DeliverNow(Request $req){

    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Do_Delivery");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}

   $s = Delivery::where("OrderId",$req->OrderId)->first();

    $m = MasterRepo::where("OrderId",$req->OrderId)->first();
    if (!$m) {
        return response()->json(["message" => "No Record Found"], 400);
    }

    if ($s && Hash::check($req->Password, $s->Password)){

        $s->Status = "Delivered";
        $s->save();

        $m->Status = "Delivered";
        $m->save();

        $user = Notification::where("UserId",$s->UserId)->where("OrderId",$req->OrderId)->first();
        $user->delete();











        $message = "The Order Id: ".$req->OrderId." was successfully delivered";
    $this->audit->Auditor($req->AdminId, $message);


    return response()->json(["message" => "Order Delivered Successfully"], 400);


    }
    else{
        return response()->json(['message' => "Invalid credentials."], 401);
    }





}

function ViewSingleDeliveredOrders(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Do_Delivery");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}

   $s = Delivery::where("DAdminId",$req->AdminId)->where("Status","Delivered")->get();

    $message = "Viewed Delivered Orders ";
    $this->audit->Auditor($req->AdminId, $message);

    return $s;

}


function ViewDeliveredOrders(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId,  "Can_Track_Delivery");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
}

   $s = Delivery::where("Status","Delivered")->get();
    $message = "Viewed Universal Delivered Orders ";
    $this->audit->Auditor($req->AdminId, $message);
    return $s;

}






function IdGenerator(): string {
    $randomID = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
    return $randomID;
}


}
