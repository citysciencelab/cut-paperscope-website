<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// laravel
	require_once __DIR__ . '/../../../../vendor/autoload.php';
	use Illuminate\Support\Str;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MAIN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


trait CreateMigration {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MIGRATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createMigration() {

		$filename = $this->createMigrationFilename();

		$this->fillTemplate('create_templates_table.php', 'database/migrations/'.$filename, function($template) {

			$this->setMigrationTarget($template);

			$this->replaceLine($template, '{{attributes}}', $this->createMigrationAttributes());
			$this->replaceLine($template, '{{relations}}', $this->createMigrationRelations());
			$this->replaceLine($template, '{{translatables}}', $this->createMigrationTranslatables());

			return $template;
		});
	}


	protected function setMigrationTarget(&$template) {

		if($this->userInput['target'] == 'page') {
			$replace = "\$this->setPublishedProps(\$table);\n\t\t\$this->setSharingProps(\$table);";
			$this->replaceLine($template, '\$this->setPublishedProps\(\$table\);', $replace);
		}
		else if($this->userInput['target'] == 'fragment') {
			$this->replace($template, 'setDefaultProps','setDefaultPropsNoSlug');
			$replace = "\$this->setPublishedProps(\$table);\n\t\t\$this->setFragmentProps(\$table);";
			$this->replaceLine($template, '\$this->setPublishedProps\(\$table\);', $replace);
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    ATTRIBUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createMigrationAttributes() {

		$attributes = '';

		foreach($this->userInput['attributes'] as $a) {

			$attributes .= "\t\t\$table->";

			$attributes .= match($a['type']) {
				'string' =>		"string('".$a['name']."',".$a['length'].")",
				'integer' =>	"mediumInteger('".$a['name']."')",
				'float' =>		"float('".$a['name']."')",
				'boolean' =>	"boolean('".$a['name']."')",
				'datetime' =>	"dateTime('".$a['name']."')",
				'richtext' =>	"text('".$a['name']."')",
				'image' =>		"string('".$a['name']."',256)",
				'file' =>		"string('".$a['name']."',256)",
			};

			if($a['nullable']) {
				$attributes .= '->nullable()';
			}

			if($a['default']) {
				$attributes .= match($a['type']) {
					'string' => "->default('".$a['default']."')",
					'richtext' => "->default('<p>".$a['default']."</p>')",
					default => "->default(".$a['default'].")",
				};
			}

			$attributes .= ";\n";
		}

		// output
		$attributes = Str::replaceFirst("\t\t", '', $attributes);
		$attributes = Str::replaceLast("\n", '', $attributes);
		return $attributes;
	}


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createMigrationRelations() {

		$relations = $this->createMigrationSingleRelations();

		$this->createMigrationIntermediateRelations();

		return $relations;
	}


	protected function createMigrationIntermediateRelations() {

		foreach($this->userInput['relations'] as $r) {

			if($r['type'] != 'belongsToMany') { continue; }

			// alphabetical order
			$items = [$this->userInput['slug'], $r['slug']];
			sort($items);

			$filename = $this->createMigrationFilePrefix();
			$filename .= 'create_' . Str::plural($items[0]) . '_' . Str::plural($items[1]) . '_table.php';

			$this->fillTemplate('create_templates_relations_table.php', 'database/migrations/'.$filename, function($template) use ($items) {

				$this->replace($template, '{{item1}}', $items[0]);
				$this->replace($template, '{{item2}}', $items[1]);

				return $template;
			});
		}
	}


	protected function createMigrationSingleRelations() {

		$relations = '';
		$index = '';

		foreach($this->userInput['relations'] as $r) {

			if($r['type'] != 'hasOne') { continue; }

			$slug = Str::slug($r['target'],'_');

			// add relation cols
			$relations .= "\t\t\$table->foreignUuid('".$slug."_id')";
			if($r['nullable']) { $relations .= '->nullable()'; }
			$relations .= "->constrained();\n";

			// create index
			$index .= "'".$slug."',";
		}

		// add comment header
		if($relations != '') {
			$relations = "\n\t\t// relations\n".$relations;
		}

		// add index
		if($index != '') {
			$index = Str::replaceLast(',', '', $index);
			$relations .= "\n\t\t// internal";
			$relations .= "\n\t\t\$table->index([".$index."]);";
		}

		return $relations;
	}


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    TRANSLATABLES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createMigrationTranslatables() {

		$translatables = '';
		if(!$this->isMultiLang) { return ''; }

		foreach($this->userInput['attributes'] as $a) {

			if($a['translatable']) { $translatables .= "'".$a['name']."',"; }
		}

		if($translatables != '') {
			$translatables = Str::replaceLast(',', '', $translatables);
			$translatables = "\n\t\t\$this->translate(\$table, [".$translatables."]);";
		}

		return $translatables;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    FILENAME
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createMigrationFilename() {

		$filename = $this->createMigrationFilePrefix();

		return $filename . 'create_' . $this->userInput['plural'] . '_table.php';
	}


	protected function createMigrationFilePrefix() {

		$prefix = date('Y_m_d');

		// count current migrations from today
		$files = scandir(__DIR__ . '/../../../../database/migrations');
		$files = array_filter($files, fn($file) => strpos($file, $prefix) !== false);

		// add count to filename
		$prefix .= '_' . str_pad(count($files)+1, 6, '0', STR_PAD_LEFT);

		return $prefix . '_';
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end trait
