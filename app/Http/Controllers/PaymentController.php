<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Paystack;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{

    /**
     * Redirect the User to Paystack Payment Page
     * @return Url
     */
    public function redirectToGateway()
    {
        try{
            return Paystack::getAuthorizationUrl()->redirectNow();
        }catch(\Exception $e) {
            return Redirect::back()->withMessage(['msg'=>'The paystack token has expired. Please refresh the page and try again.', 'type'=>'error']);
        }
    }




    public function makePayment()
    {
        // Step 1: Generate a transaction reference
        $tref = Paystack::genTranxRef();

        // Step 2: Prepare data for the POST request
        $postData = [
            "tref" => $tref,
            "ProductId" => 1, // Replace with actual ProductId
            "Product" => "Example Product", // Replace with actual product name
            "Username" => "solomon@gmail.com",
            "Amount" => 300, // Amount in smallest currency unit (e.g., kobo)
            "SuccessApi" => "https://adminpanel.hydottech.com/", // Replace with actual success URL
            "CallbackURL" => "https://adminpanel.hydottech.com/" // Replace with actual callback URL
        ];

        // Step 3: Send the POST request using Http facade
        $response = Http::post('https://mainapi.hydottech.com/api/AddPayment', $postData);

        // Handle the response (optional, based on your requirement)
        if ($response->failed()) {
            return response()->json(["message" => "Error sending POST request."], 400);
        }

        // Step 4: Create a payment model
        // $payment = new Payment();
        // $payment->tref = $tref;
        // $payment->confirmedPayment = false;
        // $payment->save();

        // Step 5: Prepare data for Paystack authorization URL
        $paystackData = [
            "amount" => 300*100,
            "reference" => $tref,
            "email" => 'solomon@gmail.com',
            "currency" => "GHS",
            "orderID" => 23456,
            "first_name" => "Solomon",
            "last_name" => "Danso",
            "phone" => "0599626272",
        ];

        // Redirect to the Paystack authorization URL
        return Paystack::getAuthorizationUrl($paystackData)->redirectNow();
    }




    public function getPaymentData(){

        return Paystack::getPaymentData();;
    }

    public function getAllCustomers(){

        return Paystack::getAllTransactions();
    }

    public function getAllTransactions(){
        $transactions =Paystack::getAllTransactions();
       return $transactions;

    }



}

