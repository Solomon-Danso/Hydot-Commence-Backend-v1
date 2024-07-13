<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\AdminUser;

class Registration extends Mailable {
use Queueable, SerializesModels;

public $AdminUser;
public $rawPassword;
public function __construct(AdminUser $AdminUser, $rawPassword) {
$this->AdminUser = $AdminUser;
$this->rawPassword = $rawPassword;
}

public function build(){
return $this->markdown('emails.contact.message') 
            ->with(['contact' => $this->AdminUser,
             'rawPassword' => $this->rawPassword]);
         
}



}
