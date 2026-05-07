<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Laravel\Jetstream\TeamInvitation;

class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invitation;
    public $team;

    /**
     * Create a new message instance.
     *
     * @param  TeamInvitation  $invitation
     * @param  mixed  $team
     * @return void
     */
    public function __construct(TeamInvitation $invitation, $team)
    {
        $this->invitation = $invitation;
        $this->team = $team;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Convite para juntar-se à equipe')
            ->view('team-invitation')
            ->with([
                'invitation' => $this->invitation,
                'team' => $this->team,
            ]);
    }
}
