<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Paystack;
use Illuminate\Support\Facades\Log;

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

    /**
     * Obtain Paystack payment information
     * @return void
     */
    public function makePayment()
    {
        $tref = Paystack::genTranxRef();

        $data = array(
            "amount" => 3*100,
            "reference" => $tref,
            "email" => 'solomon@gmail.com',
            "currency" => "GHS",
            "orderID" => 23456,
            "first_name"=> "Solomon",
            "last_name"=> "Danso",

            "phone"=> "0599626272",

        );

        /*

        Create a payment model
        With tref, and confirmedPayment set to false

        In the redirect page, capture the tref value, pass it through an api.
        if it exist in the database, set confirmedPayment to true
        Then redirect to the order details of the specific order page

        If not, send a strong warning to the user


        */




    return Paystack::getAuthorizationUrl($data)->redirectNow();
    }




    public function getPaymentData(){

        return Paystack::getPaymentData();;
    }

    public function getAllCustomers(){

        return Paystack::getAllTransactions();
    }

    public function getAllTransactions(){

        return Paystack::getAllTransactions();
    }



}

