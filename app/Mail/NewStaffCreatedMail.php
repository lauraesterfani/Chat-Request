<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewStaffCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $tempPassword;

    public function __construct($name, $tempPassword)
    {
        $this->name = $name;
        $this->tempPassword = $tempPassword;
    }

    public function build()
    {
        return $this->subject('Bem-vindo ao sistema')
                    ->view('emails.new_staff_created');
    }
}
