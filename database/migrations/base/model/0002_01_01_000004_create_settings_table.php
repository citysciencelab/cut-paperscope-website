<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// Laravel
	use Illuminate\Database\Schema\Blueprint;

	// App
	use Database\Migrations\Base\BaseMigration;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


return new class extends BaseMigration
{

	protected string $tableName = 'settings';

	protected array $roles = [
		'editor' => 	['create', 'edit', 'delete'],
		'admin' => 		['create', 'edit', 'delete'],
	];



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    UP
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function create(Blueprint &$table): void {

		$this->setDefaultPropsNoSlug($table);

		// setting properties
		$table->string('data_type',20);
		$table->string('category',50);
		$table->string('identifier',50);
		$table->string('reference',120);
		$table->text('content')->nullable();

		$this->translate($table, ['content']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}; // end class
