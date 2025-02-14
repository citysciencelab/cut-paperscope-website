<?php

return [

    'accessKeyId' => env('AWS_ACCESS_KEY_ID', ''),
    'secretAccessKey' => env('AWS_SECRET_ACCESS_KEY', ''),
    'defaultRegion' => env('AWS_DEFAULT_REGION', 'eu-central-1'),
    'bucket' => env('AWS_BUCKET', ''),
    'accountId' => env('AWS_ACCOUNT_ID', ''),
    
	'sqsPrefix' => env('SQS_PREFIX', ''),
    'sqsQueue' => env('SQS_QUEUE', ''),

];
