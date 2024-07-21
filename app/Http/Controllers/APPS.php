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





}
