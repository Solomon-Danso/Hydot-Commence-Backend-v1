<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Sales;

class PaymentInvoice extends Mailable {
use Queueable, SerializesModels;

public $Sales;
public function __construct(Sales $Sales) {
$this->Sales = $Sales;

}

public function build(){
return $this->markdown('emails.contact.payment') 
            ->with(['contact' => $this->Sales]);
         
}



}
