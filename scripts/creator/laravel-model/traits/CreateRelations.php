<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// laravel
	require_once __DIR__ . '/../../../../vendor/autoload.php';
	use function Laravel\Prompts\form;
	use function Laravel\Prompts\select;
	use function Laravel\Prompts\confirm;
	use function Laravel\Prompts\clear;
	use function Laravel\Prompts\info;
	use function Laravel\Prompts\table;
	use Illuminate\Support\Collection;
	use Illuminate\Support\Str;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MAIN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


trait CreateRelations {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    RELATIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createRelations() {

		$this->printRelationHeader();

		while(confirm('Do you want to add a relation?')) {

			$this->printRelationHeader();

			$relation = form()
				->select('Select existing model for relation?',
					options: $this->getAvailableRelations(),
					scroll: 10,
					name:'target',
				)
				->add(function($form) {
					if($form['target']=='Fragment') { return 'childRelation'; }
					return select('Select type of relation?',
						options:[
							'hasOne' => "Every ".$this->userInput['name'] ." has one ".$form['target'],
							'belongsToMany' => "Combine multiple ".Str::plural($this->userInput['name']) ." with multiple ".Str::plural($form['target']),
						]
					);
				},name:'type')
				->confirm('Is the relation optional?', name:'nullable')
				->submit();

			$relation['slug'] = Str::slug($relation['target'],'_');

			array_push($this->userInput['relations'], $relation);

			$this->printRelationHeader();
		}
	}


	protected function getAvailableRelations() {

		$models = collect(scandir('app/Models/App/Base'));
		$models = $models->merge(collect(scandir('app/Models/App')));

		$models = $models->filter(fn($file) => Str::endsWith($file, '.php'))
			->map(fn($file) => Str::replaceLast('.php', '', $file));

		// remove existing relations
		foreach($this->userInput['relations'] as $relation) {
			$models = $models->filter(fn($model) => $model != $relation['target']);
		}

		$relations = [];
		foreach($models as $model) { $relations[$model] = $model; }

		return $relations;
	}


	protected function printRelationHeader() {

		clear();
		info('Relations');

		if(count($this->userInput['relations']) == 0) { return; }

		table(
			headers: ['target', 'type', 'nullable'],
			rows: collect($this->userInput['relations'])->map(function($relation) {
				return [
					$relation['target'],
					$relation['type'],
					$relation['nullable'] ? 'yes' : '',
				];
			}),
		);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end trait
