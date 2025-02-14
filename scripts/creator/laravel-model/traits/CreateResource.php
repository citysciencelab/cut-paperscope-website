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


trait CreateResource {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    RESOURCE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createResource() {

		$this->fillTemplate('TemplateResource.php', 'app/Http/Resources/'.$this->userInput['name'].'Resource.php', function($template) {

			$this->replaceLine($template, '{{attributes}}', $this->createResourceAttributes());
			$this->replaceLine($template, '{{relations}}', $this->createResourceRelations());
			$this->replaceLine($template, '{{backendProperties}}', $this->createResourceBackendProperties());
			$this->replaceLine($template, '{{includesApp}}', $this->createResourceIncludesApp());

			return $template;
		});

		$this->fillTemplate('TemplateListResource.php', 'app/Http/Resources/'.$this->userInput['name'].'ListResource.php', function($template) {

			$this->replaceLine($template, '{{attributes}}', $this->createResourceAttributes());
			$this->replaceLine($template, '{{relations}}', $this->createResourceRelations());
			$this->replaceLine($template, '{{backendProperties}}', $this->createResourceBackendProperties());
			$this->replaceLine($template, '{{includesApp}}', $this->createResourceIncludesApp());

			return $template;
		});
	}


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    ATTRIBUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createResourceAttributes() {

		$attributes = "";

		// find longast attribute name
		$longest = '';
		foreach($this->userInput['attributes'] as $a) {
			if(strlen($a['name']) > strlen($longest)) { $longest = $a['name']; }
		}

		foreach($this->userInput['attributes'] as $a) {

			$tabs = $this->calculateTabs($a['name'], $longest);
			$attributes .= "'".$a['name']."' =>".$tabs;

			if($a['translatable']) {
				$attributes .= "\$this->translate('".$a['name']."'),\n\t\t\t";
			}
			else {
				$attributes .= "\$this->".$a['name'].",\n\t\t\t";
			}
		}

		return Str::replaceLast("\n\t\t\t", "", $attributes);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createResourceRelations() {

		$relations = "";

		// find longast relation name
		$longest = '';
		foreach($this->userInput['relations'] as $r) {
			if(strlen($r['slug']) > strlen($longest)) { $longest = $r['slug']; }
		}

		foreach($this->userInput['relations'] as $r) {

			$tabs = $this->calculateTabs($r['slug'], $longest);

			if($r['type'] == 'hasOne') {
				$relations .= "'".$r['slug']."' =>".$tabs."\$this->getSingleRelation(".$r['target']."ListResource::class,'".$r['slug']."'),\n\t\t\t";
			}
			else if($r['type'] == 'belongsToMany') {
				$plural = Str::plural($r['slug']);
				$relations .= "'".$plural."' =>".$tabs."\$this->getManyRelation(".$r['target']."ListResource::class,'".$plural."'),\n\t\t\t";
			}
			else if($r['type'] == 'childRelation') {
				$relations .= "'fragments' =>".$tabs."\$this->getChildRelation(FragmentResource::class,'fragments'),\n\t\t\t";
			}
		}

		if($relations != "") {
			$relations = "\n\t\t\t// relations\n\t\t\t".$relations;
		}

		return Str::replaceLast("\n\t\t\t", "", $relations);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    BACKEND PROPERTIES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createResourceBackendProperties() {

		$properties = "";

		if($this->userInput['target'] == 'fragment') {
			$properties = "\$this->addBackendProperties([\n\t\t\t\t";
			$properties .= "'parent_id' => \$this->parent_id,\n\t\t\t\t";
			$properties .= "'parent_type' => \$this->parent_type\n\t\t\t";
			$properties .= "]),";
		}
		else {
			$properties = "//\$this->addBackendProperties(['approved' => \$this->approved]),";
		}

		return $properties;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    RESOURCE INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createResourceIncludesApp() {

		$includes = "";

		foreach($this->userInput['relations'] as $r) {

			if($r['type'] == 'childRelation') {
				$includes .= "use App\Http\Resources\Base\FragmentResource;\n\t";
			}
			else {
				$includes .= "use App\Http\Resources\\".$r['target']."Resource;\n\t";
			}
		}

		return $includes;
	}


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end trait
