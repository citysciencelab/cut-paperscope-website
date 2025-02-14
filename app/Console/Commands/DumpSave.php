<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Console\Commands;

	// Laravel
	use Illuminate\Console\Command;
	use Illuminate\Support\Facades\Storage;
	use Spatie\DbDumper\Databases\MySql as Dumper;
	use Aws\S3\S3Client;
	use Aws\Credentials\Credentials;
	use ZipArchive;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class DumpSave extends Command
{

	protected $signature = 'dump:save';
	protected $description = 'dump database and storage content to a file.';

	protected $dumpFile = 'storage/app/temp/dump/save/database.sql';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HANDLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function handle() {

		$this->createDatabaseDump();
		$this->rewriteDatabaseUrls();
		$this->createStorageDump();

		// clean up
		Storage::disk('temp')->deleteDirectory('dump');

		$this->info('Dump saved.');

	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    DATABASE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	private function createDatabaseDump(): void {

		$this->info('Create database dump ...');
		$mysqldump = $this->findMysqldumpBinary();

		Storage::disk('temp')->makeDirectory('dump/save');

		// export database
		Dumper::create()
			->setDumpBinaryPath($mysqldump)
			->setDbName(env('DB_DATABASE'))
			->setUserName(env('DB_USERNAME'))
			->setPassword(env('DB_PASSWORD'))
			->excludeTables([
				'failed_jobs', 'jobs',
				'model_has_permissions', 'model_has_roles',
				'password_reset_tokens', 'personal_access_tokens',
				'product_user',
				'subscriptions', 'subscription_items',
				'users',
			])
			->dumpToFile($this->dumpFile);
	}


	private function rewriteDatabaseUrls() {

		$this->info('Rewrite database urls ...');

		$storage = config('filesystems.default');
		$url = '';

		// set correct storage url
		if($storage == 'public') {
			$url = env('APP_URL').'storage/';
		}
		elseif($storage == 's3') {
			$url = config('filesystems.disks.s3.url');
		}

		// replace urls
		$database = file_get_contents($this->dumpFile);
		$database = str_replace($url, '__STORAGE_URL_1__', $database);
		$url = str_replace('/', '\\\/', $url);
		$database = str_replace($url, '__STORAGE_URL_2__', $database);

		file_put_contents($this->dumpFile, $database);
	}


	private function findMysqldumpBinary(): string {

		$paths = [
			'/usr/bin/',
			'/Applications/MAMP/Library/bin/',
		];

		foreach ($paths as $p) { if(file_exists($p.'mysqldump')) { return $p; } }

		return '/usr/local/bin/';
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    STORAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	private function createStorageDump(): void {

		$this->info('Create storage dump ...');

		$storage = config('filesystems.default');
		$storageBase = storage_path('app/public/');
		$skipFolders = ['users', 'testing', '.DS_Store', '.gitingore'];

		// create zip file
		$zip = new ZipArchive;
		$zip->open('dump.zip',ZipArchive::CREATE | ZipArchive::OVERWRITE);

		// add database to zip
		$zip->addFile($this->dumpFile, 'database.sql');

		// download s3 storage files
		if($storage == 's3') {

			$client = new S3Client([
				'version' => 'latest',
				'region'  => config('aws.defaultRegion'),
				'credentials' => new Credentials(config('aws.accessKeyId'), config('aws.secretAccessKey')),
			]);

			$client->downloadBucket('storage/app/temp/dump/save/', config('filesystems.disks.s3.bucket'), '/');
			$storageBase = storage_path('app/temp/dump/save/');
		}

		// add files recursively
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($storageBase,
			\RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($files as $file) {

			$filePath = $file->getRealPath();
			$relativePath = substr($filePath, strlen($storageBase));

			// skip folders
			$skip = false;
			foreach ($skipFolders as $folder) {
				if (strpos($relativePath, $folder) === 0) { $skip = true; break; }
			}
			if ($skip) { continue; }

			// add file or folder
			if ($file->isDir()) { $zip->addEmptyDir($relativePath); }
			elseif ($file->isFile()) { $zip->addFile($filePath, $relativePath); }
		}

		$zip->close();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

