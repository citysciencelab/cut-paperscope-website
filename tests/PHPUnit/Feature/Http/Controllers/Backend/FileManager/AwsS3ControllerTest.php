<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Http\Controllers\Backend\FileManager;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Aws\S3\Exception\S3Exception;
	use Mockery;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class AwsS3ControllerTest extends TestCase {

	use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    PREFLIGHT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_create_preflight_header() {

		// arrange
		$this->mockS3ClientUpload();
		$this->loginAsAdmin();

		// act
		$response = $this->options('api/backend/s3/multipart',[], $this->getBackendHeaders());

		// assert
		$response->assertStatus(204);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CREATE UPLOAD
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_create_upload() {

		$this->seed();

		// arrange
		$this->mockS3ClientUpload();
		$this->loginAsAdmin();

		// act
		$response = $this->post('api/backend/s3/multipart',[
			'filename' => 'test.jpg',
			'type' => 'image/jpeg',
			'metadata' =>  [
				'folder' => 'test-folder/',
				'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716=',
			]
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(200);
		$response->assertJsonStructure(['key','uploadId']);
	}


	public function test_create_upload_with_invalid_data() {

		$this->seed();

		// arrange
		$this->mockS3ClientUpload(['wrong' => 'data']);
		$this->loginAsAdmin();

		// act
		$response = $this->post('api/backend/s3/multipart',[
			'filename' => 'test.jpg',
			'type' => 'image/jpeg',
			'metadata' =>  [
				'folder' => 'test-folder/',
				'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716=',
			]
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(400);
	}


	public function test_sign_part_upload() {

		$this->seed();

		// arrange
		$this->mockS3ClientSignPart();
		$this->loginAsAdmin();

		// act
		$response = $this->get('api/backend/s3/multipart/upload-id-123/1?key=test.jpg', $this->getBackendHeaders());

		// assert
		$response->assertStatus(200);
	}


	public function test_sign_part_upload_with_invalid_key() {

		$this->seed();

		// arrange
		$this->mockS3ClientSignPart();
		$this->loginAsAdmin();

		// act
		$response = $this->get('api/backend/s3/multipart/upload-id-123/1', $this->getBackendHeaders());

		// assert
		$response->assertStatus(400);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MULTIPART UPLOAD
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_get_uploaded_parts() {

		// arrange
		$this->mockS3ClientParts();
		$this->loginAsAdmin();

		// act
		$response = $this->get('api/backend/s3/multipart/upload-id-123?key=test.jpg', $this->getBackendHeaders());

		// assert
		$response->assertStatus(200);
	}


	public function test_get_uploaded_parts_with_truncated_parts() {

		// arrange
		$this->mockS3ClientParts(true);
		$this->loginAsAdmin();

		// act
		$response = $this->get('api/backend/s3/multipart/upload-id-123?key=test.jpg', $this->getBackendHeaders());

		// assert
		$response->assertStatus(200);
	}


	public function test_get_uploaded_parts_with_invalid_key() {

		// arrange
		$this->mockS3ClientParts();
		$this->loginAsAdmin();

		// act
		$response = $this->get('api/backend/s3/multipart/upload-id-123', $this->getBackendHeaders());

		// assert
		$response->assertStatus(400);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    COMPLETE UPLOAD
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_complete_multipart_upload() {

		// arrange
		$this->mockS3ClientMultipart();
		$this->loginAsAdmin();

		// act
		$response = $this->post('api/backend/s3/multipart/upload-id-123/complete',[
			'parts' => [
				[
					'partNumber' => 1,
					'ETag' => 'etag-123',
				],
			],
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(200);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    ABORT UPLOAD
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_abort_multipart_upload() {

		// arrange
		$this->loginAsAdmin();
		$this->mockS3ClientAbort();

		// act
		$response = $this->delete('api/backend/s3/multipart/upload-id-123?key=test.jpg',[], $this->getBackendHeaders());

		// assert
		$response->assertStatus(200);
	}


	public function test_abort_multipart_upload_exception() {

		// arrange
		$this->loginAsAdmin();

		// arrange: mock s3 client
		Mockery::mock('overload:\Aws\Credentials\Credentials');
		$client = Mockery::mock('overload:\Aws\S3\S3Client');
		$client->shouldReceive('abortMultipartUpload')->andThrow(new S3Exception('test', new \Aws\Command('test')));

		// act
		$response = $this->delete('api/backend/s3/multipart/upload-id-123?key=test.jpg',[], $this->getBackendHeaders());

		// assert
		$response->assertStatus(500);
	}


	public function	 test_abort_multipart_upload_with_invalid_key() {

		// arrange
		$this->loginAsAdmin();
		$this->mockS3ClientAbort();

		// act
		$response = $this->delete('api/backend/s3/multipart/upload-id-123',[], $this->getBackendHeaders());

		// assert
		$response->assertStatus(400);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MOCKS AWS S3
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function mockS3ClientUpload(array $response = null) {

		Mockery::mock('overload:\Aws\Credentials\Credentials');

		$client = Mockery::mock('overload:\Aws\S3\S3Client');
		$client->shouldReceive('createMultipartUpload')->andReturn($response ?? [
			'UploadId' => 'upload-id-123',
			'Key' => 'key-123',
			'Bucket' => 'bucket-123',
			'Endpoint' => 'endpoint-123',
		]);
	}


	protected function mockS3ClientSignPart() {

		Mockery::mock('overload:\Aws\Credentials\Credentials');

		$client = Mockery::mock('overload:\Aws\S3\S3Client');
		$client->shouldReceive('getCommand')->andReturn(true);
		$client->shouldReceive('getUri')->andReturn('https://test.com');
		$client->shouldReceive('createPresignedRequest')->andReturn($client);
	}


	protected function mockS3ClientParts($isTruncated = false) {

		Mockery::mock('overload:\Aws\Credentials\Credentials');

		$client = Mockery::mock('overload:\Aws\S3\S3Client');
		$client->shouldReceive('listParts')->andReturnUsing(function($params) use ($isTruncated) {

			return [
				'IsTruncated' => $params['PartNumberMarker'] != 0 ? false : $isTruncated,
				'NextPartNumberMarker' => 3,
				'Parts' => [
					[
						'PartNumber' => 1,
						'ETag' => 'etag-123',
						'LastModified' => '2021-01-01',
						'Size' => 123,
					],
					[
						'PartNumber' => 2,
						'ETag' => 'etag-456',
						'LastModified' => '2021-01-01',
						'Size' => 456,
					],
					[
						'PartNumber' => 3,
						'ETag' => 'etag-789',
						'LastModified' => '2021-01-01',
						'Size' => 789,
					],
				],
			];
		});
	}


	protected function mockS3ClientMultipart() {

		Mockery::mock('overload:\Aws\Credentials\Credentials');

		$client = Mockery::mock('overload:\Aws\S3\S3Client');
		$client->shouldReceive('completeMultipartUpload')->andReturn([
			'Location' => 'https://test.com',
		]);
	}


	protected function mockS3ClientAbort() {

		Mockery::mock('overload:\Aws\Credentials\Credentials');

		$client = Mockery::mock('overload:\Aws\S3\S3Client');
		$client->shouldReceive('abortMultipartUpload')->andReturn(true);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

