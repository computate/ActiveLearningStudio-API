<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectImportNotification extends Notification
{
    use Queueable;
     /**
     * @var string
     */
    public $userName;

    /**
     * @var string
     */
    public $projectName;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($userName, $projectName)
    {
        $this->userName = $userName;
        $this->projectName = $projectName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail','database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->greeting('Hello '. $this->userName . '!')
                    ->line('Your import of Project ['.$this->projectName.'] has been completed. Please log back into <a href="'.url('/').'">CurrikiStudio</a> and navigate to My Projects to access your shiny new project!')
                    
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
