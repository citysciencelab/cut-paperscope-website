<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Notifications;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class SubscriptionCancelledNotificationTest extends TestCase {

	use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MAIL
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_notification_mail() {

		// arrange
		$user = $this->createUser();
		$date = '01.10.2023 13:45';

		// act
		$notification = new \App\Notifications\SubscriptionCancelledNotification($date, 'de');
		$mailMessage = $notification->toMail($user);

		// assert: notification driver
		$this->assertEquals(['mail'], $notification->via($user));

		// assert: mail message
		$this->assertEquals('Subscription gekündigt', $mailMessage->subject);
		$this->assertEquals('Hallo '.$user->name, $mailMessage->greeting);
		$this->assertStringContainsString('01.10.2023', $mailMessage->introLines[0]);
	}

	public function test_dateformat_for_en() {

		// arrange
		$user = $this->createUser();
		$date = '01.10.2023 13:45';

		// act
		$notification = new \App\Notifications\SubscriptionCancelledNotification($date, 'en');
		$mailMessage = $notification->toMail($user);

		// assert: mail message
		$this->assertStringContainsString('01/10/2023', $mailMessage->introLines[0]);
	}




/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

