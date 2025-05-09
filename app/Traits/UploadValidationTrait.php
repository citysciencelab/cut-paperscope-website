<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Traits;

	// Laravel
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Str;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	TRAIT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


trait UploadValidationTrait {


	public function validateUpload(string $filename, UploadedFile $file = null) {

		// find invalid name input
		if($this->hasBadExtension($filename)) { return false; }
		if($this->hasBadCharacters($filename)) { return false; }
		if($this->hasBadFilenames($filename)) { return false; }
		if($this->hasFolderTraversal($filename)) { return false; }

		// is valid
		return true;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	EXTENSIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	private function hasBadExtension(string $filename) {

		$badExensions = [
			'.php', '.php5', '.pht', '.phtml',
			'.asp', '.aspx', '.ascx', '.ashx', '.asmx', '.cer',
			'.jsp', '.jspx', '.jsw', '.jsv', '.jspf', '.jtml',
			'.sql', '.cgi', '.pl', '.htaccess', '.htpasswd', '.py',
			'.sh', '.bash', '.csh', '.ksh', '.zsh', '.bashrc', '.bash_profile',
			'.zip', '.rar', '.tar', '.gz', '.gzip', '.7z', '.bz2', '.bzip2', '.xz', '.lzma', '.cab', '.iso', '.dmg',
			'.tar.gz', '.tar.bz2', '.tar.xz', '.tar.lzma', '.tar.lz', '.tar.Z', '.tar.lzo', '.tar.lz4', '.tar.sz', '.tar.zst',
			// prevent double extensions with valid extensions
			'.txt.', '.jpg.', '.jpeg.', '.png.', '.gif.', '.svg.', '.webp.',
		];

		// check for bad extensions
		foreach($badExensions as $badExension) {
			if(stripos($filename, $badExension) !== false) { return true; }
		}

		// is valid
		return false;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CHARACTERS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	private function hasBadCharacters(string $filename) {

		$badCharacters = [
			'/', '\\', ':', '*', '?', '"', '<', '>', '|', ' ', '$', '%',
			// control characters
			"\0", '%00', "\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06",
		];

		// check for bad characters
		foreach($badCharacters as $badCharacter) {
			if(stripos($filename, $badCharacter) !== false) { return true; }
		}

		// is valid
		return false;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FILENAMES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	private function hasBadFilenames(string $filename) {

		$badFilenames = [
			'.DS_Store', '.htaccess', '.htpasswd', 'web.config', '.gitignore', '.env', '.git',
			// file name as folder
			'.', '..', '...',
		];

		// check for bad characters
		foreach($badFilenames as $badFilename) {
			if($filename == $badFilename) { return true; }
		}

		// is valid
		return false;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FOLDER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function validateFolder(string &$folder = null) {

		// empty folder
		if(empty($folder) || $folder=='/') { $folder = '/'; return true; }

		// default checks
		if($this->hasFolderTraversal($folder)) { return false; }

		// is valid folder name
		if($folder != '/' && !preg_match('/^[\w\d\/_-]+$/i',$folder)) { return false; }

		// unsupported folder names
		$folder = $folder != '/' ? Str::replaceEnd('/', '', $folder) : $folder;
		$badFolders = ['.','..','...','undefined','null','.ssh','.git','.trash'];
		foreach($badFolders as $badFolder) {
			if(stripos($folder, $badFolder) !== false) { return false; }
		}

		$folder = Str::finish($folder, '/');

		// is valid
		return true;
	}


	private function hasFolderTraversal(string $filename) {

		$badFilenames = [
			'../', '..\\', './', '.\\', '/.', '\\.',
		];

		// check for bad characters
		foreach($badFilenames as $badFilename) {
			if(stripos($filename, $badFilename) !== false) { return true; }
		}

		// is valid
		return false;
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
