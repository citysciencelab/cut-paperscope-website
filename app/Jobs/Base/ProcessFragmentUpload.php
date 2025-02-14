<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Jobs\Base;

	// App
	use App\Jobs\Base\BaseJob;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ProcessFragmentUpload extends BaseJob
{



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HANDLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function handle(): void {

		parent::handle();

		foreach($this->langKeys as $lang) {

			$content = json_decode($this->target['content'.$lang],true);
			$this->handleTemplate($content,str_replace('_','',$lang));
			$this->target['content'.$lang] = $this->getEncodedJson($content);
		}

		$this->saveModel();
	}


	protected function getEncodedJson($json) {

		if(empty($json) || $json=='[]' || (is_array($json) && count($json)==0)) {
			return '{}';
		}

		return json_encode($json);
	}


	protected function handleTemplate(&$content, $lang = null) {

		switch($this->target->template) {
			case 'text':			$this->handleText($content,$lang); break;
			case 'text-image':		$this->handleTextImage($content,$lang); break;
			case 'image': 			$this->handleTextImage($content,$lang); break;
			case 'slider-image': 	$this->handleSliderImage($content,$lang); break;
			case 'video': 			$this->handleVideo($content,$lang); break;
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	TEXT / IMAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function handleText(&$content, $lang = null) {

		$property = 'copy' . (!empty($lang) ? '_'.$lang : '');
		if(empty($content[$property])) { return; }

		// find image urls in copy
		$re = '/<img src=\\"(.+?)\\"/m';
		preg_match_all($re, $content[$property], $matches, PREG_SET_ORDER, 0);

		// iterate all images
		foreach($matches as $match) {

			// get image
			$image = $match[1];
			if(!str_contains($image,'_upload/')) { continue; }

			// image properties
			$extension = $this->getFileExtension($image);
			$filename = $this->getFileName($image);
			$filename = str_replace('.'.$extension,'',$filename);
			$width = getimagesize($image)[0];

			// image processing
			$this->convertImage($extension, $width*0.25, null, $image, $filename.'-mobile-lr', 20, 0);
			$this->convertImage($extension, $width*0.5, null, $image, $filename.'-desktop-lr', 40, 0);
			$this->convertImage($extension, $width*0.5, null, $image, $filename.'-mobile-hr', 70);
			$imgUrl = $this->convertImage($extension, $width, null, $image, $filename.'-desktop-hr', 70);

			// update copy
			if($imgUrl) {
				$content[$property] = str_replace($image, $imgUrl, $content[$property]);
				$this->deleteUploadFile($image);
			}
		}
	}


	protected function handleTextImage(&$content, $lang=null, $imageName='image') {

		$this->handleText($content,$lang);

		$property = 'image' . (!empty($lang) ? '_'.$lang : '');
		if(empty($content[$property])) { return; }

		$image = $content[$property];
		$extension = $this->getFileExtension($image);

		// no image processing if gif
		if($extension == 'gif') {

			$imgUrl = $this->moveFile($image, $this->storageUrl.$this->target->storageFolder.'image.gif');
			if($imgUrl) { $content[$property] = $imgUrl; }
		}
		else if(array_search($extension,['jpg','jpeg','png']) !== false) {

			// image properties
			$imgSizeHR = $this->target->template == $property ? 1600 : 1000;
			$imgSizeLR = 500;
			$extension = $extension == 'png'?'png':'jpg';

			// image processing
			$imageName .= '-' . (!empty($lang) ? $lang.'-' : '');
			$this->convertImage($extension, $imgSizeLR*0.5, null, $image, $imageName.'mobile-lr', 20, 0);
			$this->convertImage($extension, $imgSizeLR, null, $image, $imageName.'desktop-lr', 40, 0);
			$this->convertImage($extension, $imgSizeHR*0.5, null, $image, $imageName.'mobile-hr', 70);
			$imgUrl = $this->convertImage($extension, $imgSizeHR, null, $image, $imageName.'desktop-hr', 70);

			// update content property
			if($imgUrl) {
				$content[$property] = $imgUrl;
				$this->deleteUploadFile($image);
			}
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	IMAGE SLIDER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function handleSliderImage(&$content, $lang = null) {

		if(empty($content['items']) || !is_array($content['items'])) { return; }

		foreach($content['items'] as &$item) {
			$this->handleTextImage($item, $lang, 'image-'.$item['id']);
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	VIDEO
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function handleVideo(&$content, $lang = null) {

		// convert poster images
		$this->handleVideoPoster($content, 'poster_desktop', 'poster-desktop', 1920, 1080, $lang);
		$this->handleVideoPoster($content, 'poster_mobile', 'poster-mobile', 960, 540, $lang);

		$targetFolder = $this->storageUrl . $this->target->storageFolder;

		// move video files
		$property = 'video_desktop' . (!empty($lang) ? '_'.$lang : '');
		if(isset($content[$property])) {
			$content[$property] = $this->moveFile($content[$property], $targetFolder.str_replace('_','-',$property).'.mp4');
		}
		$property = 'video_mobile' . (!empty($lang) ? '_'.$lang : '');
		if(isset($content[$property])) {
			$content[$property] = $this->moveFile($content[$property], $targetFolder.str_replace('_','-',$property).'.mp4');
		}
	}


	protected function handleVideoPoster(&$content, $property, $filename, $imgWidth, $imgHeight, $lang = null) {

		$property .= !empty($lang) ? '_'.$lang : '';
		if(!isset($content[$property])) { return;}

		$image = $content[$property];
		$extension = $this->getFileExtension($image);

		// image processing
		$filename .= !empty($lang) ? '-'.$lang : '';
		$imgUrl = $this->convertImage($extension == 'png'?'png':'jpg', $imgWidth, $imgHeight, $image, $filename , 70, 75);

		// update content property
		if($imgUrl) {
			$content[$property] = $imgUrl;
			$this->deleteUploadFile($image);
		}
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
