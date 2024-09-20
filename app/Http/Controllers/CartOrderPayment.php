<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\AuditTrialController;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
use App\Models\MasterRepo;
use App\Models\Payment;
use Paystack;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Bagging;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use App\Models\CreditSales;
use App\Models\CollectionAccount;
use App\Models\ShoppingCard;


class CartOrderPayment extends Controller
{
    protected $audit;


    public function __construct(AuditTrialController $auditTrialController)
    {
        $this->audit = $auditTrialController;

    }

function AddToCart(Request $req){



        $this->audit->RateLimit($req->ip());

        $c = Customer::where("UserId", $req->UserId)->first();
        if(!$c){
            return response()->json(["message"=>"Invalid User"],400);
        }

        $p = Product::where("ProductId", $req->ProductId)->first();
        if(!$p){
            return response()->json(["message"=>"Invalid Product"],400);
        }

        if ($req->Quantity < 0) {
            $this->audit->ManualFreeze($req->ip(), 0, 10);
        }



        $checker = Cart::where("ProductId", $req->ProductId)->where("UserId", $req->UserId)->where("Size", $req->Size)->first();
        if($checker){

            $TotalQuantity = $checker->Quantity + $req->Quantity;

            if($TotalQuantity>$p->Quantity){
                return response()->json(["message" => "Your requested quantity exceeds the available stock."], 400);
            }

            $checker->Quantity =  $TotalQuantity;
            $checker->Size = $req->Size;
            $saver = $checker->save();
            if($saver){

                $message = $p->Title." was added to cart";
                $this->audit->CustomerAuditor($req->UserId, $message);
                return response()->json(["message"=>$checker->Title." added to cart successfully"],200);
            }else{
                return response()->json(["message"=>"Failed to add ".$checker->Title." to cart"],400);

            }


        }
        else{

            $s = new Cart();
            $s->CartId = $this->IdGenerator();
            $s->MenuId = $p->MenuId;
            $s->CategoryId = $p->CategoryId;
            $s->ProductId = $p->ProductId;
            $s->Picture = $p->Picture;
            $s->Title = $p->Title;

            if($p->DiscountPrice>0){
                $s->Price = $p->DiscountPrice;
            }else{
                $s->Price = $p->Price;
            }


            if ($req->Quantity > $p->Quantity) {
                return response()->json(["message" => "Your requested quantity exceeds the available stock."], 400);
            }

            $s->Quantity = $req->Quantity;
            $s->Size = $req->Size;
            $s->UserId = $c->UserId;

            $saver = $s->save();
            if($saver){
                $message = $p->Title." was added to cart";
                $this->audit->CustomerAuditor($req->UserId, $message);

                return response()->json(["message"=>$s->Title." added to cart successfully"],200);
            }else{
                return response()->json(["message"=>"Failed to add ".$s->Title." to cart"],400);

            }

        }




}

function UpdateCart(Request $req){
        $this->audit->RateLimit($req->ip());

        $s = Cart::where("CartId", $req->CartId)->first();
        if(!$s){
            return response()->json(["message"=>"Invalid Cart Item"],400);
        }

        $p = Product::where("ProductId", $s->ProductId)->first();
        if(!$p){
            return response()->json(["message"=>"Invalid Product"],400);
        }

        if ($req->Quantity > $p->Quantity) {
            return response()->json(["message" => "Your requested quantity exceeds the available stock."], 400);
        }

        if($req->filled("Quantity")){
            $s->Quantity = $req->Quantity;
        }

        if($req->filled("Size")){
            $s->Size = $req->Size;
        }







        $saver = $s->save();
        if($saver){
            $message = $p->Title." was updated in cart";
            $this->audit->CustomerAuditor($req->UserId, $message);

            return response()->json(["message"=>"Success"],200);
        }else{
            return response()->json(["message"=>"Failed"],400);

        }


    }

