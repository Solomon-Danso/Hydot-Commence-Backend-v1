<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditTrial;
use App\Models\Visitors;
use App\Models\CustomerTrail;
use App\Models\ProductAssessment;
use App\Models\RateLimitCatcher;
use App\Http\Controllers\AuditTrialController;
use App\Models\MasterRepo;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Bagging;
use App\Models\Checker;



class Master extends Controller
{

    protected $audit;


    public function __construct(AuditTrialController $auditTrialController)
    {
        $this->audit = $auditTrialController;

    }


    function ViewMUsers(Request $req){
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Master");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
    $pay = Customer::where("UserId",$req->UserId)->first();

    return $pay;

    }

    function ViewMOrder(Request $req){
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Master");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
    $pay = Order::where("OrderId",$req->OrderId)->first();

    return $pay;

    }

    function ViewMBagging(Request $req){
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Master");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
    $pay = Bagging::where("BaggingId",$req->BaggingId)->first();

    return $pay;

    }

    function ViewMChecker(Request $req){
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Master");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
    $pay = Checker::where("CheckerId",$req->CheckerId)->first();

    return $pay;

    }

    function ViewMDelivery(Request $req){
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Master");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
    $pay = Delivery::where("DeliveryId",$req->DeliveryId)->first();

    return $pay;

    }

    function ViewMPayment(Request $req){
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Master");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
    $pay = Payment::where("PaymentId",$req->PaymentId)->first();

    return $pay;

    }















function ViewAuditTrail(Request $req){
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Audit_Trail");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
            $pay = AuditTrial::orderBy("created_at","desc")->get();

            return $pay;

    }

function ViewCustomerTrail(Request $req){
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Customer_Trail");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
            $pay = CustomerTrail::orderBy("created_at","desc")->get();

            return $pay;
    }


function ViewProductAssessment(Request $req){
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Product_Assessment");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
            $pay = ProductAssessment::orderBy("created_at","desc")->get();

            return $pay;

    }

    function ViewRateLimitCatcher(Request $req){
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Rate_Limit_Catcher");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
            $pay = RateLimitCatcher::orderBy("created_at","desc")->get();

            return $pay;

    }

    function ViewMasterRepo(Request $req){
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Master_Repo");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
            $pay = MasterRepo::get();

            return $pay;

    }







}
