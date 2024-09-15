<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\AuditTrialController;
use App\Model\PaymentConfiguration;

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























}

