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

	protected string $tableName = 'product_user';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    UP
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function create(Blueprint &$table): void {

		$table->bigIncrements('id');

		// relation
		$table->foreignUuid('product_id')->constrained();
		$table->foreignUuid('user_id')->constrained();
		$table->string('status',20)->info('pending | succeeded');
		$table->string('receipt',256)->nullable();

		// internal
		$table->index(['product_id','user_id']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}; // end class
