<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\AuditTrialController;
use App\Models\PaymentConfiguration;
use App\Models\CreditSales;
use App\Models\HirePurchase;
use App\Models\CollectionAccount;
use App\Models\CollectionPaymentHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\SalesInvoice;
use App\Mail\CreditPayment;
use App\Mail\ShoppingCards;
use Paystack;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\ShoppingCard;
use App\Models\ShoppingCardCollector;
use App\Models\Customer;
use App\Models\MasterRepo;
use App\Models\Bagging;
use App\Models\DeliveryConfig;
use App\Models\Product;
use App\Models\PaymentOnDelivery;
use App\Models\Order;

class MasterControllerV1 extends Controller
{
    protected $audit;

    public function __construct(AuditTrialController $auditTrialController)
    {
        $this->audit = $auditTrialController;

    }




function PaymentMethods(Request $req){

        $this->audit->RateLimit($req->ip());
        $rp= $this->audit->RoleAuthenticator($req->AdminId, "Can_Configure_System");
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
        $rp= $this->audit->RoleAuthenticator($req->AdminId, "Can_Configure_System");
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

function ViewAwaitingCreditSales(Request $req){

    $this->audit->RateLimit($req->ip());
    $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Handle_Credit_Sales");
    if ($rp->getStatusCode() !== 200) {
     return $rp;  // Return the authorization failure response
 }

    $c = CreditSales::where("IsApproved", "false")->get();
    return $c;
}

function ViewSingleAwaitingCreditSales(Request $req){

    $this->audit->RateLimit($req->ip());
    $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Handle_Credit_Sales");
    if ($rp->getStatusCode() !== 200) {
     return $rp;  // Return the authorization failure response
 }

    $c = CreditSales::where("ReferenceId", $req->ReferenceId)->first();
    if(!$c){
        return response()->json(["message"=>"Sales not available"],400);
    }
    return $c;
}



public function AcceptCreditSales(Request $req)
{
    // Rate limit and role authentication checks
    $this->audit->RateLimit($req->ip());
    $rp = $this->audit->RoleAuthenticator($req->AdminId, "Can_Handle_Credit_Sales");
    if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }

    // Retrieve the credit sales record
    $c = CreditSales::where("ReferenceId", $req->ReferenceId)->first();
    if (!$c) {
        return response()->json(["message" => "Credit sales not found"], 400);
    }

    // Approve the credit sales
    $c->IsApproved = true;

    // Initialize CollectionAccount
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
    $s->UserPic = $c->UserPic;
    $s->IDFront = $c->IDFront;
    $s->IDBack = $c->IDBack;
    // Assign the calculated value to the DaysToPayment field

    // Get the user-selected number of sessions
    $numSessions = $req->Sessions; // Default to 4 sessions if not provided

    if ($numSessions < 1) {
        return response()->json(["message" => "Invalid number of sessions"], 400);
    }

    // Calculate total amount to pay per session
    $amountPerSession = $c->CreditAmount / $numSessions;

    // Calculate the payment dates based on the deadline
    $currentDate = Carbon::now(); // Current date
    $deadlineDate = Carbon::parse($req->Deadline); // Convert deadline to a Carbon instance
    $daysUntilDeadline = $currentDate->diffInDays($deadlineDate); // Calculate days until the deadline
    $daysToPayment = $daysUntilDeadline / $numSessions; // Divide by the number of sessions
    $s->DaysToPayment = $daysToPayment;
    // Initialize payment plan array
    $paymentPlan = [];
    for ($i = 1; $i <= $numSessions; $i++) {
        $paymentDate = $currentDate->copy()->addDays($daysToPayment * $i);

        // Ensure the last payment does not exceed the deadline
        if ($i == $numSessions && $paymentDate->greaterThan($deadlineDate)) {
            $paymentDate = $deadlineDate;
        }

        // Store the payment plan details
        $paymentPlan[] = [
            "Session" => $i,
            "Amount" => round($amountPerSession, 2), // Rounded for precision
            "PaymentDate" => $paymentDate
        ];
    }

    // Save the payment session details to the CollectionAccount model
    $s->NextBillingDate = $currentDate->addDays($daysToPayment);
    $s->AmountToPay = round($amountPerSession, 2); // First payment amount
    $s->Status = "InProcess";

    // Save the credit sale and collection account
    $c->save();
    $saver = $s->save();

    if ($saver) {
        // Create MasterRepo and Bagging records
        $baggingId = $this->audit->IdGenerator();

        $m = new MasterRepo();
        $m->MasterId = $c->OrderId;
        $m->UserId = $s->UserId;
        $m->OrderId = $c->OrderId;
        $m->BaggingId = $baggingId;
        $m->save();

        $b = new Bagging();
        $b->MasterId = $c->OrderId;
        $b->UserId = $s->UserId;
        $b->OrderId = $c->OrderId;
        $b->BaggingId = $baggingId;
        $b->PaymentId = $s->AccountId;
        $b->save();

        // Update product quantities and order statuses
        $orderList = Order::where("UserId", $c->UserId)->where("OrderId", $c->OrderId)->get();
        foreach ($orderList as $o) {
            $product = Product::where("ProductId", $o->ProductId)->first();
            if (!$product) {
                return response()->json(["message" => "Invalid Product in your order"], 400);
            }

            $product->Quantity -= $o->Quantity;
            $product->PurchaseCounter += 1;
            $product->save();

            $o->OrderStatus = "awaiting delivery";
            $o->save();
        }

        // Prepare payment plan and email list
        $list = [
            "Fullname" => $s->FullName,
            "OrderId" => $s->OrderId,
            "PaymentPlan" => $paymentPlan, // Add the payment plan
            "Total" => $s->Debit
        ];

        // Try sending the email
        try {
            Mail::to($s->Email)->send(new SalesInvoice($list));

            $message = "Approved " . $s->OrderId . " orderId as a credit sales for " . $s->FullName;
            $this->audit->Auditor($req->AdminId, $message);

            return response()->json(["message" => $s->OrderId . " Approved Successfully"], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $s->OrderId . " Approved but email failed to send"], 200);
        }
    } else {
        return response()->json(["message" => "Failed to approve order"], 400);
    }
}



