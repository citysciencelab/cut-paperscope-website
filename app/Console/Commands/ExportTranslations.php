<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Console\Commands;

	// Laravel
	use Illuminate\Console\Command;
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

	// App
	use App\Models\App\Base\Page;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ExportTranslations extends Command
{

	protected $signature = 'export:translations';
	protected $description = 'Export all translations strings';

	protected $spreadsheet;
	protected $globalOutput = [];



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HANDLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function handle() {

		$this->createExcel();

		$this->exportGlobalTranslations('Global', 'js/global/lang/');
		$this->exportGlobalTranslations('App', 'js/app/lang/');

		$this->exportVueComponents('Global Components', 'js/global/components/');
		$this->exportVueComponents('App Components', 'js/app/components/');
		$this->exportVueComponents('Global Pages', 'js/global/pages/');
		$this->exportVueComponents('App Pages', 'js/app/pages/');

		$this->exportModel(Page::class, true);

		$this->saveExcel();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    EXCEL
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	private function createExcel() {

		$this->spreadsheet = new Spreadsheet();
		$this->spreadsheet->removeSheetByIndex(0);
	}


	protected function saveExcel() {

		$writer = new Xlsx($this->spreadsheet);
		$writer->save('translations.xlsx');
	}


	protected function addTranslationsToSheet(string $nameSheet, $data) {

		$defaultLang = config('app.fallback_locale');
		$langs = config('app.available_locales');

		// init sheet
		$sheet = $this->spreadsheet->createSheet();
		$sheet->setTitle(substr($nameSheet,0,31));
		$sheet->setCellValue('A1', 'key');
		$sheet->getColumnDimension('A')->setWidth(50);

		// each col is a lang
		$col = 2;
		foreach ($langs as $lang) {
			$sheet->setCellValue([$col,1], $lang);
			$sheet->getColumnDimensionByColumn($col++)->setWidth(50);
		}

		// add a notes column at the end
		$sheet->setCellValue([$col,1], 'notes');
		$sheet->getColumnDimensionByColumn($col)->setWidth(50);

		// apply header style
		$style = [
			'font' => ['bold' => true],
			'alignment' => ['horizontal' => 'center'],
			'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['argb' => 'FF000000']]],
			'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFD9D9D9']]
		];
		$sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->applyFromArray($style);

		// collect all keys from all languages
		$keys = [];
		foreach ($langs as $lang) {
			$keys = array_unique(array_merge($keys, array_keys($data[$lang])));
		}

		// add values by keys
		$col=2; $row=2;
		foreach ($keys as $key) {

			$sheet->setCellValue([1,$row], $key);

			foreach ($langs as $lang) {
				$value = $data[$lang][$key] ?? '';
				if (!$value && $lang == $defaultLang) { $value = $key; }
				$sheet->setCellValue([$col,$row], $value);
				$col++;
			}

			$col=2; $row++;
		}

		// apply cell styling
		$sheet->getStyle('A1:'.$sheet->getHighestColumn().$sheet->getHighestRow())->getAlignment()->setWrapText(true);
		$sheet->getStyle('A1:'.$sheet->getHighestColumn().$sheet->getHighestRow())->getAlignment()->setVertical('top');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    GLOBAL TRANSLATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	private function exportGlobalTranslations(string $nameSheet, string $path): void {

		$output = $this->initOutput();
		$defaultLang = config('app.fallback_locale');
		$langs = config('app.available_locales');

		// add all global translations
		$path = resource_path($path);
		$files = scandir($path);
		foreach ($files as $file) {

			if ($file == '.' || $file == '..') { continue; }
			$contents = file_get_contents($path . $file);
			foreach ($langs as $lang) {

				// convert js object to json
				preg_match('/const '.$lang.' = ([\s\S]*?);/m', $contents, $matches);
				if(!$matches || count($matches)<2) { continue; }
				$content = $matches[1];
				$content = preg_replace('/\/\/.*\n/', '', $content); 			// remove lines with comments
				$content = preg_replace('/,\s*([\]\}])/', '$1', $content); 		// remove last comma
				$json = json_decode($content, true);

				// merge content to output
				$output[$lang] = array_merge($output[$lang], $json);
			}
		}

		// search for duplicates in global translations
		$keys = array_keys($output[$defaultLang]);
		foreach ($keys as $key) {
			if (isset($output[$defaultLang][$key]) && $output[$defaultLang][$key] == $key) {
				$this->info('Duplicate key in global translation: '.$key);
				unset($output[$defaultLang][$key]);
			}
		}
		$this->globalOutput = array_merge($this->globalOutput, $output);

		// save to excel sheet
		$this->addTranslationsToSheet($nameSheet, $output);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    VUE COMPONENTS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function exportVueComponents(string $nameSheet, string $path) {

		$this->exportVueSFC($path);


	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    SFC TRANSLATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function exportVueSFC($path) {

		$langs = config('app.available_locales');

		// iterate over all vue files recursively
		$absolutePath = resource_path($path);
		$files = scandir($absolutePath);
		foreach ($files as $file) {

			if($file == '.' || $file == '..') { continue; }

			// recursive call if directory
			if(is_dir($absolutePath . $file)) {

				// skip unsupported directories
				if($file == 'shop' && !config('app.features.shop')) { continue; }

				$this->exportVueSFC($path . $file . '/');
				continue;
			}

			// skip unsupported files
			if(pathinfo($file, PATHINFO_EXTENSION) != 'vue') { continue; }
			if($file == 'UserSubscription.vue' && !config('app.features.shop')) { continue; }

			$output = $this->initOutput();
			$contents = file_get_contents($absolutePath . $file);

			// find translations in component
			$this->getInlineTranslationStrings($contents, $output);
			$this->getI18N($contents, $output);
			$this->getInputSelectItems($contents, $output);

			// save to excel sheet
			if(count($output[$langs[0]]) > 0) {
				$this->addTranslationsToSheet($file, $output);
			}
		}
	}


	protected function getInlineTranslationStrings(&$contents, &$output) {

		$defaultLang = config('app.fallback_locale');

		// single quotes
		$results = preg_match_all('/[\s"\'+{]t\(\'(.*?)\'\)/m', $contents, $matches);
		if(!$results) { return; }
		foreach ($matches[1] as $key) { $output[$defaultLang][$key] = $key; }

		// double quotes
		$results = preg_match_all('/[\s"\'+{]t\("(.*?)"\)/m', $contents, $matches);
		if(!$results) { return; }
		foreach ($matches[1] as $key) { $output[$defaultLang][$key] = $key; }
	}


	protected function getI18N(&$contents, &$output) {

		// get i18n tag
		preg_match('/<i18n(.*?)>([\s\S]*?)<\/i18n>/m', $contents, $matches);
		if(!$matches || count($matches)<3) { return; }
		$i18n = $matches[2];

		// convert js object to json
		$i18n = preg_replace('/,\s*?}/m','}', $i18n); 		// remove last comma
		$i18n = str_replace("'", '"', $i18n);
		$json = json_decode($i18n, true);

		// merge content to output
		foreach ($json as $key => $value) {
			$output[$key] = array_merge($output[$key], $value);
		}
	}


	protected function getInputSelectItems(&$contents, &$output) {

		$defaultLang = config('app.fallback_locale');

		// get template tag
		preg_match('/<template>([\s\S]*)<\/template>/m', $contents, $matches);
		if(!$matches || count($matches)<2) { return; }
		$template = $matches[1];

		// get items from select input
		$matches = [];
		$results = preg_match_all('/<input-select(.*):options="(.*?)"/m', $template, $matches);
		if($results) {
			foreach ($matches[2] as $options) {

				// find list of items in vue setup data
				preg_match('/const '.$options.'([\s\S]*?)\(([\s\S]*?)\);/m',$contents,$matches2);
				if(!$matches2 || count($matches2)<3) { continue; }

				// get item labels from content
				$items = $matches2[2];
				$items = preg_replace('/,\s*([\]\}])/', '$1', $items);
				$items = str_replace("'", '"', $items);
				$json = json_decode('{"data":'.$items.'}', true);
				$json = array_merge(...$json['data']);
				$keys = array_keys($json);

				// add keys if not already present or in global translations
				foreach ($keys as $key) {
					if (!isset($output[$defaultLang][$key]) || $output[$defaultLang][$key] == $key) {
						$output[$defaultLang][$key] = $key;
					}
				}
			}
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MODEL
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function exportModel(string $model, bool $hasFragments = false) {

		$models = $model::with($hasFragments ? 'fragments' : [])->get();
		$langs = config('app.available_locales');
		$props = (new $model)->getTranslationProps();

		foreach ($models as $model) {

			$output = $this->initOutput();

			// add translation props
			foreach ($langs as $lang) {
				foreach ($props as $prop) {
					$output[$lang][$prop] = $model[$prop.'_'.$lang] ?? ' ';
				}
			}

			// add fragment translations
			if($hasFragments) {

				foreach ($model->fragments as $fragment) {

					// skip unsupported fragments
					if(array_search($fragment->template, ['text','text-image']) === false ) { continue; }

					foreach ($langs as $lang) {
						$json = json_decode($fragment['content_'.$lang], true);
						$output[$lang]['fragment_'.$fragment->name.'_title'] = $json['title_'.$lang] ?? ' ';
						$output[$lang]['fragment_'.$fragment->name.'_copy'] = $json['copy_'.$lang] ?? ' ';

						// image attributes
						if($fragment->template == 'text-image') {
							$output[$lang]['fragment_'.$fragment->name.'_subline'] = $json['subline_'.$lang] ?? ' ';
							$output[$lang]['fragment_'.$fragment->name.'_image_alt'] = $json['image_alt_'.$lang] ?? ' ';
						}
					}
				}
			}

			// save to excel sheet
			$modelName = class_basename($model) . ' ' . ($model->slug ?? $model->name);
			$this->addTranslationsToSheet($modelName, $output);
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HELPERS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function initOutput() {

		$output = [];

		$langs = config('app.available_locales');
		foreach ($langs as $lang) { $output[$lang] = []; }

		return $output;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

