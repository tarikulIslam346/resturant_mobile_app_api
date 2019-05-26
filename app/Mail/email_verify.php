<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class email_verify extends Mailable
{
    use Queueable, SerializesModels;
    public $verification_code ;
    public $first_name;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($first_name,$verification_code )
    {
        //
        $this->first_name = $first_name;
        $this->verification_code = $verification_code;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.verify')->subject('Email Verification');
    }
}
