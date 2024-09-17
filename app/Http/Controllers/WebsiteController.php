<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\AuditTrialController;
use App\Models\Website;
use App\Models\AdminUser;


class WebsiteController extends Controller
{

    protected $audit;


    public function __construct(AuditTrialController $auditTrialController)
    {
        $this->audit = $auditTrialController;

    }



function CreateWebsite(Request $req)
{
    $this->audit->RateLimit($req->ip());
    $rp =  $this->audit->RoleAuthenticator($req->AdminId, "Can_Configure_Website");
    if ($rp->getStatusCode() !== 200) {
        return $rp;  // Return the authorization failure response
    }

    $s = AdminUser::where("UserId", $req->AdminId)->first();

    if (!$s) {
        return response()->json(["message" => "Admin not found"], 400);
    }

    $s = Website::firstOrNew();

    if ($req->filled("CompanyName")) {
        $s->CompanyName = $req->CompanyName;
    }

    if ($req->hasFile("Image1")) {
        $s->Image1 = $req->file("Image1")->store("", "public");
    }
    if ($req->hasFile("Image2")) {
        $s->Image2 = $req->file("Image2")->store("", "public");
    }
    if ($req->hasFile("Image3")) {
        $s->Image3 = $req->file("Image3")->store("", "public");
    }

    // Debugging step for Video file upload
    if ($req->hasFile("Video")) {
        $videoFile = $req->file("Video");
        $videoPath = $videoFile->store("", "public");

        if ($videoPath) {
            $s->Video = $videoPath;
            // Logging the successful upload path
            \Log::info('Video uploaded successfully: ' . $videoPath);
        } else {
            \Log::error('Video upload failed');
        }
    } else {
        \Log::error('No video file found in the request');
    }

    if ($req->filled("Whatsapp")) {
        $s->Whatsapp = "https://wa.me/" . $req->Whatsapp;
    }

    if ($req->filled("Instagram")) {
        $s->Instagram = $req->Instagram;
    }

    if ($req->filled("Facebook")) {
        $s->Facebook = $req->Facebook;
    }

    $saver = $s->save();
    if ($saver) {
        $message = $s->Username . " configured the website";
        $this->audit->Auditor($req->AdminId, $message);

        return response()->json(["message" => "Website configured successfully"], 200);
    } else {
        return response()->json(["message" => "Could not configure website"], 400);
    }
}



function GetWebsite(Request $req){
    return Website::get();
}







}
