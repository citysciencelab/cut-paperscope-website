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


trait CreateSaveRequestTest {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    SAVE REQUEST TEST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createSaveRequestTest() {

		$this->fillTemplate('TemplateSaveRequestTest.php', 'tests/PHPUnit/Feature/Http/Requests/Backend/'.$this->userInput['name'].'SaveRequestTest.php', function($template) {

			$this->setSaveRequestTestTarget($template);

			$this->replaceLine($template, '{{attributes}}', $this->createSaveRequestTestAttributes());
			$this->replaceLine($template, '{{relations}}', $this->createSaveRequestTestRelations());
			$this->replaceLine($template, '{{includesApp}}', $this->createSaveRequestTestIncludesApp());
			$this->replaceLine($template, '{{assertRelations}}', $this->createSaveRequestTestAssertRelations());

			return $template;
		});
	}


	protected function setSaveRequestTestTarget(&$template) {

		if($this->userInput['target'] == 'fragment') {
			$this->replaceLine($template, "'slug' => 'test-slug',",'');
			$this->replaceLine($template, "'published_start' => '1.2.2023 12:34',",'');
		}

		$jobs = "";
		if($this->userInput['target'] == 'page') {
			$jobs .= "Bus::assertDispatched(ProcessSharingUpload::class);";
		}
		$this->replaceLine($template, '{{assertJobs}}', $jobs);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    ATTRIBUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createSaveRequestTestAttributes() {

		$attributes = "";

		foreach($this->userInput['attributes'] as $a) {

			if($a['translatable']) {
				$attributes .= "\t\t\$formData[\$this->translateProp('".$a['name']."')] = ";
			}
			else {
				$attributes .= "\t\t\$formData['".$a['name']."'] = ";
			}

			$value = match($a['type']) {
				'string' => "'test ".$a['name']."'",
				'integer' => "1",
				'float' => "1.0",
				'boolean' => "false",
				'datetime' => "'1.2.2023 12:34'",
				'richtext' => "'<p>test ".$a['name']."</p>'",
				'image' => "'test ".$a['name'].".jpg'",
				'file' => "'test ".$a['name']."'.txt",
			};

			$attributes .= $value.";\n";
		}

		return Str::replaceLast("\n","",$attributes);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createSaveRequestTestRelations() {

		$relations = "";

		foreach($this->userInput['relations'] as $r) {

			if($r['type'] == 'hasOne') {
				$relations .= "\t\t\$".$r['slug']." = ".$r['target']."::factory()->create();\n";
				$relations .= "\t\t\$formData['".$r['slug']."_id'] = \$".$r['slug']."->id;\n";
			}
			if($r['type'] == 'belongsToMany') {
				$plural = Str::plural($r['slug']);
				$relations .= "\t\t\$".$plural." = ".$r['target']."::factory()->count(2)->create()->toArray();\n";
				$relations .= "\t\t\$formData['".$plural."'] = \$".$plural.";\n";
			}
		}

		if($relations != "") {
			$relations = "\n\t\t// arrange: relations\n".$relations;
		}

		return Str::replaceLast("\n","",$relations);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createSaveRequestTestIncludesApp() {

		$includes = "";

		foreach($this->userInput['relations'] as $r) {

			if($r['type'] == 'hasOne' || $r['type'] == 'belongsToMany') {
				$includes .= "use App\Models\App\\".$r['target'].";\n\t";
			}
		}

		if($this->userInput['target'] == 'page') {
			$includes .= "use App\Jobs\Base\ProcessSharingUpload;\n\t";
		}

		return Str::replaceLast("\n\t", '', $includes);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    ASSERT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createSaveRequestTestAssertRelations() {

		$asserts = "";

		foreach($this->userInput['relations'] as $r) {

			if($r['type'] == 'hasOne') {
				$slug = $this->userInput['slug'];
				$asserts .= "\$".$slug." = ".$this->userInput['name']."::with('".$r['slug']."')->find(\$".$slug."['id']);\n\t\t";
				$asserts .= "\$this->assertEquals(\$".$r['slug']."['id'], \$".$slug."['".$r['slug']."']['id']);\n\t\t";
			}
			else if($r['type'] == 'belongsToMany') {
				$plural = Str::plural($r['slug']);
				$slug = $this->userInput['slug'];
				$asserts .= "\$".$slug." = ".$this->userInput['name']."::with('".$plural."')->find(\$".$slug."['id']);\n\t\t";
				$asserts .= "\$this->assertEquals(count(\$".$plural."), \$".$slug."['".$plural."']->count());\n\t\t";
				$asserts .= "\$this->assertEquals(\$".$plural."[0]['id'], \$".$slug."['".$plural."'][0]['id']);\n\t\t";
			}
		}

		if($asserts != "") {
			$asserts = "\n\t\t// assert: relations\n\t\t".$asserts;
		}

		return Str::replaceLast("\n\t\t", '', $asserts);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end trait
