<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// laravel
	require_once __DIR__ . '/../../../../vendor/autoload.php';
	use Illuminate\Support\Str;
	use Ramsey\Uuid\Uuid;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MAIN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


trait CreateFactory {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MODEL
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createFactory() {

		$this->fillTemplate('TemplateFactory.php', 'database/factories/App/'.$this->userInput['name'].'Factory.php', function($template) {

			$this->replace($template, '{{attributes}}', $this->createFactoryAttributes());

			return $template;
		});

		$this->addFactoryToSeeder();
		$this->addFactoryData();

		$this->addFactoryToTestingSeeder();

	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    ATTRIBUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createFactoryAttributes() {

		$attributes = '';

		// find longast relation name
		$longest = '';
		foreach($this->userInput['attributes'] as $a) {
			if(strlen($a['name']) > strlen($longest)) { $longest = $a['name']; }
		}

		foreach($this->userInput['attributes'] as $a) {

			$name = $a['name'];
			$tabs = $this->calculateTabs($name, $longest);
			if($a['translatable']) { $name .= '_'.$this->defaultLang; }

			$value = match($a['type']) {
				"string" => '$this->faker->text('.max(5,intval($a['length'] * 0.5)).')',
				"integer" => '$this->faker->numberBetween(1, 100)',
				"float" => '$this->faker->randomFloat(2, 1, 100)',
				"boolean" => '$this->faker->boolean()',
				"datetime" => '$this->faker->dateTime()',
				"richtext" => "'<p>'.\$this->faker->text(20).'</p>'",
				default => null
			};
			if(!$value) continue;

			$attributes .= "'" . $name."' =>" . $tabs . $value . ",\n\t\t\t";
		}

		return Str::replaceLast("\n\t\t\t", '', $attributes);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    SEEDER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function addFactoryToSeeder() {

		$seeder = file_get_contents(__DIR__ . '/../../../../database/seeders/LocalSeeder.php');

		// add factory include
		preg_match_all('/use App.+$/m', $seeder, $matches);
		$lastUse = Str::replace("\\","\\\\",array_pop($matches[0]));
		$use = "use App\\Models\\App\\".$this->userInput['name'].';';
		$this->replaceLine($seeder, $lastUse, $lastUse."\n\t".$use);

		// append factory to run method
		$factory = '$this->seedFactoryData('.$this->userInput['name'].'::factory(),\'App/'.$this->userInput['name'].'FactoryData.json\');';
		preg_match('/\/\/\sRUN.*\/\/\sFACTORY/ms',$seeder, $matches);
		$replace = Str::replaceLast('}', "\n\t\t".$factory."\n\t}", $matches[0]);
		$seeder = Str::replaceFirst($matches[0], $replace, $seeder);

		if($this->testMode) {
			file_put_contents(__DIR__ . '/../tests/LocalSeeder.php', $seeder);
		}
		else {
			file_put_contents(__DIR__ . '/../../../../database/seeders/LocalSeeder.php', $seeder);
		}
	}


	protected function addFactoryToTestingSeeder() {

		if($this->userInput['target'] == 'fragment') { return; }

		$seeder = file_get_contents(__DIR__ . '/../../../../database/seeders/TestingSeeder.php');

		// add factory include
		preg_match_all('/use App.+$/m', $seeder, $matches);
		$lastUse = Str::replace("\\","\\\\",array_pop($matches[0]));
		$use = "use App\\Models\\App\\".$this->userInput['name'].';';
		$this->replaceLine($seeder, $lastUse, $lastUse."\n\t".$use);

		// add factory relations
		$relations = '';
		foreach($this->userInput['relations'] as $r) {

			if($r['type'] == 'hasOne') { $relations .= 'has'.$r['target'].'()->'; }
			if($r['type'] == 'belongsToMany') { $relations .= 'has'.Str::plural($r['target']).'()->'; }
			else if($r['type'] == 'childRelation') { $relations .= 'hasFragments(3)->'; }
		}

		// factory seeder
		$factory = $this->userInput['name'].'::factory()->count(3)->public()->'.$relations.'create();'."\n\t\t";
		$factory .= $this->userInput['name'].'::factory()->count(3)->'.$relations.'create([\'public\'=>false]);';

		// append factory to run method
		preg_match('/\/\/\sRUN.*\/\*\/\//ms',$seeder, $matches);
		$replace = Str::replaceLast('}', "\n\t\t".$factory."\n\t}", $matches[0]);
		$seeder = Str::replaceFirst($matches[0], $replace, $seeder);

		if($this->testMode) {
			file_put_contents(__DIR__ . '/../tests/TestingSeeder.php', $seeder);
		}
		else {
			file_put_contents(__DIR__ . '/../../../../database/seeders/TestingSeeder.php', $seeder);
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    FACTORY DATA
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function addFactoryData() {

		$faker = Faker\Factory::create();

		$factoryData = "{\n\t\"data\": [\n\t\t{\n\t\t\t";

		// default props
		$factoryData .= "\"id\": \"".Uuid::uuid4()."\",\n\t\t\t";
		$factoryData .= "\"name\": \"".$this->userInput['name']."\",\n\t\t\t";
		if($this->userInput['target'] != 'fragment') {
			$factoryData .= "\"slug\": \"".Str::slug($faker->text(20))."\",\n\t\t\t";
		}
		$factoryData .= "\"public\": true,\n\t\t\t";
		$factoryData .= "\"order\": 0,\n\t\t\t";

		// attributes
		$attributes = '';
		foreach($this->userInput['attributes'] as $a) {

			$name = $a['name'];
			if($a['translatable']) { $name .= '_'.$this->defaultLang; }

			$value = match($a['type']) {
				"string" => '"'.$faker->text(max(5,intval($a['length'] * 0.5))).'"',
				"integer" => $faker->numberBetween(1, 100),
				"float" => $faker->randomFloat(2, 1, 100),
				"boolean" => $faker->boolean(),
				"datetime" => $faker->dateTime(),
				"richtext" => '"<p>'.$faker->text(20).'<\/p>"',
				default => null
			};
			if(!$value) continue;

			$attributes .= '"'.$name.'": ';
			$attributes .= $value.",\n\t\t\t";
		}

		$factoryData .= $attributes;
		$factoryData = Str::replaceLast(",\n\t\t\t", '', $factoryData);
		$factoryData .= "\n\t\t}\n\t]\n}";

		if($this->testMode) {
			file_put_contents(__DIR__ . '/../tests/'.$this->userInput['name'].'FactoryData.json', $factoryData);
		}
		else {
			file_put_contents(__DIR__ . '/../../../../database/factories/App/'.$this->userInput['name'].'FactoryData.json', $factoryData);
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end trait