    function ViewAllCart(Request $req){
        $this->audit->RateLimit($req->ip());

        $s = Cart::where("UserId", $req->UserId)->get();
        $message = "Viewed all items in cart";
        $this->audit->CustomerAuditor($req->UserId, $message);


        return $s;
}

function DeleteCart(Request $req){
        $this->audit->RateLimit($req->ip());

        $s = Cart::where("CartId", $req->CartId)->first();
        if(!$s){
            return response()->json(["message"=>"Invalid Cart Item"],400);
        }

        $saver = $s->delete();
        if($saver){
            $message = $s->Title." was deleted from cart";
            $this->audit->CustomerAuditor($req->UserId, $message);

            return response()->json(["message"=>"Deleted Successfully"],200);
        }else{
            return response()->json(["message"=>"Failed to Delete, Try Again"],400);

        }


}

function AddToOrder(Request $req){



        $this->audit->RateLimit($req->ip());

        $cartList = Cart::where("UserId", $req->UserId)->get();
        if($cartList->isEmpty()) {
            return response()->json(["message"=>"Your cart is empty"],400);
        }

        $OrderId = $this->IdGenerator();

        foreach($cartList as $item){

            $s = new Order();
            $s->OrderId = $OrderId;
            $s->CartId = $item->CartId;
            $s->MenuId = $item->MenuId;
            $s->CategoryId = $item->CategoryId;
            $s->ProductId = $item->ProductId;
            $s->Picture = $item->Picture;
            $s->Price = $item->Price;
            $s->Quantity = $item->Quantity;
            $s->Title = $item->Title;
            $s->Size = $item->Size;
            $s->UserId = $item->UserId;
            $s->Country = $req->Country;
            $s->Region = $req->Region;
            $s->City = $req->City;
            $s->DigitalAddress = $req->DigitalAddress;
            $s->DetailedAddress = $req->DetailedAddress;
            $s->OrderStatus= "pending order";
            $s->save();

            $c = Cart::where("CartId", $item->CartId)->first();
            $c->delete();

        }

        $m = new MasterRepo();
        $m->MasterId =  $OrderId;
        $m->UserId =  $req->UserId;
        $m->OrderId = $OrderId;
        $m->save();

        $message = "Placed an order with Id ".$OrderId;
        $this->audit->CustomerAuditor($req->UserId, $message);




        return response()->json(["message"=>"Order placed successfully"],200);







}

function ViewAllOrder(Request $req)
    {
        $this->audit->RateLimit($req->ip());

        $subQuery = Order::selectRaw('MAX(created_at) as latest_created_at')
                         ->where('UserId', $req->UserId)
                         ->groupBy('OrderId');

        $orders = Order::joinSub($subQuery, 'sub', function ($join) {
            $join->on('orders.created_at', '=', 'sub.latest_created_at');
        })->where('UserId', $req->UserId)
          ->orderBy('created_at', 'desc')
          ->get();

          $message = "Viewed all orders";
          $this->audit->CustomerAuditor($req->UserId, $message);


        return $orders;
    }



    function DetailedOrder(Request $req){
        $this->audit->RateLimit($req->ip());
        $s = Order::where("UserId", $req->UserId)->where("OrderId", $req->OrderId)->get();
        $message = "Viewed details of the order ".$req->OrderId;
        $this->audit->CustomerAuditor($req->UserId, $message);

        return $s;

    }

