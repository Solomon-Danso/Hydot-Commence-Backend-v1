<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\AuditTrialController;
use App\Model\PaymentConfiguration;
use App\Models\CreditSales;
use App\Models\HirePurchase;
use App\Models\CollectionAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\SalesInvoice;

class MasterControllerV1 extends Controller
{
    protected $audit;

    public function __construct(AuditTrialController $auditTrialController)
    {
        $this->audit = $auditTrialController;

    }

function PaymentMethods(Request $req){

        $this->audit->RateLimit($req->ip());
        $rp= $this->audit->RoleAuthenticator($req->AdminId, "Can_Configure_PaymentMethods");
        if ($rp->getStatusCode() !== 200) {
            return $rp;  // Return the authorization failure response
        }

        $s = new PaymentConfiguration();
        $s->PaymentMethod = $req->PaymentMethod;


        $checker = PaymentConfiguration::where("PaymentMethod", $req->PaymentMethod)->first();
        if($checker){
            return response()->json(["message"=>"Payment method has already been configured"],400);
        }

        $saver = $s->save();
        if($saver){

            $message = $s->PaymentMethod." payment method has been configured";
            $this->audit->Auditor($req->AdminId, $message);

            return response()->json(["message"=>$message],200);
        }
        else{
            return response()->json(["message"=>"Payment method failed to be configured"],400);
        }




}

function ViewPaymentMethods(){
        $s = PaymentConfiguration::get();
        return $s;
}

function DeletePaymentMethods(Request $req){
        $this->audit->RateLimit($req->ip());
        $rp= $this->audit->RoleAuthenticator($req->AdminId, "Can_Configure_PaymentMethods");
        if ($rp->getStatusCode() !== 200) {
            return $rp;  // Return the authorization failure response
        }
    $s = PaymentConfiguration::where("Id", $req->Id)->first();
    if(!$s){
        return response()->json(["message"=>"Payment Method is not configured"],400);
    }

    $saver = $s->delete();
        if($saver){

            $message = $s->PaymentMethod." payment method has been deleted";
            $this->audit->Auditor($req->AdminId, $message);

            return response()->json(["message"=>$message],200);
        }
        else{
            return response()->json(["message"=>"Payment method failed to be deleted"],400);
        }





}

function ViewAwaitingCreditSales(){
    $c = CreditSales::where("IsApproved", "false")->get();
    return $c;
}

function AcceptCreditSales(Request $req){
    $c = CreditSales::where("ReferenceId", $req->ReferenceId)->first();
    if(!$c){
        return response()->json(["message"=>"Credit sales not found"],400);
    }

    $c->IsApproved = true;

    $s = new CollectionAccount();
    $s->OrderId = $c->OrderId;
    $s->AccountId = $this->audit->ProformaIdGenerator();
    $s->Phone = $c->Phone;
    $s->Email = $c->Email;
    $s->Debit = $c->CreditAmount;
    $s->Credit = 0;
    $s->UserId = $c->UserId;
    $s->FullName = $c->FullName;
    $s->Balance = $c->CreditAmount;
    $s->AccountType = "CreditSales";
    $s->Deadline = $req->Deadline;
    $s->AmountToPay = $c->CreditAmount/4;

    $currentDate = Carbon::now(); // Get the current date
    $deadlineDate = Carbon::parse($req->Deadline); // Convert Deadline to a Carbon instance
    $daysUntilDeadline = $currentDate->diffInDays($deadlineDate); // Calculate days between now and deadline
    $daysToPayment = $daysUntilDeadline / 4; // One fourth of the total days

    $s->DaysToPayment = ceil($daysToPayment); // Round up to the nearest whole day
    $s->NextBillingDate = $currentDate->addDays($daysToPayment);

    $c->save();

    $saver = $s->save();

    if($saver){
        //Send Email Informing the Client That Their Credit Sales has been approved

        $list = [
            "Fullname"=>$s->FullName,
            "OrderId" => $s->OrderId,
            "FirstPayment"=>$currentDate->addDays($daysToPayment),
            "SecondPayment"=>$currentDate->addDays($daysToPayment*2),
            "ThirdPayment"=>$currentDate->addDays($daysToPayment*3),
            "FourthPayment"=>$currentDate->addDays($daysToPayment*4),
            "Total" => $s->Debit

        ];

        try {
            Mail::to($s->Email)->send(new SalesInvoice( $list ));

        $message = "Approved ".$s->OrderId." orderId as a credit sales for ".$s->FullName;
        $this->audit->Auditor($req->AdminId, $message);
        return response()->json(["message"=>$s->OrderId." Approved Successfully"],200);
        } catch (\Exception $e) {

            return response()->json(['message' => $s->OrderId." Approved but email failed to send"], 200);
        }


    }
    else{
        return response()->json(["message"=>"Failed to approve order"],400);
    }





}


function RejectCreditSales(Request $req){
    $c = CreditSales::where("ReferenceId", $req->ReferenceId)->first();
    if(!$c){
        return response()->json(["message"=>"Credit sales not found"],400);
    }

    $saver = $c->save();

    if($saver){
        $message = "Rejected ".$s->OrderId." orderId as a credit sales for ".$s->FullName;
        $this->audit->Auditor($req->AdminId, $message);
        return response()->json(["message"=>$s->OrderId." Rejected Successfully"],200);

    }
    else{
        return response()->json(["message"=>"Failed to reject order"],400);
    }




}


//TODO:
/*
1. A function that will automatically send a manual payment Invoice To all Clients
2. A function a user can send the payment invoice to a specific customer
3. A function to cater for late payment, where a user can manually input the amount
4. A function from the frontend to the backend where the user can pay in advance
5. Users can automatically buy Vouchers which will be used by a specific customer to shop



*/



















}

