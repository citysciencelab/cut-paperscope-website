<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\Backend\FileManager;

	// Laravel
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Aws\Credentials\Credentials;
	use Illuminate\Support\Collection;
	use Aws\S3\S3Client;
	use Aws\S3\Exception\S3Exception;

	// App
	use App\Traits\UploadValidationTrait;
	use App\Http\Controllers\Backend\BackendController;
	use App\Http\Requests\Backend\FileManager\FileUploadS3Request;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class AwsS3Controller extends BackendController {

	use UploadValidationTrait;

	// client for aws access (AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY must be generated via IAM)
	private $client;


	public function __construct() {

		parent::__construct();

		$this->initClient();
	}


	private function initClient(): void {

		$credentials = new Credentials(config('aws.accessKeyId'), config('aws.secretAccessKey'));

		$this->client = new S3Client([
			'version' => 'latest',
			'region'  => config('aws.defaultRegion'),
			'credentials' => $credentials,
		]);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	PREFLIGHT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	* Add the preflight response header so it's possible to use the X-CSRF-TOKEN on Uppy request header.
	*
	* @return string JSON with 204 status no content
	*/

	public function createPreflightHeader(Request $request): JsonResponse {

		// add additional header for authentification in laravel (X-CSRF-TOKEN, Authorization)
		header('Access-Control-Allow-Headers: Authorization, Content-Type, X-CSRF-TOKEN, Origin, X-Requested-With, Accept, Key, X-XSRF-TOKEN');

		return response()->json(['message' => 'No content'], 204);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CREATE UPLOAD
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	* Create a multipart upload.
	*
	* @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#createmultipartupload  S3 Syntax
	* @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateMultipartUpload.html  S3 Syntax
	*/

	public function createMultipartUpload(FileUploadS3Request $request): JsonResponse {

		$validated = $request->validated();

		$filename = $validated->filename;
		$folder = $request->validated('metadata.folder','');

		// additional security check
		if(!$this->validateUpload($filename)) {	return $this->responseError(422); }
		if(!$this->validateFolder($folder)) { return $this->responseError(422); }

		// initiate the upload request
		$response = $this->client->createMultipartUpload([
			'Bucket'        => config('aws.bucket'),
			'Key'           => $folder . $filename,
			'ContentType'   => $validated->type,
			'ContentDisposition' => 'inline',
			'Expires'       => 60
		]);

		// validate aws response
		$mpuKey 		= $response['Key'] ?? null;
		$mpuUploadId 	= $response['UploadId'] ?? null;

		if (!$mpuKey || !$mpuUploadId) {
			return $this->responseError(400, 'Unable to process upload request.');
		}

		return response()->json(['key' => $mpuKey, 'uploadId'  => $mpuUploadId]);
	}


	/**
	* Get the presigned URL for a part.
	*
	* @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#uploadpart  S3 Syntax
	*/

	public function signPartUpload(Request $request, string $uploadId, string $partNumber): JsonResponse {

		// Check key
		$key = $this->encodeURIComponent($request->input('key'));
		if (!is_string($key) || empty($key)) {
			return $this->responseError(400, 's3: the object key must be passed as a query parameter. For example: "?key=abc.jpg"');
		}

		$command = $this->client->getCommand('uploadPart', [
			'Bucket'     => config('aws.bucket'),
			'Key'        => $key,
			'UploadId'   => $uploadId,
			'PartNumber' => (int) $partNumber,
			//'Body'       => '',
		]);

		$presignedUrl = (string) $this->client->createPresignedRequest($command, '+60 minutes')->getUri();

		return response()->json(['url' => $presignedUrl]);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MULTIPART UPLOAD
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	* List the multipart uploaded parts.
	*/

	public function getUploadedParts(Request $request, string $uploadId): JsonResponse {

		// check key
		$key = $request->input('key');
		if (!is_string($key)) {
			return $this->responseError(400, 's3: the object key must be passed as a query parameter. For example: "?key=abc.jpg"');
		}

		$parts = $this->listPartsPage($key, $uploadId, 0);

		return response()->json($parts);
	}


	/**
	* Get the uploaded parts. Retry the part if it's truncated.
	*/

	private function listPartsPage(string $key, string $uploadId, int $partIndex, Collection $parts = null): Collection {

		$parts = $parts ?? collect();

		$results = $this->client->listParts([
			'Bucket'           => config('aws.bucket'),
			'Key'              => $key,
			'UploadId'         => $uploadId,
			'PartNumberMarker' => $partIndex,
		]);

		if ($results['Parts']) {
			$parts = $parts->concat($results['Parts']);

			if ($results['IsTruncated']) {
				$results = $this->listPartsPage($key, $uploadId, $results['NextPartNumberMarker'], $parts);
				$parts = $parts->concat($results);
			}
		}

		return $parts;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPLETE UPLOAD
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	* Completes a multipart upload by assembling previously uploaded parts.
	*
	* @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#completemultipartupload  S3 Syntax
	*/

	public function completeMultipartUpload(Request $request, string $uploadId): JsonResponse {

		$key = $this->encodeURIComponent($request->input('key'));

		$parts = $request->input('parts');

		$result = $this->client->completeMultipartUpload([
			'Bucket'          => config('aws.bucket'),
			'Key'             => $key,
			'UploadId'        => $this->encodeURIComponent($uploadId),
			'MultipartUpload' => ['Parts' => $parts],
		]);


		$location = urldecode($result['Location']);

		return response()->json(['location' => $location]);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	ABORT UPLOAD
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
    * Aborts a multipart upload, deleting the uploaded parts.
    *
    * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#abortmultipartupload   S3 Syntax
	*/

	public function abortMultipartUpload(Request $request, string $uploadId): JsonResponse {

		// check key
		$key = $request->input('key');
		if (!is_string($key)) {
			return response()->json(['status' => 'error', 'message' => 's3: the object key must be passed as a query parameter. For example: "?key=abc.jpg"'], 400);
		}

		// Cancel the multipart upload
		try {
			$this->client->abortMultipartUpload([
				'Bucket'   => config('aws.bucket'),
				'Key'      => $this->encodeURIComponent($key),
				'UploadId' => $this->encodeURIComponent($uploadId),
			]);
		}
		catch (S3Exception $e) {
			return $this->responseError(500, $e->getAwsErrorMessage());
		}

		return response()->json([]);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	 protected function encodeURIComponent($str) {

		$revert = ['%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')', '%2F'=>'/'];
		return strtr(rawurlencode($str), $revert);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
