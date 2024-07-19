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

class Master extends Controller
{

    protected $audit;


    public function __construct(AuditTrialController $auditTrialController)
    {
        $this->audit = $auditTrialController;

    }


function ViewAuditTrail(Request $req){
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Audit_Trail");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
            $pay = AuditTrial::get();

            return $pay;

    }

function ViewCustomerTrail(Request $req){
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Customer_Trail");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
            $pay = CustomerTrail::get();

            return $pay;
    }


function ViewProductAssessment(Request $req){
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Product_Assessment");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
            $pay = ProductAssessment::get();

            return $pay;

    }

    function ViewRateLimitCatcher(Request $req){
        $this->audit->RateLimit($req->ip());
       $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_View_Rate_Limit_Catcher");
       if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }
            $pay = RateLimitCatcher::get();

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