    function DetailedAllOrder(Request $req){
        $this->audit->RateLimit($req->ip());
        $s = Order::where("OrderId", $req->OrderId)->get();
        $message = "Viewed details of the order ".$req->OrderId;
        $this->audit->Auditor($req->AdminId, $message);

        return $s;

}

function EditProductInDetailedOrder(Request $req){
        $this->audit->RateLimit($req->ip());
        $s = Order::where("UserId", $req->UserId)->where("ProductId", $req->ProductId)->first();
        if(!$s){
            return response()->json(["message"=>"Invalid Product in your order"],400);
        }

        $b = Bagging::where("UserId", $req->UserId)->where("OrderId", $s->OrderId)->first();
        if($b){
            return response()->json(["message"=>$s->Title." has already been processed"],400);
        }

        $p = Product::where("ProductId", $req->ProductId)->first();
        if(!$p){
            return response()->json(["message"=>"Invalid Product in your order"],400);
        }

        if($req->Quantity > $p->Quantity){
            return response()->json(["message" => "Your requested quantity exceeds the available stock."], 400);
        }

        if($req->filled("Size")){
            $s->Size = $req->Size;
        }


        if($req->filled("Quantity")){
            $s->Quantity = $req->Quantity;
        }

        $saver = $s->save();
        if($saver){
            $message = $s->Title." quantity was updated in ".$s->OrderId;
            $this->audit->CustomerAuditor($req->UserId, $message);

            return response()->json(["message"=>$s->Title." quantity has been updated"],200);
        }{
            return response()->json(["message"=>"Failed to update quantity"],400);
        }






}


function AddDeliveryDetails(Request $req){
        $this->audit->RateLimit($req->ip());
        $r = Customer::where("UserId", $req->UserId)->first();
        if (!$r) {
            return response()->json(["message" => "Customer does not exist"], 400);
        }
        $dataList = Order::where("OrderId", $req->OrderId)->get();

        // Check if the $dataList is not empty
        if (!$dataList->isEmpty()) {
            foreach($dataList as $s){

                $s->Country = $req->Country;
                $s->Region = $req->Region;
                $s->City = $req->City;
                $s->DigitalAddress = $req->DigitalAddress;
                $s->DetailedAddress = $req->DetailedAddress;
                $s->OrderStatus = "awaiting payment";
                $s->save();

            }
        } else {
            return response()->json(["message"=>"Order does not exist"],400);
        }

        $orderList = Order::where("UserId", $req->UserId)->where("OrderId", $req->OrderId)->get();

        foreach($orderList as $o){
            $product = Product::where("ProductId", $o->ProductId)->first();
            if(!$product){
                return response()->json(["message"=>"Invalid Product in your order"],400);
            }

            if($o->Quantity > $product->Quantity){
                $message = "Current quantity in stock for ".$product->Title ." ". $this->Grammer($product->Quantity)." ".$product->Quantity;
                return response()->json(["message"=>$message],400);
            }
        }

        $pay = Payment::where("UserId", $req->UserId)
        ->where("OrderId", $req->OrderId)
        ->where("Status", "confirmed")
        ->first();

        if ($pay) {
            return response()->json(["message" => "Payment already completed, awaiting delivery"], 400);
        }

        $m = MasterRepo::where("OrderId", $req->OrderId)->first();
        if (!$m) {
            return response()->json(["message" => "Main Order does not exist"], 400);
        }

        $total = Order::where("UserId", $req->UserId)->where("OrderId", $req->OrderId)->sum(DB::raw('Price * Quantity'));


    $distance = $this->getRoadDistance($req->Latitude, $req->Longitude);

    $q = DeliveryConfig::first();
        $shipping =  $distance * $q->PricePerKm;
        $totalPay = $total + $shipping;
        $formattedTotal = number_format($totalPay, 2, '.', '');


if($req->PaymentMethod == "Mobile Money or Credit Card"){

    $p = new Payment();
    $p->OrderId = $req->OrderId;
    $p->Phone = $r->Phone;
    $p->Email = $r->Email;
    $p->AmountPaid = $formattedTotal;
    $p->UserId = $req->UserId;

    $saver = $p->save();

    if($saver){
        $message = $req->OrderId." order has been placed";
        $this->audit->CustomerAuditor($req->UserId, $message);
        return response()->json(["message"=>"Your location information has been sent"], 200);
    }else{

        return response()->json(["message"=>"Failed to Process Order"], 400);

    }




}

if($req->PaymentMethod == "Shopping Card"){

    $card = ShoppingCard::where("CardNumber",$req->CardNumber)->first();

    if(!$card){
        return response()->json(["message"=>"The Card You Entered Does Not Exist"],400);
    }

    if($card->AccountHolderID != $r->UserId){
        return response()->json(["message"=>"You are not authorised to use this card"],400);
    }

    if($card->Amount < $formattedTotal){
        return response()->json(["message"=>`The amount left on the card is {$card->Amount}, please top-up to continue`],400);
    }




    $p = new Payment();
    $p->OrderId = $req->OrderId;
    $p->Phone = $r->Phone;
    $p->Email = $r->Email;
    $p->AmountPaid = $formattedTotal;
    $p->UserId = $card->AccountHolderID;

    $saver = $p->save();

    $card->Amount = $card->Amount-$formattedTotal;
    $card->save();


    $baggingId = $this->audit->IdGenerator();
    $payId = $this->audit->IdGenerator();

    $m = new MasterRepo();
    $m->MasterId =  $p->OrderId;
    $m->UserId =  $p->UserId;
    $m->OrderId = $p->OrderId;
    $m->BaggingId = $baggingId;
    $m->PaymentId =  $payId;
    $m->save();

    $b = new Bagging();
    $b->MasterId = $p->OrderId;
    $b->UserId = $p->UserId;
    $b->OrderId = $p->OrderId;
    $b->BaggingId =  $baggingId;
    $b->PaymentId =  $payId;
    $b->save();





    if($saver){
        $message = $req->OrderId." order has been placed";
        $this->audit->CustomerAuditor($req->UserId, $message);
        return response()->json(["message"=>"Your location information has been sent"], 200);
    }else{

        return response()->json(["message"=>"Failed to Process Order"], 400);

    }




}


if($req->PaymentMethod == "Credit Sales"){

    $p = new CreditSales();
    $p->OrderId = $req->OrderId;
    $p->ReferenceId = $this->audit->ProformaIdGenerator();
    $p->Phone = $r->Phone;
    $p->Email = $r->Email;
    $p->CreditAmount = $formattedTotal;
    $p->UserId = $r->UserId;
    $p->FullName = $r->userName;
    $p->DigitalAddress = $req->DigitalAddress;
    $p->NationalIDType = $req->NationalIDType;
    $p->NationalID = $req->NationalID;

    $saver = $p->save();

    if($saver){
        $message = $req->OrderId." order has been placed";
        $this->audit->CustomerAuditor($req->UserId, $message);
        return response()->json(["message"=>"Your order has been processed, awaiting approval"], 200);
    }else{

        return response()->json(["message"=>"Failed to Process Order"], 400);

    }



}



}

function getRoadDistance($lat2, $lon2) {
    // OSRM API URL for driving distance between two locations

    $s = DeliveryConfig::first();

    // Convert latitude and longitude from string to float (decimal)
    $lat1 = floatval($s->Latitude);
    $lon1 = floatval($s->Longitude);


    $url = "http://router.project-osrm.org/route/v1/driving/{$lon1},{$lat1};{$lon2},{$lat2}?overview=false";

    // Initialize cURL session
    $ch = curl_init();

    // Set the URL and options for cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // Execute the cURL request and get the response
    $response = curl_exec($ch);

    // Close the cURL session
    curl_close($ch);

    // Decode the JSON response
    $data = json_decode($response, true);

    // Check if the response contains routes
    if (isset($data['routes']) && isset($data['routes'][0]['distance'])) {
        // Extract the distance (in meters)
        $distanceInMeters = $data['routes'][0]['distance'];

        // Convert meters to kilometers
        $distanceInKm = $distanceInMeters / 1000;

        return round($distanceInKm, 2); // Return the distance in kilometers rounded to 2 decimal places
    } else {
        return "Unable to calculate road distance.";
    }
}


function getDistance($lat2, $lon2) {
    $earthRadius = 6371; // Earth's radius in kilometers

    // Fetch the DeliveryConfig (latitude and longitude might be stored as strings)
    $s = DeliveryConfig::first();

    // Convert latitude and longitude from string to float (decimal)
    $lat1 = floatval($s->Latitude);
    $lon1 = floatval($s->Longitude);

    // Convert degrees to radians
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    // Apply the Haversine formula
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    // Calculate the distance
    $distance = $earthRadius * $c;
    $roundDistance = round($distance);
    return $roundDistance;
}


