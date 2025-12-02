<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $newsletter;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct($newsletter, $user = null)
    {
        $this->newsletter = $newsletter;
        $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->newsletter->subject ?? 'Newsletter';
        return $this->subject($subject)
            ->view('newsletter.emails.html')
            ->text('newsletter.emails.text');
    }
}
