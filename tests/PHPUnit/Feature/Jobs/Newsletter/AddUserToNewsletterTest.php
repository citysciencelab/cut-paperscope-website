<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Jobs\Newsletter;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Support\Facades\Log;
	use Mockery;

	// App
	use App\Jobs\Newsletter\AddUserToNewsletter;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class AddUserToNewsletterTest extends TestCase {

	use RefreshDatabase;


	protected function setUp(): void {

		parent::setUp();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	JOB
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_job_failed() {

		// arrange
		$user = $this->createUser();

		// assert
		Log::shouldReceive('critical')->with('Job failed: Unable to create Brevo contact. Email: '.$user->email)->once();

		// act
		$job = new AddUserToNewsletter($user, false);
		$job->failed(new \Exception('test exception'));
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	ADD USER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_add_user_without_double_opt_in() {

		// arrange
		$user = $this->createUser();

		// arrange: mock brevo contact
		$contactsApi = $this->mockBrevo();
		$contactsApi->shouldReceive('createContact')->andReturn(true);

		// act
		$job = new AddUserToNewsletter($user, false);
		$job->handle();

		// assert
		$this->expectNotToPerformAssertions();
	}


	public function test_add_user_with_double_opt_in() {

		// arrange
		$user = $this->createUser();

		// arrange: mock brevo contact
		$contactsApi = $this->mockBrevo();
		$contactsApi->shouldReceive('createDoiContact')->andReturn(true);

		// act
		$job = new AddUserToNewsletter($user, true);
		$job->handle();

		// assert
		$this->expectNotToPerformAssertions();
	}


	public function test_add_existing_user() {

		// arrange
		$user = $this->createUser();

		// arrange: mock brevo config
		$config = Mockery::mock('overload:Brevo\Client\Configuration');
		$config->shouldReceive('getDefaultConfiguration')->andReturn($config);
		$config->shouldReceive('setApiKey')->andReturn($config);

		// arrange: mock brevo contact
		$contact = Mockery::mock('overload:Brevo\Client\Model\GetExtendedContactDetails');
		$contact->shouldReceive('getId')->andReturn(true);

		// arrange: mock brevo api
		$contactsApi = Mockery::mock('overload:Brevo\Client\Api\ContactsApi');
		$contactsApi->shouldReceive('getContactInfo')->andReturn($contact);
		$contactsApi->shouldReceive('addContactToList')->andReturn(true);

		// act
		$job = new AddUserToNewsletter($user, true);
		$job->handle();

		// assert
		$this->expectNotToPerformAssertions();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	EXCEPTION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_exception_user_with_double_opt_in() {

		// arrange
		$user = $this->createUser();

		// arrange: mock brevo api
		$contactsApi = $this->mockBrevo();

		// arrange: mock brevo eyception
		$contactsApi->shouldReceive('createDoiContact')->andThrow(new \Exception('test exception'));
		Log::shouldReceive('critical')->with('test exception')->once();

		// act
		$job = new AddUserToNewsletter($user, true);
		$job->handle();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MOCKS BREVO
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function mockBrevo() {

		// mock brevo config
		$config = Mockery::mock('overload:Brevo\Client\Configuration');
		$config->shouldReceive('getDefaultConfiguration')->andReturn($config);
		$config->shouldReceive('setApiKey')->andReturn($config);

		// mock brevo api
		$contactsApi = Mockery::mock('overload:Brevo\Client\Api\ContactsApi');
		$contactsApi->shouldReceive('getContactInfo')->andReturn(Mockery::mock('overload:Brevo\Client\Model\GetExtendedContactDetails'));

		return $contactsApi;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

