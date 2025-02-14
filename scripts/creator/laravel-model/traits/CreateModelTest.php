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


trait CreateModelTest {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MODEL TEST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createModelTest() {

		$this->fillTemplate('TemplateTest.php', 'tests/PHPUnit/Feature/Models/App/'.$this->userInput['name'].'Test.php', function($template) {

			$this->setModelTestTarget($template);

			$this->replaceLine($template, '{{attributes}}', $this->createModelTestAttributes());
			$this->replaceLine($template, '{{translatables}}', $this->createModelTestTranslatables());
			$this->replace($template, '{{relations}}', $this->createModelTestRelations());
			$this->replaceLine($template, '{{relationIncludes}}', $this->createModelTestRelationIncludes());

			return $template;
		});
	}


	protected function setModelTestTarget(&$template) {

		if($this->userInput['target'] == 'fragment') {
			$this->replace($template, 'getBaseProps','getBasePropsNoSlug');
			$this->replace($template, 'getPublishedProps','getParentModelProps');
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    ATTRIBUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createModelTestAttributes() {

		$attributes = '';

		foreach($this->userInput['attributes'] as $a) {

			if($a['translatable']) { continue; }
			$attributes .= "'".$a['name']."', ";
		}

		return $attributes;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    TRANSLATABLES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createModelTestTranslatables() {

		$translatables = '';

		foreach($this->userInput['attributes'] as $a) {

			if(!$a['translatable']) { continue; }
			$translatables .= "\$this->translateProp('".$a['name']."'),\n\t\t\t";
		}

		return Str::replaceLast("\n\t\t\t", '', $translatables);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createModelTestRelations() {

		$relations = '';

		foreach($this->userInput['relations'] as $r) {

			if($r['type'] == 'hasOne') {
				$relations .= $this->createModelTestSingleRelation($r);
			}
			else if($r['type'] == 'belongsToMany') {
				$relations .= $this->createModelTestManyRelation($r);
			}
			else if($r['type'] == 'childRelation') {
				$relations .= $this->createModelTestChildRelation($r);
			}
			else if($r['type'] == 'parentRelation') {
				$relations .= $this->createModelTestParentRelation($r);
			}
		}

		return $relations != '' ? "\n".$relations : '';
	}


	protected function createModelTestSingleRelation($relation) {

		$out = $this->commentSection($relation['target'].' RELATION');
		$out .= $this->getTestPartial('TemplateTestSingle.php', $relation);

		return $out;
	}


	protected function createModelTestManyRelation($relation) {

		$out = $this->commentSection($relation['target'].' RELATION');
		$out .= $this->getTestPartial('TemplateTestMany.php', $relation);

		return $out;
	}


	protected function createModelTestChildRelation($relation) {

		$out = $this->commentSection('FRAGMENT RELATION');
		$out .= $this->getTestPartial('TemplateTestChild.php', $relation);

		return $out;
	}


	protected function createModelTestParentRelation($relation) {

		$out = $this->commentSection('PARENT RELATION');
		$out .= $this->getTestPartial('TemplateTestParent.php', $relation);

		return $out;
	}


	protected function createModelTestRelationIncludes() {

		$includes = '';

		foreach($this->userInput['relations'] as $r) {

			if($r['type'] == 'parentRelation') {
				$includes .= "use App\Models\App\Base\Item;\n\t";
			}
			else if($r['type'] == 'childRelation') {
				$includes .= "use App\Models\App\Base\Fragment;\n\t";
			}
			else {
				$includes .= "use App\Models\App\\".$r['target'].";\n\t";
			}
		}

		return Str::replaceLast("\n\t", '', $includes);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function getTestPartial($file,$relation) {

		// read template
		$template = file_get_contents(__DIR__ . '/../templates/' . $file);

		// sort relation
		$items = collect([$this->userInput['slug'], $relation['slug']])->sort();
		$items = $items->values()->all();

		// replace template vars
		$template = Str::replace('{{ModelClass}}', $this->userInput['name'], $template);
		$template = Str::replace('{{slug}}', $this->userInput['slug'], $template);
		$template = Str::replace('{{plural}}', $this->userInput['plural'], $template);

		// replace relation vars
		$template = Str::replace('{{r_slug}}', $relation['slug'], $template);
		$template = Str::replace('{{r_target}}', $relation['target'], $template);
		$template = Str::replace('{{r_plural}}', Str::plural($relation['slug']), $template);
		$template = Str::replace('{{r_hasPlural}}', Str::ucfirst(Str::plural($relation['slug'])), $template);
		$template = Str::replace('{{r_table}}', $items[0].'_'.$items[1], $template);

		return $template;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end trait
