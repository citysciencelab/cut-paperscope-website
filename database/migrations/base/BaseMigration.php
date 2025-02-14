<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Database\Migrations\Base;

	// Laravel
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Support\Facades\Schema;
	use Spatie\Permission\Models\Role;
	use Spatie\Permission\Models\Permission;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


abstract class BaseMigration extends Migration
{

	/**
	 * Define the table name.
	 */
	 protected string $tableName = '';


	/**
	 * Define roles and permissions for a Laravel model.
	 *
	 * The key is the role name and the value is an array of permissions (without model name).
	 *
	 * Example:
	 * $roles = ['editor' => ['create', 'edit', 'delete'] ];
	 */
	protected array $roles = [];


	public function __construct() {

		// Reset cached roles and permissions
		app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    UP
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function up(): void {

		Schema::create($this->tableName, function(Blueprint $table) {
			$this->create($table);
			$table->timestamps();
		});
		$this->addPermissionsAndRoles();
	}


	/**
	 * Define all columns for the table via the Laravel Blueprint variable.
	 *
	 * Permissions and roles are added automatically.
	 *
	 * @param Blueprint &$table Reference to the Laravel Blueprint variable
	 */

	abstract public function create(Blueprint &$table): void;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    DOWN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function down(): void {

		$this->removePermissionsAndRoles();
		Schema::dropIfExists($this->tableName);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    DEFAULT PROPERTIES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * Every model should have the same default properties.
	 *
	 * Include this method in the create() method of the child class.
	 *
	 * @param Blueprint &$table Reference to the Laravel Blueprint variable
	 */

	protected function setDefaultProps(Blueprint &$table): void {

		$table->uuid('id')->primary();
		$table->string('name',50);
		$table->string('slug',100)->unique();
		$table->boolean('public')->default(false);
		$table->integer('order')->default(0);
	}


	/**
	 * Every model should have the same default properties but use this model without a unique slug.
	 *
	 * Include this method in the create() method of the child class.
	 *
	 * @param Blueprint &$table Reference to the Laravel Blueprint variable
	 */

	protected function setDefaultPropsNoSlug(Blueprint &$table): void {

		$this->setDefaultProps($table);
		$table->removeColumn('slug');
	}


	/**
	 * Add this properties if you want to control a datetime based visibility/duration for this model via cms.
	 *
	 * Include this method in the create() method of the child class.
	 *
	 * @param Blueprint &$table Reference to the Laravel Blueprint variable
	 */

	protected function setPublishedProps(Blueprint &$table): void {

		$table->dateTime('published_start')->nullable();
		$table->dateTime('published_end')->nullable();
	}


	/**
	 * Add this properties if this model should be handled as page in cms.
	 *
	 * Include this method in the create() method of the child class.
	 *
	 * @param Blueprint &$table Reference to the Laravel Blueprint variable
	 */

	protected function setPageProps(Blueprint &$table): void {

		$table->boolean('navi_visible')->default(true);
		$table->string('navi_label',30)->nullable();

		$this->setSharingProps($table);

		$this->translate($table, ['navi_label']);
	}


	/**
	 * Add this properties if this model should support social sharing.
	 *
	 * Include this method in the create() method of the child class.
	 *
	 * @param Blueprint &$table Reference to the Laravel Blueprint variable
	 */

	protected function setSharingProps(Blueprint &$table): void {

		$table->string('meta_title',30)->nullable();
		$table->string('meta_description',160)->nullable();
		$table->string('social_description',297)->nullable();
		$table->string('social_image',256)->nullable();

		$this->translate($table, ['meta_title', 'meta_description', 'social_description', 'social_image']);
	}


	/**
	 * Add this properties if this model should be handled as fragment in cms.
	 *
	 * Include this method in the create() method of the child class.
	 *
	 * @param Blueprint &$table Reference to the Laravel Blueprint variable
	 */

	protected function setFragmentProps(Blueprint &$table): void {

		$table->uuid('parent_id');
		$table->string('parent_type',40);

		$table->index(['parent_id']);
	}


	/**
	 * Add this properties if this model should be handled as product synced with stripe payment in cms.
	 *
	 * Include this method in the create() method of the child class.
	 *
	 * @param Blueprint &$table Reference to the Laravel Blueprint variable
	 */

	protected function setStripeProps(Blueprint &$table): void {

		$table->string('stripe_id',50)->unique();
		$table->boolean('stripe_synced')->default(false);

		// product properties
		$table->string('stripe_name',256)->nullable();
		$table->string('stripe_description',256)->nullable();

		// price properties
		$table->string('stripe_price_id',50)->nullable();
		$table->string('stripe_price_value',10)->nullable();
		$table->string('stripe_price_amount',10)->nullable();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    PERMISSIONS / ROLES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * Automatically add permissions and roles to the database.
	 *
	 * If rules or permissions already exist, they will be updated.
	 */

	protected function addPermissionsAndRoles(): void {

		if(!count($this->roles)) { return; }

		// create permissions
		Permission::findOrCreate('create ' . $this->tableName);
		Permission::findOrCreate('edit ' . $this->tableName);
		Permission::findOrCreate('delete ' . $this->tableName);

		// update roles
		foreach($this->roles as $roleName => $permissions) {

			/** @var Spatie\Permission\Models\Role $role **/
			$role = Role::findOrCreate($roleName);

			foreach($permissions as $permission) {
				$role->givePermissionTo(trim($permission) . ' ' . $this->tableName);
			}
		}
	}


	/**
	 * Automatically remove permissions and roles from the database.
	 */

	protected function removePermissionsAndRoles() {

		// update roles
		foreach($this->roles as $role => $permissions) {

			$role = Role::findByName($role);
			if(!$role) { continue; }

			foreach($permissions as $permission) { $role->revokePermissionTo(trim($permission) . ' ' . $this->tableName); }
		}

		if($this->tableName =='') { return; }

		// remove permissions (findByName returns an exception if not found)
		try {
			/** @var Spatie\Permission\Models\Permission $create **/
			$create = Permission::findByName('create ' . $this->tableName);
			$create->delete();

			/** @var Spatie\Permission\Models\Permission $edit **/
			$edit = Permission::findByName('edit ' . $this->tableName);
			$edit->delete();

			/** @var Spatie\Permission\Models\Permission $delete **/
			$delete = Permission::findByName('delete ' . $this->tableName);
			$delete->delete();
		}
		catch(\Exception $e) {
			return;
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    TRANSLATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * Automatically add translated columns to the database.
	 *
	 * The fallback language may be required, all other languages are nullable. If the multi_lang feature is disabled, column name will not be changed.
	 * Example: column 'title' will be translated to 'title_de', 'title_en', ...
	 *
	 * @param Blueprint &$table Reference to the Laravel Blueprint variable
	 * @param string[] $columns Array of column names to translate
	 */

	public function translate(Blueprint &$table, array $columns): void {

		if(!config('app.features.multi_lang')) { return; }

		$langs = config('app.available_locales');
		$fallbackLang = config('app.fallback_locale');

		// remove all cols and create new order with translated columns
		$newCols = [];
		foreach($table->getColumns() as $column) {

			// find column to translate
			if(in_array($column['name'],$columns)) {

				// add translated columns
				foreach($langs as $lang) {
					$col = $column->toArray();
					$col['name'] .= '_' . $lang;
					if($lang != $fallbackLang) { $col['nullable'] = true; }
					array_push($newCols, $col);
				}
			}
			else {
				array_push($newCols, $column->toArray());
			}

			$table->removeColumn($column['name']);
		}

		// add all columns with new order
		foreach($newCols as $column) {
			$table->addColumn($column['type'], $column['name'], $column);
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
