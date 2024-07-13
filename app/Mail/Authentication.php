<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Authentication extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public function __construct($token)
    {
        $this->token = $token;
    }

    public function build()
    {
        return $this->markdown('emails.contact.login')
                    ->with(['token' => $this->token]);
    }}
