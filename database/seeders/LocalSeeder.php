<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	NCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Database\Seeders;

	// Laravel
	use Illuminate\Database\Seeder;
	use Illuminate\Database\Eloquent\Collection;
	use Illuminate\Database\Eloquent\Factories\Factory;

	// App
	use App\Models\App\Base\Item;
	use App\Models\App\Base\Page;
	use App\Models\App\Base\Fragment;
	use App\Models\Backend\Setting;
	use App\Models\Shop\Product;
	use App\Models\App\Project;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class LocalSeeder extends Seeder
{



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	RUN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function run(): void {

		$this->seedFactoryData(Item::factory(),'App/Base/ItemFactoryData.json');
		$this->seedFactoryData(Page::factory(),'App/Base/PageFactoryData.json');
		$this->seedFactoryData(Fragment::factory(),'App/Base/FragmentFactoryData.json');
		$this->seedFactoryData(Setting::factory(),'Backend/SettingFactoryData.json');

		if(config('app.features.shop')) {
			$this->seedFactoryData(Product::factory(),'Shop/ProductFactoryData.json');
		}
	
		$this->seedFactoryData(Project::factory(),'App/ProjectFactoryData.json');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FACTORY DATA
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * Seed model data from a json file instead of a factory.
	 *
	 * @param Factory $factory The factory for the model to get default values and merge with data.
	 * @param string $path The path to the json file in the factories folder.
	 */

	protected function seedFactoryData(Factory $factory, string $path): void {

		// load seeding data
		$file = file_get_contents(__DIR__.'/../factories/'.$path);
		$file = json_decode($file,true);

		// skip on error
		if($file==null) {
			echo  PHP_EOL . '### ERROR: Skipping seeding of '.$path.' - invalid data found.' . PHP_EOL;
			return;
		}

		// extract relations for each entry in data
		$relations = [];
		foreach($file['data'] as &$el) {
			if(isset($el['relations'])) {
				array_push($relations, $el['relations']);
				unset($el['relations']);
			}
			else {
				// entry without relations
				array_push($relations,false);
			}
		}

		// create models
		$len = count($file['data']);
		$models = $factory->count($len)->sequence(fn($sequence) => $file['data'][$sequence->index])->create();

		// force factory id with data id
		$models->each(function($item,$key) use ($file)  {
			$item->id = $file['data'][$key]['id'];
			$item->save();
		});

		// add relations to models
		$this->addRelationsToData($models,$relations);
	}


	/**
	 * Add relations from json data to the models.
	 *
	 * @param Collection $models The models to add the relations to.
	 * @param array $relations The relations to add to the models. Must be in the same order and length as the models.
	 */

	protected function addRelationsToData(Collection &$models, array &$relations): void {

		// iterate all models
		for($i=0; $i<count($models);$i++) {

			// skip if no relations
			if(!is_array($relations[$i])) { continue; }

			// iterate all relations from data
			foreach($relations[$i] as $key => $val) {
				$models[$i]->{$key}()->sync($val);
			}
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
