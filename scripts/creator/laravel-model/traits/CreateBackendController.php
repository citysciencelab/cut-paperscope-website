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


trait CreateBackendController {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    BACKEND CONTROLLER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createBackendController() {

		if($this->userInput['target'] == 'fragment') { return; }

		$this->fillTemplate('TemplateBackendController.php', 'app/Http/Controllers/Backend/'.$this->userInput['name'].'Controller.php', function($template) {

			$this->setBackendSharingJob($template);

			$this->replaceLine($template, '{{attributes}}', $this->createBackendControllerAttributes());
			$this->replaceLine($template, '{{translatables}}', $this->createBackendControllerTranslatables());
			$this->replaceLine($template, '{{translatables}}', $this->createBackendControllerTranslatables());
			$this->replace($template, '{{relations}}', $this->createBackendControllerRelations());
			$this->replace($template, '{{relationsList}}', $this->createBackendControllerRelationsList());

			return $template;
		});
	}


	protected function setBackendSharingJob(&$template) {

		$replaceIncludes = '';
		$replaceJobs = '';

		if($this->userInput['target'] == 'page') {
			$replaceIncludes = "use App\Jobs\Base\ProcessSharingUpload;";
			$replaceJobs = "ProcessSharingUpload::dispatch(\$".$this->userInput['slug'].");";
		}

		$this->replaceLine($template, '{{sharingjob_include}}', $replaceIncludes);
		$this->replaceLine($template, '{{sharingjob_process}}', $replaceJobs);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    ATTRIBUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createBackendControllerAttributes() {

		$attributes = "";

		// find longast attribute name
		$longest = '';
		foreach($this->userInput['attributes'] as $a) {
			if($a['translatable']) { continue; }
			if(strlen($a['name']) > strlen($longest)) { $longest = $a['name']; }
		}

		foreach($this->userInput['attributes'] as $a) {

			if($a['translatable']) { continue; }

			$tabs = $this->calculateTabs($a['name'], $longest);
			$attributes .= "\$".$this->userInput['slug']."->".$a['name'].$tabs."= \$validated->".$a['name'].";\n\t\t";
		}

		return Str::replaceLast('\t\t','',$attributes);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    TRANSLATABLES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createBackendControllerTranslatables() {

		$translatables = "";
		$hasAttributes = false;

		// find longast attribute name
		$longest = '';
		foreach($this->userInput['attributes'] as $a) {
			if(!$a['translatable']) { continue; }
			if(strlen($a['name']) > strlen($longest)) { $longest = $a['name']; }
		}

		foreach($this->userInput['attributes'] as $a) {

			if(!$a['translatable']) { $hasAttributes = true; continue; }

			$tabs = $this->calculateTabs($a['name'], $longest);
			$translatables .= "\$".$this->userInput['slug']."['".$a['name']."'.\$lang]".$tabs."= \$validated->{'".$a['name']."'.\$lang};\n\t\t\t";
		}

		if($translatables != "") {

			$translatables = Str::replaceLast("\t\t\t",'',$translatables);

			$output = ($hasAttributes?'':"\n") . "\t\t// save translatable properties\n\t\t";
			$output .= "foreach(\$this->langKeys as \$lang) {\n\t\t\t";
			$output .= $translatables;
			$output .= "\t\t}\n";

			$translatables = $output;
		}

		return Str::replaceLast('\t\t\t','',$translatables);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createBackendControllerRelations() {

		$relations = "";

		foreach($this->userInput['relations'] as $r) {

			if($r['type'] == 'hasOne') {

				$relations .= "\$".$this->userInput['slug']."->".$r['slug']."_id = \$validated->".$r['slug']."_id;\n\t\t";
			}
			else if($r['type'] == 'belongsToMany') {

				$plural = Str::plural($r['slug']);
				$relations .= "\$this->saveManyRelation('".$plural."',\$validated,\$".$this->userInput['slug'].");\n\t\t";
			}
			else if($r['type'] == 'parentRelation') {

				$relations .= "\$this->saveParentRelation(\$validated, \$".$this->userInput['slug'].");\n\t\t";
			}
		}

		if($relations != "") {

			$relations = Str::replaceLast("\t\t",'',$relations);
			$relations = "// save relations\n\t\t" . $relations;
		}

		return Str::replaceLast('\t\t','',$relations);
	}


	protected function createBackendControllerRelationsList() {

		$relations = "";

		foreach($this->userInput['relations'] as $r) {

			if($r['type'] == 'hasOne') {

				$relations .= "'".$r['slug']."',";
			}
			else if($r['type'] == 'belongsToMany') {

				$relations .= "'".Str::plural($r['slug'])."',";
			}
			else if($r['type'] == 'childRelation') {

				$relations .= "'".Str::plural($r['slug'])."',";
			}
		}

		return Str::replaceLast(',', '', $relations);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end trait
