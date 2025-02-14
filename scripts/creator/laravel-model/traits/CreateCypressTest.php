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


trait CreateCypressTest {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MIGRATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createCypressTest() {

		$filename = $this->userInput['slug'] . '/01-backend-model-'.$this->userInput['slug'].'-create.cy.js';

		$this->fillTemplate('01-backend-model-template-create.cy.js', 'tests/Cypress/e2e/backend/model/'.$filename, function($template) {
			$this->replaceLine($template, '{{attributes}}', $this->createCypressAttributes());
			return $template;
		});

		$filename = $this->userInput['slug'] . '/02-backend-model-'.$this->userInput['slug'].'-edit.cy.js';
		$this->fillTemplate('02-backend-model-template-edit.cy.js', 'tests/Cypress/e2e/backend/model/'.$filename, function($template) {
			return $template;
		});

		$filename = $this->userInput['slug'] . '/03-backend-model-'.$this->userInput['slug'].'-delete.cy.js';
		$this->fillTemplate('03-backend-model-template-delete.cy.js', 'tests/Cypress/e2e/backend/model/'.$filename, function($template) {
			return $template;
		});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    ATTRIBUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createCypressAttributes() {

		$faker = Faker\Factory::create();

		$attributes = '';
		foreach($this->userInput['attributes'] as $a) {

			$name = Str::replace("_","-",$a['name']);

			if($a['type'] == "string") {
				$value = '"'.$faker->text(max(5,intval($a['length'] * 0.5))).'"';
				$attributes .= "cy.get('#input-".$name."').type(".$value.");\n\t\t";
			}
			else if($a['type'] == "integer") {
				$value = $faker->numberBetween(1, 100);
				$attributes .= "cy.get('#input-".$name."').type(".$value.");\n\t\t";
			}
			else if($a['type'] == "float") {
				$value = $faker->randomFloat(2, 1, 100);
				$attributes .= "cy.get('#input-".$name."').type(".$value.");\n\t\t";
			}
			else if($a['type'] == "boolean") {
				$value = $name . ($faker->boolean()?'-0':'-1');
				$attributes .= "cy.get('#input-".$value."').check({force:true});\n\t\t";
			}
			else if($a['type'] == "datetime") {
				$attributes .= "cy.get('#input-".$name."').click();\n\t\t";
				$attributes .= "cy.wait(1000); // popup opening\n\t\t";
				$attributes .= "cy.get('.date-calendar-day.today').first().click();\n\t\t";
				$attributes .= "cy.get('.date-selector-confirm').click();\n\t\t";
				$attributes .= "cy.wait(1000); // popup closing\n\t\t";
			}
			else if($a['type'] == "richtext") {
				$value = $faker->text(20);
				$attributes .= "cy.get('.row-".$name." .ck-editor__editable').then(el => {\n\t\t\t";
				$attributes .= "const editor = el[0].ckeditorInstance;\n\t\t\t";
				$attributes .= "editor.setData('".$value."');\n\t\t";
				$attributes .= "});\n\t\t";
			}
		}

		// output
		$attributes = Str::replaceLast("\n\t\t", '', $attributes);
		return $attributes;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end trait
