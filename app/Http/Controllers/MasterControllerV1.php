<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\AuditTrialController;
use App\Model\PaymentConfiguration;
use App\Models\CreditSales;
use App\Models\HirePurchase;
use App\Models\CollectionAccount;
use App\Models\CollectionPaymentHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\SalesInvoice;
use App\Mail\HydotPay;
use Paystack;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;

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
    $s->Status = "InProcess"; //

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

public function SchedulePayment(Request $req){
    $currentDate = Carbon::now();

    $creditors = CollectionAccount::where($currentDate,">","=","NextBillingDate")
    ->where("Status","InProcess")
    ->get();
    $amount = 0;

    foreach($creditors as $c){

        if($c->Balance > $c->AmountToPay){
            $amount = $c->AmountToPay;
        }else{
            $amount = $c->Balance;
        }
        $TransactionId = $this->audit->IdGenerator();

        $s = new CollectionPaymentHistory;
        $s->AccountType = $c->AccountType;
        $s->AccountId = $c->AccountId;
        $s->UserId = $c->UserId;
        $s->Email = $c->Email;
        $s->OrderId = $c->OrderId;
        $s->OldBalance = $c->Balance;
        $s->TransactionId = $TransactionId;
        $s->AmountPaid = $amount;
        $s->NewBalance = $c->Balance - $amount;
        $s->Status = "Pending";
        $s->save();

        $list = [
            "TransactionId"=> $TransactionId,
            "Amount" => $amount,
            "Name" => $c->FullName,
            "UserId" => $c->UserId,
            "PaymentReference" =>`Scheduled payment for order with Id {$c->OrderId}`,

        ];
        try {
            Mail::to($c->Email)->send(new HydotPay( $list));
        } catch (\Exception $e) {
            Log::info(`Failed to send invoice to: {$c->Email}`);
        }




    }



}


public function ScheduleSinglePayment(Request $req){
    $currentDate = Carbon::now();

    $c = CollectionAccount::where("AccountId",$req->AccountId)
    ->where("Status","InProcess")
    ->first();
    $amount = $req->Amount;

        $TransactionId = $this->audit->IdGenerator();

        $s = new CollectionPaymentHistory;
        $s->AccountType = $c->AccountType;
        $s->AccountId = $c->AccountId;
        $s->UserId = $c->UserId;
        $s->Email = $c->Email;
        $s->OrderId = $c->OrderId;
        $s->OldBalance = $c->Balance;
        $s->TransactionId = $TransactionId;
        $s->AmountPaid = $amount;
        $s->NewBalance = $c->Balance - $amount;
        $s->Status = "Pending";
        $s->save();

        $list = [
            "TransactionId"=> $TransactionId,
            "Amount" => $amount,
            "Name" => $c->FullName,
            "UserId" => $c->UserId,
            "PaymentReference" =>`Scheduled payment for order with Id {$c->OrderId}`,

        ];
        try {
            Mail::to($c->Email)->send(new HydotPay( $list));
            $message = `Requested {$c->FullName} with UserId {$c->UserId} to pay {$amount} for the Order with Id {$c->OrderId}`;
            $this->audit->Auditor($req->AdminId, $message);
            return response()->json(["message"=>"Payment Scheduled Successfully"],200);

        } catch (\Exception $e) {
            Log::info("Failed to send invoice to: {$c->Email}");
            return response()->json(["message"=>`Failed to send invoice to: {$c->Email}`],200);

        }








}



public function MakePayment($TransactionId)
{
    $sales = CollectionPaymentHistory::where("TransactionId", $TransactionId)->first();
    if (!$sales) {
        return response()->json(["message" => "Transaction not found"], 400);
    }

    // Ensure the total amount is an integer and in the smallest currency unit (e.g., kobo, pesewas)
    $totalInPesewas = intval($sales->AmountPaid * 100);

    //$tref = Paystack::genTranxRef();
    $email = $sales->Email;

    $saver = $sales->save();
    if ($saver) {
        $response = Http::post('https://mainapi.hydottech.com/api/AddPayment', [
            'tref' =>  $TransactionId,
            'ProductId' => "hdtCollection",
            'Product' => 'Manual Collection',
            'Username' => $sales->UserId,
            'Amount' => $sales->AmountPaid,
            'SuccessApi' => 'https://127.0.0.1:8000/api/ConfirmPayment/' . $TransactionId,
            //'SuccessApi' => 'https://hydottech.com',
            'CallbackURL' => 'https://hydottech.com',
        ]);

        if ($response->successful()) {


            $paystackData = [
                "amount" => $totalInPesewas, // Amount in pesewas
                "reference" => $TransactionId,
                "email" => $email,
                "currency" => "GHS",
            ];

            return Paystack::getAuthorizationUrl($paystackData)->redirectNow();
        } else {
            return response()->json(["message" => "External Payment Api is down"], 400);
        }
    } else {
        return response()->json(["message" => "Failed to initialize payment"], 400);
    }
}



function ConfirmCreditPayment($TransactionId)
{

    $c = CollectionPaymentHistory::where("TransactionId", $TransactionId)->first();
    if (!$c) {
        return response()->json(["message" => "Transaction not found"], 400);
    }

    $c->Status = "Confirmed";

    $ca = CollectionAccount::where("AccountId", $c->AccountId)->first();
    if (!$ca) {
        return response()->json(["message" => "Account not found"], 400);
    }

    $oldDate = $ca->NextBillingDate;
    $newBalance = $c->Balance - $c->AmountPaid;
    $ca->Debit = $c->Balance;
    $ca->Credit = $c->AmountPaid;
    $ca->Balance =  $newBalance;

    if( $newBalance <= 0){
        $ca->Status = "Completed";
    }
    else{
        $ca->Status = "InProcess";
    }

    $ca->NextBillingDate = $oldDate->addDays($ca->DaysToPayment);

    $p = new Payment();
    $p->OrderId = $ca->OrderId;
    $p->Phone = $ca->Phone;
    $p->Email = $ca->Email;
    $p->AmountPaid =  $c->AmountPaid;
    $p->UserId = $ca->UserId;




    $cSaver = $c->save();
    $caSaver = $ca->save();
    $pSaver = $p->save();

    if( $cSaver & $caSaver & $pSaver ){
        $message = "A payment of ".$c->AmountPaid." has been made for the order with ID ".$ca->OrderId." as ".$ca->AccountType;
        $this->audit->CustomerAuditor($ca->UserId, $message);
        return response()->json(["message"=>"Operation was successful"],200);

    }
    else{
        return response()->json(["message"=>"Operation was unsuccessful"],400);
    }








}


//Voucher System 










//TODO:
/*
1. A function that will automatically send a manual payment Invoice To all Clients [Done]
2. A function a user can send the payment invoice to a specific customer [Done]
3. A function to cater for late payment, where a user can manually input the amount [Done]
4. A function from the frontend to the backend where the user can pay in advance [Will Cause A Lot Of Issues]
5. Users can automatically buy Vouchers which will be used by a specific customer to shop



*/



















}