    function DeleteProductInDetailedOrder(Request $req){
        $this->audit->RateLimit($req->ip());
        $s = Order::where("UserId", $req->UserId)->where("ProductId", $req->ProductId)->first();
        if(!$s){
            return response()->json(["message"=>"Invalid Product in your order"],400);
        }

        $b = Bagging::where("UserId", $req->UserId)->where("OrderId", $s->OrderId)->first();
        if($b){
            return response()->json(["message"=>$s->Title." has already been processed"],400);
        }


        $saver = $s->delete();
        if($saver){
            $message = $s->Title." has been deleted in ".$s->OrderId;
            $this->audit->CustomerAuditor($req->UserId, $message);

            return response()->json(["message"=>$s->Title." has been removed successfully"],200);
        }{
            return response()->json(["message"=>"Failed to remove product"],400);
        }






    }





    function Grammer($quantity){
        if($quantity>1){
            return "are";
        }
        else{
            return "is";
        }
    }


    function GetTotalPaymentAmount(Request $req)
    {
        // Rate limit based on IP address
        $this->audit->RateLimit($req->ip());

        // Retrieve the payment details
        $s = Payment::where("UserId", $req->UserId)
            ->where("OrderId", $req->OrderId)
            ->first();

        // If payment details are not available, return an error response
        if (!$s) {
            return response()->json(["message" => "Payment not available"], 400);
        }

        // Log the action
        $message = $req->UserId . " viewed total payment amount for " . $req->OrderId;
        $this->audit->CustomerAuditor($req->UserId, $message);

        // Calculate shipping and tax
        $shipping = 0.08 * $s->AmountPaid;
        $tax = 0.01 * $s->AmountPaid;
        $total = $s->AmountPaid + $shipping + $tax;

        // Format the total amount to 2 decimal places
        $formattedTotal = number_format($total, 2, '.', ',');

        // Return the total payment amount in JSON response
        return response()->json(["message" => $formattedTotal], 200);
    }




public function Payment($UserId, $OrderId)
    {
        $pay = Payment::where("UserId", $UserId)
        ->where("OrderId", $OrderId)
        ->where("Status", "confirmed")
        ->first();

    if ($pay) {
        return response()->json(["message" => "Payment already completed, awaiting delivery"], 400);
    }


        $s = Payment::where("UserId", $UserId)->where("OrderId", $OrderId)->first();
        if(!$s){
            return response()->json(["message"=>"Failed to initiate payment"],400);
        }

        $m = MasterRepo::where("OrderId", $OrderId)->first();
        if (!$m) {
         return response()->json(["message" => "Main Order does not exist"], 400);
         }

         $shipping = 0.08 * $s->AmountPaid;
         $tax = 0.01 * $s->AmountPaid;
         $total = $s->AmountPaid + $shipping + $tax;

        // Ensure the total amount is an integer and in the smallest currency unit (e.g., kobo, pesewas)
        $totalInPesewas = intval($total * 100);

        $tref = Paystack::genTranxRef();


        $s->ReferenceId = $tref;

        $saver = $s->save();
        if ($saver) {
            $m->PaymentId =  $s->ReferenceId;
            $m->save();

            $response = Http::post('https://mainapi.hydottech.com/api/AddPayment', [
                'tref' =>  $tref,
                'ProductId' => "hdtCommerce",
                'Product' => 'Hydot Commerce',
                'Username' => $s->Phone,
                'Amount' => $s->AmountPaid,
                'SuccessApi' => 'https://api.commerce.hydottech.com/api/ConfirmPayment/'.$tref,
                'CallbackURL' => 'https://web.commerce.hydottech.com/orders',
            ]);

            if ($response->successful()) {

                $paystackData = [
                    "amount" => $totalInPesewas, // Amount in pesewas
                    "reference" => $tref,
                    "email" => $s->Email,
                    "currency" => "GHS",
                    "orderID" => $s->OrderId,
                    "phone" => $s->Phone,
                ];



                return Paystack::getAuthorizationUrl($paystackData)->redirectNow();
            } else {
                return response()->json(["message" => "External Payment Api is down"], 400);
            }
        } else {
            return response()->json(["message" => "Failed to initialize payment"], 400);
        }
}



function ConfirmPayment($RefId)
    {
        // Find the payment record in your local database
        $a = Payment::where("ReferenceId", $RefId)->first();
        if (!$a) {
            return response()->json(["message" => "No Payment Found"], 400);
        }


        // Get all transactions from Paystack
        $b = Paystack::getAllTransactions();
        $transactions = $b; // Assuming getAllTransactions returns an array of transactions directly

        $paymentFound = false;

        $c = MasterRepo::where("PaymentId", $RefId)->first();
        if (!$c) {
            return response()->json(["message" => "No Payment Record Found"], 400);
        }

        // Check through the transactions to find the one that matches the reference ID and is successful
        foreach ($transactions as $transaction) {
            if ($transaction['reference'] === $RefId && $transaction['status'] === 'success') {
                $paymentFound = true;
                break;
            }
        }

        if (!$paymentFound) {
            return response()->json(["message" => "Invalid payment reference id"], 400);
        }

        // Additional logic if payment is found and confirmed
        // For example, you might want to update the payment status in your local database
        $a->status = 'confirmed';
       $saver= $a->save();
       if($saver){
        $b = new Bagging();
        $b->MasterId = $c->MasterId;
        $b->UserId = $c->UserId;
        $b->OrderId = $c->OrderId;
        $b->BaggingId = $this->IdGenerator();
        $b->PaymentId = $c->PaymentId;
        $b->save();

        $c->BaggingId = $b->BaggingId;
        $c->save();

        $orderList = Order::where("UserId", $c->UserId)->where("OrderId", $c->OrderId)->get();

        foreach($orderList as $o){
            $product = Product::where("ProductId", $o->ProductId)->first();
            if(!$product){
                return response()->json(["message"=>"Invalid Product in your order"],400);
            }

            $product->Quantity = $product->Quantity - $o->Quantity;
            $product->PurchaseCounter = $product->PurchaseCounter+1;
            $product->save();

            $o->OrderStatus = "awaiting delivery";
            $o->save();

        }




        $message = "Confirmed payment for the order with Id ".$c->OrderId;
        $this->audit->CustomerAuditor($c->UserId, $message);

        return response()->json(["message" => "Payment confirmed successfully"], 200);
       }else{
        return response()->json(["message" => "Payment confirmation failed"], 400);

       }


}

function ViewAllPayment(Request $req){
    $this->audit->RateLimit($req->ip());
   $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Payment");
   if ($rp->getStatusCode() !== 200) {
    return $rp;  // Return the authorization failure response
    }
        $pay = Payment::orderBy("created_at","desc")->get();

        return $pay;


}

function GetCustNotification(Request $req){
    $this->audit->RateLimit($req->ip());

    $n = Notification::where("UserId",$req->UserId)->orderBy("created_at","desc")->get();

    if(!$n){
        return response()->json(["message"=>" "],200);
    }

    return $n;

}














    function IdGenerator(): string {
        $randomID = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        return $randomID;
    }


}
