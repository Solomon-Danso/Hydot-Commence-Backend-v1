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



        $checker = Cart::where("ProductId", $req->ProductId)->where("UserId", $req->UserId)->first();
        if($checker){

            $TotalQuantity = $checker->Quantity + $req->Quantity;

            if($TotalQuantity>$p->Quantity){
                return response()->json(["message" => "Your requested quantity exceeds the available stock."], 400);
            }

            $checker->Quantity =  $TotalQuantity;
            $checker->Size = $req->Size;
            $saver = $checker->save();
            if($saver){
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
            $s->Price = $p->Price;

            if ($req->Quantity > $p->Quantity) {
                return response()->json(["message" => "Your requested quantity exceeds the available stock."], 400);
            }

            $s->Quantity = $req->Quantity;
            $s->Size = $req->Size;
            $s->UserId = $c->UserId;

            $saver = $s->save();
            if($saver){
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


        $s->Quantity = $req->Quantity;
        $s->Size = $req->Size;




        $saver = $s->save();
        if($saver){
            return response()->json(["message"=>"Success"],200);
        }else{
            return response()->json(["message"=>"Failed"],400);

        }


    }

    function ViewAllCart(Request $req){
        $this->audit->RateLimit($req->ip());

        $s = Cart::where("UserId", $req->UserId)->get();

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
            $s->Size = $item->Size;
            $s->UserId = $item->UserId;
            $s->Country = $req->Country;
            $s->Region = $req->Region;
            $s->City = $req->City;
            $s->DigitalAddress = $req->DigitalAddress;
            $s->DetailedAddress = $req->DetailedAddress;
            $s->OrderStatus= "awaiting shipment";
            $s->save();

            $c = Cart::where("CartId", $item->CartId)->first();
            $c->delete();

        }

        $m = new MasterRepo();
        $m->MasterId =  $OrderId;
        $m->UserId =  $req->UserId;
        $m->OrderId = $OrderId;
        $m->save();




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

        return $orders;
    }

    function DetailedOrder(Request $req){
        $this->audit->RateLimit($req->ip());
        $s = Order::where("UserId", $req->UserId)->where("OrderId", $req->OrderId)->get();
        return $s;

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

        $order = Order::where("UserId", $UserId)->where("OrderId", $OrderId)->first();
        if (!$order) {
            return response()->json(["message" => "Order does not exist"], 400);
        }

        $r = Customer::where("UserId", $UserId)->first();
        if (!$r) {
            return response()->json(["message" => "Customer does not exist"], 400);
        }

        $m = MasterRepo::where("OrderId", $OrderId)->first();
        if (!$m) {
            return response()->json(["message" => "Main Order does not exist"], 400);
        }

        $total = Order::where("UserId", $UserId)->where("OrderId", $OrderId)->sum('Price');

        // Ensure the total amount is an integer and in the smallest currency unit (e.g., kobo, pesewas)
        $totalInPesewas = intval($total * 100);

        $tref = Paystack::genTranxRef();

        $s = new Payment();
        $s->OrderId = $order->OrderId;
        $s->ReferenceId = $tref;
        $s->Phone = $r->Phone;
        $s->Email = $r->Email;
        $s->AmountPaid = $total;
        $s->UserId = $UserId;

        $saver = $s->save();
        if ($saver) {
            $m->PaymentId =  $s->ReferenceId;
            $m->save();

            $response = Http::post('https://mainapi.hydottech.com/api/AddPayment', [
                'tref' =>  $tref,
                'ProductId' => "hdtCommerce",
                'Product' => 'Hydot Commerce',
                'Username' => $s->Phone,
                'Amount' => $total,
                //'SuccessApi' => 'http://127.0.0.1:8000/api/ConfirmPayment/'.$tref,
                'SuccessApi' => 'https://hydottech.com',
                'CallbackURL' => 'http://localhost:3000/',
            ]);

            if ($response->successful()) {

                $paystackData = [
                    "amount" => $totalInPesewas, // Amount in pesewas
                    "reference" => $tref,
                    "email" => $r->Email,
                    "currency" => "GHS",
                    "orderID" => $order->OrderId,
                    "phone" => $r->Phone,
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
        $s->BaggingId = $this->IdGenerator();
        $b->PaymentId = $c->PaymentId;
        $b->save();

        $c->BaggingId = $b->BaggingId;
        $c->save();

        return response()->json(["message" => "Payment confirmed successfully"], 200);
       }else{
        return response()->json(["message" => "Payment confirmation failed"], 400);

       }


    }














    function IdGenerator(): string {
        $randomID = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        return $randomID;
    }


}
