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
    public function handleGatewayCallback()
    {
        //$paymentDetails = Paystack::getPaymentData();

       // dd($paymentDetails);
        // Now you have the payment details,
        // you can store the authorization_code in your db to allow for recurrent subscriptions
        // you can then redirect or do whatever you want

        $data = array(
            "amount" => 700 * 100,
            "reference" => 'zbxgfchbfcvghmj',
            "email" => 'user@mail.com',
            "currency" => "GHS",
            "orderID" => 23456,
            "first_name"=> "Solomon",
            "last_name"=> "Danso",

            "phone"=> "0599626272",

        );

        $worked= false;

        try{

            Paystack::getAuthorizationUrl($data)->redirectNow();
            $worked = true;
        }
        catch(Exception $e){
            $worked = false;
        }



    return $worked ;
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

