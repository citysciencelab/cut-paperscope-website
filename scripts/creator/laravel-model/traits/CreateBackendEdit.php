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


trait CreateBackendEdit {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    BACKEND NAVI
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createBackendEdit() {

		$this->fillTemplate('PageTemplateEdit.vue', 'resources/js/backend/pages/'.$this->userInput['slug'].'/Page'.$this->userInput['name'].'Edit.vue', function($template) {

			$this->setBackendEditTarget($template);
			$this->replaceLine($template, '{{attributes}}', $this->createBackendEditAttributes());
			$this->replaceLine($template, '{{relations}}', $this->createBackendEditRelations());

			return $template;
		});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    TARGET
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function setBackendEditTarget(string &$template) {

		if($this->userInput['target'] == 'page') {
			$this->replace($template, 'v-slot','page v-slot');
		}
		else if($this->userInput['target'] == 'fragment') {
			$this->replace($template, 'v-slot',':slug="false" v-slot');
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    ATTRIBUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createBackendEditAttributes() {

		$attributes = '';

		foreach($this->userInput['attributes'] as $a) {

			$req = $a['nullable'] ? '' : ' required';
			$trans = $a['translatable'] ? ' multilang' : '';
			$id = Str::replace('_','-',$a['name']);

			if($a['type']=='string') {
				$attributes .= '<input-text :label="t(\''.$a['name'].'\')" id="'.$id.'" v-model="form" :error="errors" :max-length="'.$a['length'].'"'.$req.$trans.'/>';
			}
			else if($a['type']=='integer' || $a['type']=='float') {
				$attributes .= '<input-text :label="t(\''.$a['name'].'\')" id="'.$id.'" v-model="form" :error="errors"'.$req.$trans.'/>';
			}
			else if($a['type']=='boolean') {
				$attributes .= '<input-radio :label="t(\''.$a['name'].'\')" id="'.$id.'" :options="itemsBoolean" v-model="form" :error="errors"'.$req.$trans.'/>';
			}
			else if($a['type']=='datetime') {
				$attributes .= '<input-date-time :label="t(\''.$a['name'].'\')" id="'.$id.'" v-model="form" :error="errors"'.$req.$trans.'/>';
			}
			else if($a['type']=='richtext') {
				$attributes .= '<input-richtext :label="t(\''.$a['name'].'\')" id="'.$id.'" :folder="folder" v-model="form" :error="errors"'.$req.$trans.'/>';
			}
			else if($a['type']=='image') {
				$attributes .= '<input-file :label="t(\''.$a['name'].'\')" info="HQ in 1600x900px" id="'.$id.'" type="image" :folder="folder" v-model="form" :error="errors"'.$req.$trans.'/>';
			}
			else if($a['type']=='file') {
				$attributes .= '<input-file :label="t(\''.$a['name'].'\')" id="'.$id.'" :folder="folder" v-model="form" :error="errors"'.$req.$trans.'/>';
			}

			$attributes .= "\n\t\t\t\t";
		}

		return Str::replaceLast("\n\t\t\t\t", '', $attributes);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createBackendEditRelations() {

		$relations = '';

		foreach($this->userInput['relations'] as $r) {
			if($r['type'] == 'hasOne') {
				$plural = Str::plural($r['slug']);
				$relations .= '<input-relation :label="t(\''.$plural.'\')" id="'.$plural.'" :max-length="1" v-model="form" :error="errors" relation="'.$r['slug'].'"/>'."\n\t\t\t\t";
			}
			if($r['type'] == 'belongsToMany') {
				$plural = Str::plural($r['slug']);
				$relations .= '<input-relation :label="t(\''.$plural.'\')" id="'.$plural.'" v-model="form" :error="errors" relation="'.$r['slug'].'"/>'."\n\t\t\t\t";
			}
			if($r['type'] == 'childRelation') {
				$relations .= '<input-fragments :label="t(\'Inhalte Detailseite\')" id="fragments" v-model="form" :error="errors"/>'."\n\t\t\t\t";
			}
		}

		return Str::replaceLast("\n\t\t\t\t", '', $relations);
	}


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end trait
