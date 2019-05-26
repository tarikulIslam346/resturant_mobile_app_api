<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class reset_password extends Mailable
{
    use Queueable, SerializesModels;
    public $verification_code ;
    public $email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email,$verification_code )
    {
        //
        $this->email = $email;
        $this->verification_code = $verification_code;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.reset')->subject('Resest Pasword');
    }
}
