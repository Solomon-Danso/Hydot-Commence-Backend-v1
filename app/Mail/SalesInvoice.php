<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\CreditSales;

class SalesInvoice extends Mailable {
use Queueable, SerializesModels;

public $Sales;
public function __construct(array $Sales) {
$this->Sales = $Sales;

}

public function build(){
return $this->markdown('emails.contact.salesinvoice')
            ->with(['c' => $this->Sales]);

}



}