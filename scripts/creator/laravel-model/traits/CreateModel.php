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


trait CreateModel {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MODEL
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createModel() {

		$this->fillTemplate('Template.php', 'app/Models/App/'.$this->userInput['name'].'.php', function($template) {

			$this->setModelTarget($template);

			$this->replace($template, '{{attributes}}', $this->createModelAttributes());
			$this->replace($template, '{{translatables}}', $this->createModelTranslatables());
			$this->replace($template, '{{casts}}', $this->createModelCasts());
			$this->replace($template, '{{relations}}', $this->createModelRelations());

			return $template;
		});
	}


	protected function setModelTarget(&$template) {

		if($this->userInput['target'] == 'fragment') {
			$this->replace($template, 'useSlug = true','useSlug = false');
			$this->replace($template, 'useSearch = true','useSearch = false');
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    ATTRIBUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createModelAttributes() {

		$attributes = '';
		if($this->userInput['target'] == 'fragment') { $attributes .= "'parent_id', 'parent_type',\n\t\t"; }

		foreach($this->userInput['attributes'] as $a) {

			if($a['translatable']) { continue; }
			$attributes .= "'".$a['name']."', ";
		}

		return Str::replaceLast(', ', '', $attributes);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    TRANSLATABLES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createModelTranslatables() {

		$translatables = '';

		foreach($this->userInput['attributes'] as $a) {

			if(!$a['translatable']) { continue; }
			$translatables .= "'".$a['name']."', ";
		}

		return Str::replaceLast(', ', '', $translatables);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CASTS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createModelCasts() {

		$casts = '';

		foreach($this->userInput['attributes'] as $a) {

			$casts .= match($a['type']) {
				'boolean' => "'".$a['name']."' => 'boolean',\n\t\t",
				'datetime' => "'".$a['name']."' => 'datetime',\n\t\t",
				default => '',
			};
		}

		return $casts != '' ? "\n\t\t" .Str::replaceLast("\t",'',$casts) : '';
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createModelRelations() {

		$relations = "";
		$deleteRelations = false;

		foreach($this->userInput['relations'] as $r) {

			if($r['type'] == 'hasOne') {

				$relations .= "\tpublic function ".$r['slug']."() {\n\n\t\t";
				$relations .= "return \$this->getSingleRelation('App\Models\App\\".$r['target']."', '".$r['slug']."_id');";
				$relations .= "\n\t}\n\n\n";
			}
			else if($r['type'] == 'belongsToMany') {

				$relations .= "\tpublic function ".Str::plural($r['slug'])."() {\n\n\t\t";
				$relations .= "return \$this->getManyRelation('App\Models\App\\".$r['target']."');";
				$relations .= "\n\t}\n\n\n";
				$deleteRelations = true;
			}
			else if($r['type'] == 'childRelation') {

				$relations .= "\tpublic function ".Str::plural($r['slug'])."() {\n\n\t\t";
				$relations .= "return \$this->getChildRelation('App\Models\App\Base\\".$r['target']."');";
				$relations .= "\n\t}\n\n\n";
				$deleteRelations = true;
			}
			else if($r['type'] == 'parentRelation') {

				$relations .= "\tpublic function parent() {\n\n\t\t";
				$relations .= "return \$this->getParentRelation();";
				$relations .= "\n\t}\n\n\n";
			}
		}

		// delete relations
		if($deleteRelations) {

			$relations .= "\tpublic function deleteRelations() {\n\n\t\t";
			foreach($this->userInput['relations'] as $r) {

				if($r['type'] == 'belongsToMany') {
					$relations .= "\$this->".Str::plural($r['slug'])."()->detach();\n\t\t";
				}
				else if($r['type'] == 'childRelation') {
					$relations .= "\$this->".Str::plural($r['slug'])."()->delete();\n\t\t";
				}
			}
			$relations = Str::replaceLast("\n\t\t", '', $relations);
			$relations .= "\n\t}\n\n\n";
		}

		$comment = "\n" . $this->commentSection('RELATIONS');

		return $relations != '' ? $comment.$relations : '';
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end trait
