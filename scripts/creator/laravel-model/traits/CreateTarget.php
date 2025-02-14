<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// laravel
	require_once __DIR__ . '/../../../../vendor/autoload.php';
	use function Laravel\Prompts\select;
	use function Laravel\Prompts\text;
	use function Laravel\Prompts\clear;
	use Illuminate\Support\Str;




/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MAIN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


trait CreateTarget {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    TARGET
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createTarget() {

		$defaults = [
			'item' => 'Item',
			'page' => 'Page',
			'fragment' => 'Fragment'
		];

		$presets = [
			'article' => 'Article',
			'event' => 'Event',
			'category' => 'Category',
			'tag' => 'Tag'
		];

		$type = select(
			label: 'Base new model on:',
			options: ['default' => 'Default model','preset' => 'Custom preset'],
		);
		clear();

		$this->userInput['target'] = select(
			label: 'What '.($type == 'default' ? 'template': 'preset').' do you want to use?',
			options: $type == 'default' ? $defaults : $presets,
		);
		clear();

		$this->userInput['name'] = text('Name of the new model?',
			required: true,
			default: $type == 'preset' ? $presets[$this->userInput['target']] : '',
		);
		clear();

		// transform target properties
		$this->userInput['name'] = Str::studly($this->userInput['name']);
		$this->userInput['slug'] = Str::slug($this->userInput['name'], '_');
		$this->userInput['plural'] = Str::plural($this->userInput['slug']);

		// apply presets
		if($type == 'preset') {
			$this->setPreset($this->userInput['target']);
		}
		$this->setDefault();


		clear();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    PRESETS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function setPreset(string $target) {

		$this->userInput['preset'] = $target;

		if($target == 'article' || $target == 'event') {
			$this->userInput['target'] = 'page';
			array_push($this->userInput['attributes'],
				[
					'name'=>'teaser_title',
					'type'=>'string',
					'length'=>150,
					'nullable'=>true,
					'translatable'=>true,
					'default'=>false,
				],
				[
					'name'=>'teaser_content',
					'type'=>'richtext',
					'nullable'=>true,
					'translatable'=>true,
					'default'=>false,
				],
				[
					'name'=>'teaser_image',
					'type'=>'image',
					'nullable'=>true,
					'translatable'=>true,
					'default'=>false,
				],
				[
					'name'=>'teaser_image_subline',
					'type'=>'string',
					'length'=>150,
					'nullable'=>true,
					'translatable'=>true,
					'default'=>false,
				],
			);
		}

		if($target == 'event') {
			array_push($this->userInput['attributes'],
				[
					'name'=>'event_start',
					'type'=>'datetime',
					'nullable'=>true,
					'translatable'=>false,
					'default'=>false,
				],
				[
					'name'=>'event_end',
					'type'=>'datetime',
					'nullable'=>true,
					'translatable'=>false,
					'default'=>false,
				],
				[
					'name'=>'event_url',
					'type'=>'string',
					'length'=>256,
					'nullable'=>true,
					'translatable'=>false,
					'default'=>false,
				],
			);
		}

		if($target == 'category' || $target == 'tag') {
			$this->userInput['target'] = 'item';
			array_push($this->userInput['attributes'],
				[
					'name'=>'title',
					'type'=>'string',
					'length'=>50,
					'nullable'=>false,
					'translatable'=>true,
					'default'=>false,
				],
			);
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    DEFAULTS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function setDefault() {

		if($this->userInput['target'] == 'page') {
			array_unshift($this->userInput['attributes'],
				[
					'name'=>'title',
					'type'=>'string',
					'length'=>150,
					'nullable' => true,
					'translatable'=>true,
					'default'=>false,
				]
			);

			array_unshift($this->userInput['relations'],
				[
					'target'=>'Fragment',
					'slug'=>'fragment',
					'type'=>'childRelation',
					'nullable'=>true,
				]
			);
		}

		if($this->userInput['target'] == 'fragment') {
			array_unshift($this->userInput['relations'],
				[
					'target'=>'Parent',
					'slug'=>'parent',
					'type'=>'parentRelation',
					'nullable'=>true,
				]
			);
		}

	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end trait
