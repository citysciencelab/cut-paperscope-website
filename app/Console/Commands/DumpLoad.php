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
	use Illuminate\Support\Facades\File;
	use Illuminate\Support\Facades\DB;
	use Aws\S3\S3Client;
	use Aws\Credentials\Credentials;
	use ZipArchive;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class DumpLoad extends Command
{

	protected $signature = 'dump:load';
	protected $description = 'update data with a dump from database and storage';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HANDLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function handle() {

		if(!file_exists('dump.zip')) {
			return $this->info('No dump found.');
		}

		$this->readStorageDump();
		$this->readDatabaseDump();

		// clean up
		Storage::disk('temp')->deleteDirectory('dump');
		$this->call('cache:clear');

		$this->info('Dump loaded.');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    STORAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	private function readStorageDump(): void {

		$this->info('Read storage dump ...');

		// extract data from zip file
		Storage::disk('temp')->makeDirectory('dump/load');
		$zip = new ZipArchive;
		$zip->open('dump.zip');
		$zip->extractTo(storage_path('app/temp/dump/load'));
		$zip->close();

		// copy data to storage
		$storage = config('filesystems.default');
		$source = storage_path('app/temp/dump/load/');
		$directories = File::directories($source);

		if($storage=='public') {

			$destination = storage_path('app/public/');

			foreach($directories as $d) {
				$d = str_replace($source, '', $d);

				// reset destination
				Storage::disk('public')->deleteDirectory($d);
				Storage::disk('public')->makeDirectory($d);
				File::copyDirectory($source.$d, $destination.$d);
			}

			File::delete($destination.'database.sql');
		}
		elseif($storage=='s3') {

			$client = new S3Client([
				'version' => 'latest',
				'region'  => config('aws.defaultRegion'),
				'credentials' => new Credentials(config('aws.accessKeyId'), config('aws.secretAccessKey')),
			]);

			foreach($directories as $d) {
				$d = str_replace($source, '', $d);
				$client->deleteMatchingObjects(config('aws.bucket'), $d);
				$client->uploadDirectory($source.$d, config('aws.bucket'), $d);
			}

			Storage::disk('s3')->delete('database.sql');
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    DATABASE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	private function readDatabaseDump(): void {

		$this->info('Read database dump ...');

		// read sql file
		$sql = file_get_contents(storage_path('app/temp/dump/load/database.sql'));

		// rewrite urls
		$url = '';
		$storage = config('filesystems.default');
		if($storage=='public') {
			$url = env('APP_URL').'storage/';
		}
		elseif($storage=='s3') {
			$url = config('filesystems.disks.s3.url');
		}

		$sql = str_replace('__STORAGE_URL_1__',$url, $sql);
		$url = str_replace('/', '\\\/', $url);
		$sql = str_replace('__STORAGE_URL_2__', $url, $sql);

		// execute sql
		DB::unprepared($sql);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

