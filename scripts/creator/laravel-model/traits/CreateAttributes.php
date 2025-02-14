<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// laravel
	require_once __DIR__ . '/../../../../vendor/autoload.php';
	use function Laravel\Prompts\form;
	use function Laravel\Prompts\text;
	use function Laravel\Prompts\confirm;
	use function Laravel\Prompts\clear;
	use function Laravel\Prompts\info;
	use function Laravel\Prompts\table;
	use Illuminate\Support\Collection;
	use Illuminate\Support\Str;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MAIN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


trait CreateAttributes {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    ATTRIBUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createAttributes() {

		$this->printAttributeHeader();

		while(confirm('Do you want to add an attribute?')) {

			$this->printAttributeHeader();

			$attribute = form()
				->text('Name of the attribute?',
					required:true,
					name:'name',
					transform: fn($value) => Str::snake($value),
				)
				->select('Type of the attribute?',
					options:[
						'string' => 'String',
						'integer' => 'Integer',
						'float' => 'Float',
						'boolean' => 'Boolean',
						'datetime' => 'Datetime',
						'richtext' => 'Richtext',
						'image' => 'Image',
						'file' => 'File',
					],
					scroll: 10,
					name:'type')
				->confirm('Is the attribute optional?', name:'nullable')
				->confirm('Is the attribute translatable?', name:'translatable')
				->add(function($form) {
					if($form['type'] == 'string') {
						return text('Length of the string?', default: 50);
					}
				}, name:'length')
				->add(function($form) {
					if(!$form['nullable']) {
						return confirm('Do you want to add a default value?');
					}
				}, name:'useDefault')
				->add(function($form) {
					if($form['useDefault']) {
						return text('Default value of the attribute?');
					}
				}, name:'default')
				->submit();

			array_push($this->userInput['attributes'], $attribute);

			$this->printAttributeHeader();
		}
	}


	protected function printAttributeHeader() {

		clear();
		info('Attributes');

		if(count($this->userInput['attributes']) == 0) { return; }

		table(
			headers: ['name', 'type', 'nullable', 'translatable', 'length', 'default'],
			rows: collect($this->userInput['attributes'])->map(function($attribute) {
				return [
					$attribute['name'],
					$attribute['type'],
					$attribute['nullable'] ? 'yes' : '',
					$attribute['translatable'] ? 'yes' : '',
					$attribute['length'] ?? '',
					$attribute['default'] ?? '',
				];
			}),
		);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end trait
