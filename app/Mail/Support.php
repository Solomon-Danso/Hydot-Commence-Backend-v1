<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Support extends Mailable
{
    use Queueable, SerializesModels;

    public $username;
    public $message;
    public $attachment;

    /**
     * Create a new message instance.
     *
     * @param string $username
     * @param string $message
     * @param string $attachment File path for the attachment
     */
    public function __construct($username, $message, $attachment)
    {
        $this->username = $username;
        $this->message = $message;
        $this->attachment = $attachment;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->markdown('emails.contact.support')
                     ->with([
                         'username' => $this->username,
                         'messageContent' => $this->message,
                         'attachment' => $this->attachment
                     ]);

        // If attachment exists, attach it to the email
        if ($this->attachment) {
            $mail->attach(storage_path('app/public/' . $this->attachment), [
                'as' => basename($this->attachment),
                'mime' => mime_content_type(storage_path('app/public/' . $this->attachment))
            ]);
        }

        return $mail;
    }
}
