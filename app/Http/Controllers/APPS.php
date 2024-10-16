<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Scheduler;
use App\Models\Chat;
use App\Models\ReplyChat;
use App\Http\Controllers\AuditTrialController;
use Carbon\Carbon;
use App\Mail\Support;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Models\PrepaidMeter;
use Illuminate\Support\Facades\Http;


class APPS extends Controller
{
    protected $audit;

    public function __construct(AuditTrialController $auditTrialController)
    {
        $this->audit = $auditTrialController;

    }

    function CreateSchedular(Request $req) {
        $s = new Scheduler();

        // List of fields to be filled
        $fields = ['Description', 'StartTime', 'EndTime', 'ScheduleId', 'StartTimezone', 'EndTimezone', 'Subject', 'Location', 'IsAllDay', 'RecurrenceRule'];

        foreach ($fields as $field) {
            if ($req->filled($field)) {
                // Convert StartTime and EndTime to Carbon instances if they are present
                if ($field == 'StartTime' || $field == 'EndTime') {
                    // Remove the redundant timezone part
                    $dateString = $req->$field;
                    // Example date string: "Wed Jun 05 2024 20:30:00 GMT+0000 (Greenwich Mean Time)"
                    // We only need: "Wed Jun 05 2024 20:30:00 GMT+0000"
                    $dateString = substr($dateString, 0, strpos($dateString, '(') - 1);
                    $s->$field = Carbon::parse($dateString);
                } else {
                    $s->$field = $req->$field;
                }
            }
        }

        $saver = $s->save();

        if ($saver) {
            $message = $s->Subject . " was scheduled";
            // Assuming $this->audit->Auditor is a valid method for logging the action
            $this->audit->Auditor($req->AdminId, $message);
            return response()->json(["message" => $s->Subject . " has been scheduled"], 200);
        } else {
            return response()->json(["message" => "Failed to Schedule"], 400);
        }
    }

function UpdateSchedular(Request $req){
    $s =  Scheduler::where("id",$req->Id)->first();
    if($s==null){
        return response()->json(["message"=>"Schedule not found"],400);
    }

    $fields = ['Description', 'StartTime', 'EndTime', 'ScheduleId', 'StartTimezone', 'EndTimezone', 'Subject', 'Location', 'IsAllDay', 'RecurrenceRule'];

        foreach ($fields as $field) {
            if ($req->filled($field)) {
                // Convert StartTime and EndTime to Carbon instances if they are present
                if ($field == 'StartTime' || $field == 'EndTime') {
                    // Remove the redundant timezone part
                    $dateString = $req->$field;
                    // Example date string: "Wed Jun 05 2024 20:30:00 GMT+0000 (Greenwich Mean Time)"
                    // We only need: "Wed Jun 05 2024 20:30:00 GMT+0000"
                    $dateString = substr($dateString, 0, strpos($dateString, '(') - 1);
                    $s->$field = Carbon::parse($dateString);
                } else {
                    $s->$field = $req->$field;
                }
            }
        }


    $saver = $s->save();
    if($saver){
    $message = $s->Subject." schedule was updated";
    $this->audit->Auditor($req->AdminId, $message);
    return response()->json(["message"=>$s->Subject." schedule is updated"],200);
    }
    else{
    return response()->json(["message"=>"Failed to Update Schedule"],400);
}


}

function DeleteSchedule(Request $req){
    $s =  Scheduler::where("id",$req->Id)->first();
    if($s==null){
        return response()->json(["message"=>"Schedule not found"],400);
    }

    $saver = $s->delete();


    if($saver){
    $message = $s->Subject." schedule was deleted";
    $this->audit->Auditor($req->AdminId, $message);
    return response()->json(["message"=>$s->Subject." schedule is deleted"],200);
    }
    else{
    return response()->json(["message"=>"Failed to delete Schedule"],400);
    }


}

function GetSchedule(){
    return Scheduler::get();
}


function SendChat(Request $req){
    $c = new Chat();
    $c->EmailId = $this->audit->IdGenerator();

    if($req->Purpose=="Subscriber"){
    $c->Purpose = "Subscriber";
    $c->FullName= $c->EmailId;
    $c->Email = $req->Email;
    $c->Message = $req->Email." subscribed to the newsletter";

    $saver = $c->save();
    if($saver){
        return response()->json(["message"=>"Subscribed successfully"],200);
    }
    else{
        return response()->json(["message"=>"Apologies, we were unable to subscribe you to our newsletter. Please check your internet connection and try again. "],400);
    }



    }
    else{
        $c->Purpose = "Enquiry";
        $c->FullName= $req->FullName;
        $c->Email = $req->Email;
        $c->Message = $req->Message;

        $saver = $c->save();
    if($saver){
        return response()->json(["message"=>"Message sent successfully"],200);
    }
    else{
        return response()->json(["message"=>"Apologies, we were unable to send your message. Please check your internet connection and try again. "],400);
    }

    }

}

function GetChat(){
    $c = Chat::orderBy("created_at","desc")->get();

    return response()->json(["chats"=>$c],200);
}

function GetOneEmail(Request $req){
    $c = Chat::where("EmailId", $req->EmailId)->first();
    if ($c == null) {
        return response()->json(["message" => "Chat not found"]);
    }
    return $c;
}


function ReplyTheChat(Request $req)
{
    $c = Chat::where("EmailId", $req->EmailId)->first();
    if ($c == null) {
        return response()->json(["message" => "Chat not found"]);
    }

    // Create a new instance of ReplyChat
    $r = new ReplyChat();
    $r->ReplyId = $c->EmailId;
    $r->Email = $c->Email;
    $r->CustomerName = $c->FullName;
    $r->CustomerMessage = $c->Message;
    $r->Reply = $req->Reply;

    // Store attachment if exists
    $attachmentName = null;
    if ($req->hasFile("Attachment")) {
        $attachmentName = $req->file("Attachment")->store("", "public");
    }

    $saved = $r->save();
    if ($saved) {
        // Send email if the request is successful
        try {
            Mail::to($r->Email)->send(new Support($r->CustomerName, $r->Reply, $attachmentName));
            $c->isReplied = true;
            $c->save();

            return response()->json(["message" => "Reply sent successfully"]);
        } catch (\Exception $e) {
            // Return the exception message
            return response()->json(['message' => 'Email request failed: ' . $e->getMessage()], 400);
        }
    } else {
        return response()->json(['message' => 'Could not save the reply'], 500);
    }
}

function GetOneReply(Request $req){
    $c = ReplyChat::where("ReplyId", $req->EmailId)
   -> orderBy('created_at','desc')
    ->first();
    if ($c == null) {
        return response()->json(["message" => "Chat not found"]);
    }
    return $c;
}


function AppSetup(Request $req){

    $originHeader = $req->header('Origin');
    $refererHeader = $req->header('Referer');
    $allowedOrigin = "https://adminpanel.hydottech.com";
    //$allowedOrigin = "http://localhost:3000";

    if ($originHeader !== $allowedOrigin && $refererHeader !== $allowedOrigin) {
        $response['status'] = 'Failed';
        $response['message'] = 'Unauthorized request source';
        return response()->json($response, 403);
    }

    $p = PrepaidMeter::firstOrNew();

    $fields = ["Token","productId","packageType",
                "Amount","apiHost","apiKey","softwareID",
                "companyId","email","companyName",
                "companyPhone","apiSecret"
            ];

    foreach($fields as $field){

         if($req->filled($field)){
            $p->$field = $req->$field;
         }

    }

    $saver = $p->save();
    if($saver){
        $response['status'] = 'Success';
        $response['message'] = 'Setup Completed';
        return response()->json($response, 200);
    }
    else{
        $response['status'] = 'Failed';
        $response['message'] = 'Setup Failed';
        return response()->json($response, 400);
    }


}



function TopUp(Request $req){

    $originHeader = $req->header('Origin');
    $refererHeader = $req->header('Referer');
    $allowedOrigin = "https://mainapi.hydottech.com";


    if ($originHeader !== $allowedOrigin && $refererHeader !== $allowedOrigin) {
        $response['status'] = 'Failed';
        $response['message'] = 'Unauthorized request source';
        return response()->json($response, 403);
    }

    $p = PrepaidMeter::first();

    if($p->apiHost!==$req->apiHost){
        $response['message'] = 'Invalid API Host';
        return response()->json($response, 403);
    }

    if($p->companyId!==$req->companyId){
        $response['message'] = 'Invalid Company ID';
        return response()->json($response, 403);
    }

    if($p->productId!==$req->productId){
        $response['message'] = 'Invalid Product ID';
        return response()->json($response, 403);
    }

    if($p->packageType!==$req->packageType){
        $response['message'] = 'Invalid Package Type';
        return response()->json($response, 403);
    }

    if($p->softwareID!==$req->softwareID){
        $response['message'] = 'Invalid Software ID';
        return response()->json($response, 403);
    }

    $p->ExpireDate = $req->expireDate;

    $saver = $p->save();
    if($saver){
        $response['status'] = 'Success';
        $response['message'] = 'Subscription Completed';
        return response()->json($response, 200);
    }
    else{
        $response['status'] = 'Failed';
        $response['message'] = 'Subscription Failed';
        return response()->json($response, 400);
    }


}



public function SubscriptionPayment(Request $req)
{
    $s = PrepaidMeter::first();

    if ($s == null) {
        return response()->json([
            'message' => 'Setup your account first'
        ], 400);
    }

    // Define the external API URL with the parameters
    $externalApiUrl = "https://mainapi.hydottech.com/api/HCSSchedulePayment/{$s->softwareID}/{$req->amount}";

    try {
        // Send a GET request to the external API
        $apiResponse = Http::get($externalApiUrl);

        // Return the exact response from the external API
        return response($apiResponse->body(), $apiResponse->status());

    } catch (\Exception $e) {
        // Handle any exceptions that occur during the request
        return response()->json([
            'status' => 'Failed',
            'message' => 'An error occurred: ' . $e->getMessage()
        ], 500);
    }
}


public function SubscriptionDetails()
{
    $s = PrepaidMeter::first();

    if ($s == null) {
        return response()->json([
            'message' => 'Setup your account first'
        ], 400);
    }

    // Parse the expiry date using Carbon and ignore the time component
    $expiryDate = Carbon::parse($s->ExpireDate)->startOfDay();
    $currentDate = Carbon::now()->startOfDay(); // Set the current date to the start of the day

    // Calculate the days left as a whole number
    $daysLeft = $currentDate->diffInDays($expiryDate, false); // Ensures the result is a whole number

    // Format the expiry date to "20th October, 2024"
    $formattedExpiryDate = $expiryDate->format('jS F, Y');

    // Check the data type of ExpiryDate
    $expiryDateType = is_string($s->ExpireDate) ? 'string' : 'datetime';

    $data = [
        "ExpiryDate" => $formattedExpiryDate,
        "DaysLeft" => $daysLeft,
    ];

    return $data;
}





}
