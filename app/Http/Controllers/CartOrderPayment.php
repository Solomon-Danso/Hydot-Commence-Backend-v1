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

    function ViewAllOrder(Request $req){
        $this->audit->RateLimit($req->ip());

        $s = Order::where("UserId", $req->UserId) ->orderBy("created_at", "desc")->groupBy("OrderId")->get() ->map(function ($group) {
            return $group->first();
        });

        return $s;
    }

    function DetailedOrder(Request $req){
        $this->audit->RateLimit($req->ip());
        $s = Order::where("UserId", $req->UserId)->where("OrderId", $req->OrderId)->get();
        return $s;

    }

    function Payment($UserId, $OrderId){


        $order = Order::where("UserId", $UserId)->where("OrderId", $OrderId)->first();
        if(!$order){
            return response()->json(["message"=>"Order does not exist"],400);
        }

        $r = Customer::where("UserId", $UserId)->first();
        if(!$r){
            return response()->json(["message"=>"Customer does not exist"],400);
        }

        $total = Order::where("UserId", $UserId)->where("OrderId", $OrderId)->sum('Price');



        $tref = Paystack::genTranxRef();

        $s = new Payment();
        $s->OrderId = $order->OrderId;
        $s->ReferenceId = $tref;
        $s->Phone = $r->Phone;
        $s->Email = $r->Email;
        $s->AmountPaid = $total;

        $saver = $s->save();
        if ($saver) {

            $response = Http::post('https://mainapi.hydottech.com/api/AddPayment', [
                'tref' =>  $tref,
                'ProductId' => "hdtCommerce",
                'Product' => 'Hydot Commerce',
                'Username' => $s->Phone,
                'Amount' => $total,
                'SuccessApi' => 'https://www.hydottech.com',//The Code to Execute if payment is successful
                'CallbackURL' => 'http://localhost:3000/',//The redirect url to move the page to
            ]);

            // Handle the response if needed
            if ($response->successful()) {


                $paystackData = [
                    "amount" => $total,
                    "reference" => $tref,
                    "email" => $r->Email,
                    "currency" => "GHS",
                    "orderID" => $order->OrderId,
                    "phone" => $r->Phone,
                ];

                // Redirect to the Paystack authorization URL
                return Paystack::getAuthorizationUrl($paystackData)->redirectNow();


            } else {
                return response()->json(["message"=>"External Payment Api is down"],400);
            }
        }

        else{
            return response()->json(["message"=>"Failed to initialize payment"],400);
        }


    }















    function IdGenerator(): string {
        $randomID = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        return $randomID;
    }


}
