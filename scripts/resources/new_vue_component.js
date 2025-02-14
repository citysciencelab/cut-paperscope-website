/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	import inquirer from 'inquirer';
	import fs from 'fs-extra';
	import Helper from '../helper.js';
	import slugify from 'slugify';

	import { fileURLToPath } from 'url';
	import { dirname } from 'path';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MAIN / PROMPT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const __filename = fileURLToPath(import.meta.url);
	const __dirname = dirname(__filename);


	/////////////////////////////////
	// USER INPUT
	/////////////////////////////////

	inquirer.prompt([
		{
			name: 'name',
			type: 'input',
			message: 'Name of Component: ',
		},
		{
			name: 'context',
			type: 'list',
			message: 'Which context? ',
			choices: [ "app", "backend"],
		},
		{
			name: 'pathComponent',
			type: 'input',
			message: 'Path of component in "resources/_ENV_/components": ',
		},
		{
			name: 'includeComposables',
			type: 'input',
			message: 'Include additional composables? (y/n):',
		},
		{
			name: 'composables',
			type: 'checkbox',
			message: 'Select composables:',
			choices: [ "useApi", "useForm", "useLanguage", "useUser"],
			when: (answers) => answers.includeComposables === 'y'
		},
	])
	.then(input => {
		execute(input);
		console.log(`\n\nNew component created: resources/js/${opts.context}/components/${opts.path}${opts.name}.vue`);
	})
	.catch(Helper.onInquirerError);



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	EXECUTE SCRIPT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const opts = {};

	function execute(input) {

		// get inquirer input
		opts.name			= Helper.capitalizeFirstLetter(input.name);
		opts.slug			= slugify(input.name.replace(/[A-Z]/g, '-$&'), {locale:'de', lower:true});
		opts.context 		= input.context;
		opts.path 			= input.pathComponent;
		opts.composables 	= input.composables;
		opts.includeSass 	= input.includeSass;

		// validate user input
		if(!opts.name) { return console.error('Aborting. Empty name for component.'); }

		// prepare correct path
		opts.path = Helper.validatePath(opts.path);

		createComponent();
		createTests();
		createSass();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPONENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	function createComponent() {

		// read template file
		let component = fs.readFileSync(__dirname + '/templates/TemplateComponent.vue', 'utf8');
		component = component.replaceAll('__SLUG__', opts.slug);
		component = component.replaceAll('__NAME__', opts.name);

		// add composables
		if(opts.composables) {

			opts.composables.forEach(composable => {
				component = component.replace('// [import composables]', `import { ${composable} } from '@global/composables/${composable}';\n\t\t// [import composables]`);
			});

			if(opts.composables.includes('useApi')) {
				const statement = 'const { apiGet } = useApi();';
				component = component.replace('// [init composables]', statement+'\n\t\t// [init composables]');
			}

			if(opts.composables.includes('useForm')) {
				const statement = 'const { form, errors, itemsBoolean, submitBtn, submitForm } = useForm();;';
				component = component.replace('// [init composables]', statement+'\n\t\t// [init composables]');
			}

			if(opts.composables.includes('useLanguage')) {
				const statement = 'const { t } = useLanguage();';
				component = component.replace('// [init composables]', statement+'\n\t\t// [init composables]');
			}

			if(opts.composables.includes('useUser')) {
				const statement = 'const { user } = useUser();';
				component = component.replace('// [init composables]', statement+'\n\t\t// [init composables]');
			}
		}
		component = component.replace('\n\t\t// [import composables]', '');
		component = component.replace('\n\t\t// [init composables]', '');

		// save new component file
		fs.outputFile(__dirname + `/../../resources/js/${opts.context}/components/${opts.path}${opts.name}.vue`, component, err => { if(err) {
			console.log('Unable to write file: ',err);
		}});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	TESTS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	function createTests() {

		let file = fs.readFileSync(__dirname + '/templates/TemplateComponentTest.test.js', 'utf8');
		file = file.replaceAll('__NAME__', opts.name);
		file = file.replaceAll('__PATH__', opts.path);
		file = file.replaceAll('__SLUG__', opts.slug);
		file = file.replaceAll('@global', opts.context=='app' ? '@app' : '@backend');

		fs.outputFile(__dirname + `/../../tests/Vitest/Unit/${opts.context}/components/${opts.path}${opts.name}.test.js`, file, err => { if(err) {
			console.log('Unable to write file: ',err);
		}});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SASS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	function createSass() {

		let file = fs.readFileSync(__dirname + '/templates/_template-component.scss', 'utf8');
		file = file.replaceAll('__COMMENT__', opts.slug.replace('-',' ').toUpperCase());
		file = file.replaceAll('__SLUG__', opts.slug);

		fs.outputFile(__dirname + `/../../resources/sass/${opts.context}/components/${opts.path}_${opts.slug}.scss`, file, err => { if(err) {
			console.log('Unable to write file: ',err);
		}});

		// append to entry file
		if(opts.context=='app') {
			fs.appendFileSync(__dirname + '/../../resources/sass/app/app.scss', `\n\t@import 'components/${opts.path}${opts.slug}';`);
		}
		else if(appEnv=='backend') {
			fs.appendFileSync(__dirname + '/../../resources/sass/backend/backend.scss', `\n\t@import 'components/${opts.path}${opts.slug}';`);
		}
	}


