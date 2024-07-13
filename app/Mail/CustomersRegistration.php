<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Customers;

class CustomersRegistration extends Mailable {
use Queueable, SerializesModels;

public $AdminUser;

public function __construct(Customers $AdminUser) {
$this->AdminUser = $AdminUser;

}

public function build(){
return $this->markdown('emails.contact.employee')
            ->with(['contact' => $this->AdminUser]);

}



}