function RejectCreditSales(Request $req){

    $this->audit->RateLimit($req->ip());
    $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Handle_Credit_Sales");
    if ($rp->getStatusCode() !== 200) {
     return $rp;  // Return the authorization failure response
 }

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
            Mail::to($c->Email)->send(new CreditPayment( $list));
        } catch (\Exception $e) {
            Log::info(`Failed to send invoice to: {$c->Email}`);
        }




    }



}



public function ScheduleSinglePayment(Request $req){

    $this->audit->RateLimit($req->ip());
    $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Handle_Credit_Sales");
    if ($rp->getStatusCode() !== 200) {
     return $rp;  // Return the authorization failure response
 }


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
            Mail::to($c->Email)->send(new CreditPayment( $list));
            $message = `Requested {$c->FullName} with UserId {$c->UserId} to pay {$amount} for the Order with Id {$c->OrderId}`;
            $this->audit->Auditor($req->AdminId, $message);
            return response()->json(["message"=>"Payment Scheduled Successfully"],200);

        } catch (\Exception $e) {
            Log::info("Failed to send invoice to: {$c->Email}");
            return response()->json(["message"=>`Failed to send invoice to: {$c->Email}`],200);

        }








}



public function MakeCreditPayment($TransactionId)
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
        try {
            $response = Http::timeout(30)->post('https://mainapi.hydottech.com/api/AddPayment', [
                'tref' =>  $TransactionId,
            'ProductId' => "hdtCollection",
            'Product' => 'Manual Collection',
            'Username' => $sales->UserId,
            'Amount' => $sales->AmountPaid,
            'SuccessApi' => 'https://api.commerce.hydottech.com/api/ConfirmPayment/' . $TransactionId,
            //'SuccessApi' => 'https://hydottech.com',
            'CallbackURL' => 'https://web.commerce.hydottech.com/orders',
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
                return response()->json(["message" => "External Payment API is down"], 400);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Handle timeout exception
            return response()->json(["message" => "Payment API request timed out. Please try again."], 408);
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json(["message" => "An error occurred while processing your payment. Please try again."], 500);
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
    $p->Status = "confirmed";




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

function ViewCollectionAccount(Request $req){
    $this->audit->RateLimit($req->ip());
    $rp = $this->audit->RoleAuthenticator($req->AdminId, "Can_Handle_Credit_Sales");
    if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }

    $creditors = CollectionAccount::get();

    return $creditors;



}

function ViewCollectionAccountHistory(Request $req){
    $this->audit->RateLimit($req->ip());
    $rp = $this->audit->RoleAuthenticator($req->AdminId, "Can_Handle_Credit_Sales");
    if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }

    $creditors = CollectionPaymentHistory::where("AccountId", $req->AccountId)->get();

    return $creditors;



}




//Shopping Card Information

// public function ScheduleShoppingCard(Request $req){

//     $user = Customer::where('Email', $req->Email)->first();
//     if(!$user){
//         return response()->json(['message' => 'Invalid Customer Email'], 400);

//     }

//     $maker = Customer::where('UserId',  $req->UserId)->first();
//     if(!$maker){
//         return response()->json(['message' => 'Not Authorized'], 400);

//     }


//         $TransactionId = $this->audit->IdGenerator();

//         $s = new ShoppingCardCollector();
//         $s->TransactionID = $TransactionId;
//         $s->PurchasedByID = $maker->UserId;
//         $s->Amount = $req->Amount;
//         $s->AccountHolderID = $user->UserId;
//         $s->AccountHolderName = $user->Username;
//         $s->Status = "Pending";
//         $s->Email = $maker->Email;

//         $checker = ShoppingCard::where("AccountHolderID",$user->UserId)->first();

//         if($checker){
//             $s->CardNumber = $checker->CardNumber;
//         }
//         else{
//             $s->CardNumber = $this->audit->IdGenerator();
//         }
//         $s->save();

//         $list = [
//             "TransactionId"=> $TransactionId,
//             "Amount" => $s->Amount,
//             "Name" => $s->AccountHolderName,
//             "UserId" => $s->AccountHolderID,
//             "PaymentReference" =>`Shopping Card Topup for {$s->AccountHolderName}`,

//         ];
//         try {
//             Mail::to($maker->Email)->send(new ShoppingCards( $list));
//             $message = `Shopping Card Topup for {$s->AccountHolderName}`;
//             $this->audit->CustomerAuditor($s->PurchasedByID, $message);
//             return response()->json(["message"=>"Check Your Email To Complete The Topup"],200);

//         } catch (\Exception $e) {
//             Log::info("Failed to send invoice to: {$maker->Email}, ".$e);
//             return response()->json(["message"=>`Failed to send Email to: {$maker->Email}`],200);

//         }








// }

public function ScheduleShoppingCard(Request $req) {
    $user = Customer::where('Email', $req->Email)->first();
    if (!$user) {
        return response()->json(['message' => 'Invalid Customer Email'], 400);
    }

    $maker = Customer::where('UserId', $req->UserId)->first();
    if (!$maker) {
        return response()->json(['message' => 'Not Authorized'], 400);
    }

    $TransactionId = $this->audit->IdGenerator();

    $s = new ShoppingCardCollector();
    $s->TransactionID = $TransactionId;
    $s->PurchasedByID = $maker->UserId;
    $s->Amount = $req->Amount;
    $s->AccountHolderID = $user->UserId;
    $s->AccountHolderName = $user->Username;
    $s->Status = "Pending";
    $s->Email = $maker->Email;

    $checker = ShoppingCard::where("AccountHolderID", $user->UserId)->first();

    if ($checker) {
        $s->CardNumber = $checker->CardNumber;
    } else {
        $s->CardNumber = $this->audit->IdGenerator();
    }

    $s->save();

    $list = [
        "TransactionId" => $TransactionId,
        "Amount" => $s->Amount,
        "Name" => $s->AccountHolderName,
        "UserId" => $s->AccountHolderID,
        "PaymentReference" => "Shopping Card Topup for {$s->AccountHolderName}",
    ];

    try {
        Mail::to($maker->Email)->send(new ShoppingCards($list));

        $message = "Shopping Card Topup for {$s->AccountHolderName}";
        $this->audit->CustomerAuditor($s->PurchasedByID, $message);

        return response()->json(["message" => "Check Your Email To Complete The Topup"], 200);
    } catch (\Exception $e) {
        Log::info("Failed to send invoice to: {$maker->Email}, " . $e->getMessage());

        return response()->json(["message" => "Failed to send Email to: {$maker->Email}"], 400);
    }
}



public function MakePaymentForShoppingCard($TransactionId)
{
    $sales = ShoppingCardCollector::where("TransactionId", $TransactionId)->first();
    if (!$sales) {
        return response()->json(["message" => "Transaction not found"], 400);
    }

    // Ensure the total amount is an integer and in the smallest currency unit (e.g., kobo, pesewas)
    $totalInPesewas = intval($sales->Amount * 100);

    //$tref = Paystack::genTranxRef();
    $email = $sales->Email;

    $saver = $sales->save();
    $saver = $sales->save();
if ($saver) {
    try {
        $response = Http::timeout(30)->post('https://mainapi.hydottech.com/api/AddPayment', [
            'tref' =>  $TransactionId,
            'ProductId' => "hdtCollection",
            'Product' => 'Manual Collection',
            'Username' => $sales->UserId,
            'Amount' => $sales->Amount,
            'SuccessApi' => 'https://api.commerce.hydottech.com/api/ConfirmShoppingCardPayment/' . $TransactionId,
            //'SuccessApi' => 'https://hydottech.com',
            'CallbackURL' => 'https://web.commerce.hydottech.com/shoppingCard/',
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
            return response()->json(["message" => "External Payment API is down"], 400);
        }

    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        // Handle timeout exception
        return response()->json(["message" => "Payment API request timed out. Please try again."], 408);
    } catch (\Exception $e) {
        // Handle any other exceptions
        return response()->json(["message" => "An error occurred while processing your payment. Please try again."], 500);
    }
} else {
    return response()->json(["message" => "Failed to initialize payment"], 400);
}





}




function ConfirmShoppingCardPayment($TransactionId)
{
    $c = ShoppingCardCollector::where("TransactionId", $TransactionId)->first();

    if (!$c) {
        return response()->json(["message" => "Transaction not found"], 400);
    }

    $g = ShoppingCardCollector::where("TransactionId", $TransactionId)->where("Status", "Confirmed")->first();

    if ($g) {
        return response()->json(["message" => "Transaction already Confirmed"], 400);
    }

    // Update the status of the shopping card transaction
    $c->Status = "Confirmed";

    // Check if the card exists; update or create as necessary
    $d = ShoppingCard::where("CardNumber", $c->CardNumber)->first();
    if($d){
        $d->Amount += $c->Amount;
    }else{
        $d = ShoppingCard::firstOrNew();
        $d->CardNumber = $c->CardNumber;
        $d->PurchasedByID = $c->PurchasedByID;
        $d->AccountHolderID = $c->AccountHolderID;
        $d->AccountHolderName = $c->AccountHolderName;
        $d->Amount = $c->Amount;
    }

    $saver = $d->save();

    // Create a payment record
    $p = new Payment();

    $p->OrderId = "Shopping Card";
    $p->Phone = "For: {$c->PurchasedByID}";
    $p->Email = $c->Email;
    $p->AmountPaid = $c->Amount;
    $p->UserId = $c->PurchasedByID;
    $p->Status = "confirmed";



    // Save both the collector and payment records
    $cSaver = $c->save();
    $pSaver = $p->save();

    // If all saves are successful, log the audit and return success response
    if ($cSaver && $pSaver && $saver) {
        $message = "A payment of {$c->Amount} has been made for a shopping card top-up by {$p->UserId}";
        $this->audit->CustomerAuditor($p->UserId, $message);
        return response()->json(["message" => "Operation was successful"], 200);
    }

    return response()->json(["message" => "Operation was unsuccessful"], 400);
}


function CardTopupHistory(Request $req){
    $sales = ShoppingCardCollector::where("CardNumber", $req->CardNumber)->get();
    $message = "Viewed Card Topup History for " . $req->CardNumber;
    $this->audit->CustomerAuditor($p->UserId, $message);
    return $sales;
}

function CardInformation(Request $req){
    $sales = ShoppingCard::where("AccountHolderID", $req->UserId)->first();
    return $sales;
}


public function ConfirmPaymentOnDelivery(Request $req){

    $this->audit->RateLimit($req->ip());

    $rp = $this->audit->RoleAuthenticator($req->AdminId, "Can_Handle_PaymentOnDelivery");
    if ($rp->getStatusCode() !== 200) {
        return $rp;
    }

   $p =  PaymentOnDelivery::where("OrderId",$req->OrderId)->first();
   if(!$p){
    return response()->json(["message"=>"Payment does not exist"],400);
   }

   $q =  Payment::where("OrderId",$req->OrderId)->first();
   if(!$q){
    return response()->json(["message"=>"Payment does not exist"],400);
   }

   if($req->Amount < $p->Amount){
    return response()->json(["message"=>"The amount you entered {$req->Amount} is less than the required amount {$p->Amount}"],400);
   }

   $p->IsFullyPaid = true;

   $q->Status = "confirmed";
   $q->ReferenceId = "Payment On Delivery for the Order ".$p->OrderId;

   $q->save();

   $saver = $p->save();

   if($saver){
    $message = "For the Order with ID {$req->OrderId}, the amount is {$p->Amount} and this admin entered {$req->Amount} as payment amount";
    $this->audit->Auditor($req->AdminId, $message);
    return response()->json(["message"=>"Payment made successfully"],200);
    }
    else{
        return response()->json(["message"=>"Failed to complete payment"],400);
    }




}

public function ViewPaymentOnDelivery(Request $req){

    $this->audit->RateLimit($req->ip());

    $rp = $this->audit->RoleAuthenticator($req->AdminId, "Can_Handle_PaymentOnDelivery");
    if ($rp->getStatusCode() !== 200) {
        return $rp;
    }

   $p =  PaymentOnDelivery::get();

   return $p;



}


public function DeliveryConfig(Request $req)
{
    // Rate limit based on IP
    $this->audit->RateLimit($req->ip());

    // Role authentication
    $rp = $this->audit->RoleAuthenticator($req->AdminId, "Can_Configure_Delivery");
    if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }

    // Fetch latitude and longitude from the address using getLatnLong function
    $locationData = $this->getLatnLong($req->Location);

    if (!$locationData) {
        // If location data could not be fetched
        return response()->json(["message" => "Invalid Location Provided"], 400);
    }

    // Update or create the DeliveryConfig
    $s = DeliveryConfig::firstOrNew();
    $s->Latitude = $locationData['lat'];  // Set latitude from geocoding response
    $s->Longitude = $locationData['lon'];  // Set longitude from geocoding response

    $s->PricePerKm = $req->PricePerKm;
    $s->Location = $req->Location;

    $saver = $s->save();

    if ($saver) {
        return response()->json(["message" => "Price Configured Successfully"], 200);
    } else {
        return response()->json(["message" => "Price Configuration Failed"], 500);
    }
}

function ViewDeliveryConfig(){
    $s = DeliveryConfig::get();
    return $s;
}




public function getLatnLong($userAddress)
{
    $apiKey = env('GOOGLE_MAPS_API_KEY');
    $geocodeUrl = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($userAddress) . "&key=" . $apiKey;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $geocodeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $geocodeResponse = curl_exec($ch);
    curl_close($ch);

    $geocodeData = json_decode($geocodeResponse, true);

    if (!isset($geocodeData['results'][0]['geometry']['location'])) {
        return false;
    }

    $userLat = $geocodeData['results'][0]['geometry']['location']['lat'];
    $userLon = $geocodeData['results'][0]['geometry']['location']['lng'];

    return [
        "lat" => $userLat,
        "lon" => $userLon,
    ];
}




function RunPromotion(Request $req){

    $this->audit->RateLimit($req->ip());
    $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Run_Promotion");
    if ($rp->getStatusCode() !== 200) {
     return $rp;  // Return the authorization failure response
    }

    $prd = Product::where("ProductId", $req->ProductId)->first();
    if (!$prd){
        return response()->json(["message"=>"Product does not exist"],400);
    }
    $discount = $req->DiscountPercentage * $prd->Price/100;
    $prd->DiscountPrice = $prd->Price - $discount;
    $prd->DiscountPercentage = $req->DiscountPercentage;
    $prd->ValidUntil = $req->ValidUntil;
    $prd->Status = "ActivePromotion";
    $saver = $prd->save();
    if($saver){
        $message = "Promotion valid until ". $req->ValidUntil;
        $this->audit->Auditor($req->AdminId, $message);
        return response()->json(["message"=>$message],200);
    }
    else{
        return response()->json(["message"=>"Failed to schedule promotion"],400);
    }


}

function RevertPromotion(Request $req){

    $this->audit->RateLimit($req->ip());
    $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Run_Promotion");
    if ($rp->getStatusCode() !== 200) {
     return $rp;  // Return the authorization failure response
    }

    $prd = Product::where("ProductId", $req->ProductId)->first();
    if (!$prd){
        return response()->json(["message"=>"Product does not exist"],400);
    }
    $prd->DiscountPrice = 0;
    $prd->DiscountPercentage = 0;
    $prd->ValidUntil = null;
    $prd->Status = "DisablePromotion";
    $saver = $prd->save();
    if($saver){
        $message = "Promotion reverted ";
        $this->audit->Auditor($req->AdminId, $message);
        return response()->json(["message"=>$message],200);
    }
    else{
        return response()->json(["message"=>"Failed to revert promotion"],400);
    }


}






















}

