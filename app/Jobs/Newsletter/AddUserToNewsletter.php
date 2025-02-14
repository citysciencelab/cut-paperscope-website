<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Jobs\Newsletter;

	// Laravel
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Foundation\Bus\Dispatchable;
	use Illuminate\Queue\InteractsWithQueue;
	use Illuminate\Queue\SerializesModels;
	use Illuminate\Support\Facades\Log;
	use Brevo\Client\Api\ContactsApi;
	use GuzzleHttp\Client as GuzzleClient;
	use Throwable;

	// App
	use App\Models\Auth\User;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class AddUserToNewsletter implements ShouldQueue
{
	// Traits
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected User $user;

	// Brevo config
	protected $doubleOptin = true;
	protected $brevoListIds = [5];
	protected $brevoDoiTemplateIdId = 1;


	public function __construct(User $user, bool $doubleOptin=true) {

		$this->user = $user;
		$this->doubleOptin 	= $doubleOptin;
	}


	public function failed(Throwable $exception): void {

		Log::critical("Job failed: Unable to create Brevo contact. Email: " . ($this->user->email ?? 'Undefined'));
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HANDLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function handle(): void {

		// init Brevo api
		$config = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', config('mail.brevo.key'));
		$config = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('partner-key', config('mail.brevo.key'));
		$apiInstance = new \Brevo\Client\Api\ContactsApi( new GuzzleClient(), $config);

		// contact already exists?
		try {
			$result = $apiInstance->getContactInfo($this->user->email);
			if($result->getId()) {

				// add existing contact to list
				$addContact = new \Brevo\Client\Model\AddContactToList(['emails' => [$this->user->email]]);
				$apiInstance->addContactToList($this->brevoListIds[0], $addContact);

				return;
			}
		}
		catch(\Exception $e) {}

		// add user to Brevo
		if($this->doubleOptin) {
			$this->addUserWithDoubleOptIn($apiInstance);
		}
		else {
			$this->addUser($apiInstance);
		}
	}


	protected function addUserWithDoubleOptIn(ContactsApi &$apiInstance) {

		// contact model
		$contact = new \Brevo\Client\Model\CreateDoiContact([
			'email' => $this->user->email,
			'attributes' => (object) [
				'FIRSTNAME'=> $this->user->name,
				'LASTNAME' => $this->user->surname,
				'LANGUAGE' => $this->user->lang,
			],
			'includeListIds' => $this->brevoListIds,
			'templateId' => $this->brevoDoiTemplateIdId,
			'redirectionUrl' => config('app.url') . 'callback/newsletter-doi',
		]);

		// api request
		try {
			$apiInstance->createDoiContact($contact);
		}
		catch (\Exception $e) {
			Log::critical($e->getMessage());
		}
	}


	protected function addUser(ContactsApi &$apiInstance) {

		// contact model
		$contact = new \Brevo\Client\Model\CreateContact([
			'email' => $this->user->email,
			'updateEnabled' => true,
			'attributes' => (object) [
				'FIRSTNAME'=> $this->user->name,
				'LASTNAME' => $this->user->surname,
				'LANGUAGE' => $this->user->lang,
			],
			'listIds' => $this->brevoListIds,
		]);

		// api request
		$apiInstance->createContact($contact);
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */



}
