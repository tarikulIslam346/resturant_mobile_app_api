<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class welcome extends Mailable
{
    use Queueable, SerializesModels;
    public $msg ;
    public $phone;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($msg,$phone )
    {
        //
        $this->msg = $msg;
        $this->phone = $phone;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.welcome');
    }
}
