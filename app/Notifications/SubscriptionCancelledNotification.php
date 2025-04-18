<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Notifications;

	// Laravel
	use Illuminate\Bus\Queueable;
	use Illuminate\Support\Carbon;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Notifications\Messages\MailMessage;
	use Illuminate\Notifications\Notification;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class SubscriptionCancelledNotification extends Notification implements ShouldQueue
{
	// Traits
	use Queueable;

	public $date;
	public $locale;

	public function __construct($date, $locale) {

		$this->date = $date;
		$this->locale = $locale;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	NOTIFICATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function via($notifiable) {

		return ['mail'];
	}


	public function toMail($notifiable) {

		app()->setLocale($this->locale);

		$dateString = Carbon::parse($this->date)->format($this->locale == 'en' ? 'd/m/Y' : 'd.m.Y');

		return ( new MailMessage )
			->greeting(__('notification.greeting') . ' ' . $notifiable->name)
			->subject(__('notification.cancel-subscription-subject'))
			->line(__('notification.cancel-subscription-text', ['date' => $dateString]))
			->salutation(__('notification.salutation'));
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
