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


trait CreateSaveRequest {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    SAVE REQUEST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createSaveRequest() {

		$this->fillTemplate('TemplateSaveRequest.php', 'app/Http/Requests/Backend/'.$this->userInput['name'].'SaveRequest.php', function($template) {

			$this->setSaveRequestTarget($template);

			$this->replace($template, '{{attributes}}', $this->createSaveRequestAttributes());
			$this->replaceLine($template, '{{relations}}', $this->createSaveRequestRelations());

			return $template;
		});
	}


	protected function setSaveRequestTarget(&$template) {

		if($this->userInput['target'] == 'page') {
			$this->replace($template, 'getBaseRules()',"getBaseRules(['sharing'])");
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    ATTRIBUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createSaveRequestAttributes() {

		$attributes = "";

		// find longast attribute name
		$longest = '';
		foreach($this->userInput['attributes'] as $a) {
			if(strlen($a['name']) > strlen($longest)) { $longest = $a['name']; }
		}

		foreach($this->userInput['attributes'] as $a) {

			$tabs = $this->calculateTabs($a['name'], $longest);
			$attributes .= "\t\t\t'".$a['name']."' =>".$tabs;

			$rule = match($a['type']) {
				'string' => "'bail|required|string|max:".$a['length']."'",
				'integer' => "'bail|required|integer'",
				'float' => "'bail|required|float'",
				'boolean' => "\$this->rule('boolean'".($a['nullable']?'':',true').")",
				'datetime' => "\$this->rule('input-datetime'".($a['nullable']?'':',true').")",
				'richtext' => "\$this->rule('input-richtext'".($a['nullable']?'':',true').")",
				'image' => "\$this->rule('input-image-upload'".($a['nullable']?'':',true').")",
				'file' => "\$this->rule('input-file-upload'".($a['nullable']?'':',true').")",
			};

			// convert default rules to nullable
			if($a['nullable']) {
				$rule = Str::replace('required', 'nullable', $rule);
			}

			if($a['translatable']) {
				$rule = Str::contains($rule,'->rule') ? Str::replaceLast(')',").'|translate'",$rule) : Str::replaceLast("'","|translate'",$rule);
			}

			$attributes .= $rule.",\n";
		}

		return Str::replaceLast("\n","",$attributes);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createSaveRequestRelations() {

		$relations = "";

		// find longast relation name
		$longest = '';
		foreach($this->userInput['relations'] as $r) {
			if(strlen($r['slug']) > strlen($longest)) { $longest = $r['slug']; }
		}

		foreach($this->userInput['relations'] as $r) {

			$tabs = $this->calculateTabs($r['slug'], $longest);

			if($r['type'] == 'hasOne') {
				$relations .= "\t\t\t'".$r['slug']."_id' =>".$tabs;
				$relations .= "'bail|".($r['nullable']?'nullable':'required')."|uuid|exists:".$r['slug'].",id',\n";
			}
			else if($r['type'] == 'belongsToMany') {
				$plural = Str::plural($r['slug']);
				$relations .= "\t\t\t'".$plural."' =>".$tabs;
				$relations .= "\$this->rule('input-relation'),\n";
				$relations .= "\t\t\t'".$plural.".*.id' =>".$tabs;
				$relations .= "\$this->rule('input-relation-item',".($r['nullable']?'false':'true').",'".$plural."'),\n";
			}
			else if($r['type'] == 'parentRelation') {
				$relations .= "\t\t\t'parent_id' =>\t".$tabs."\$this->rule('parent_id'),\n";
				$relations .= "\t\t\t'parent_type' =>".$tabs."\$this->rule('parent_type'),\n";
				$relations .= "\t\t\t'order' =>\t\t".$tabs."'bail|nullable|integer|gte:0',\n";
			}
		}

		if($relations != "") {
			$relations = "\n\t\t\t// relations\n".$relations;
		}

		return Str::replaceLast("\n","",$relations);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end trait
