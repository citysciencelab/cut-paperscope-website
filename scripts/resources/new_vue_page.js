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
			message: 'Name of Page (without Page): ',
		},
		{
			name: 'metaTitle',
			type: 'input',
			message: 'Page title for meta: ',
		},
		{
			name: 'context',
			type: 'list',
			message: 'Which context? ',
			choices: [ "app", "backend"],
		},
		{
			name: 'pathPage',
			type: 'input',
			message: 'Path of page in "resources/_ENV_/pages": ',
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
		{
			name: 'includeSass',
			type: 'input',
			message: 'Create sass file? (y/n):',
		},
	])
	.then(input => {
		execute(input);
		console.log(`\n\nNew page created: resources/js/${opts.context}/pages/${opts.path}Page${opts.name}.vue`);
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
		opts.metaTitle 		= input.metaTitle;
		opts.context 		= input.context;
		opts.path 			= input.pathPage;
		opts.composables 	= input.composables;
		opts.includeSass 	= input.includeSass;

		// validate user input
		if(!opts.name) { return console.error('Aborting. Empty name for page.'); }
		if(opts.name.startsWith('Page')) { opts.name = opts.name.substring(0,4); }

		// prepare correct path
		opts.path = Helper.validatePath(opts.path);

		createPage();
		updateRouter();
		createTests();
		if(opts.includeSass == 'y') { createSass(); }
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	PAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	function createPage() {

		// read template file
		let page = fs.readFileSync(__dirname + '/templates/TemplatePage.vue', 'utf8');
		page = page.replaceAll('__NAME__', opts.name);
		page = page.replaceAll('__META_TITLE__', opts.metaTitle);

		// add composables
		if(opts.composables) {

			opts.composables.forEach(composable => {
				page = page.replace('// [import composables]', `import { ${composable} } from '@global/composables/${composable}';\n\t\t// [import composables]`);
			});

			if(opts.composables.includes('useApi')) {
				const statement = 'const { apiGet } = useApi();';
				page = page.replace('// [init composables]', statement+'\n\t\t// [init composables]');
			}

			if(opts.composables.includes('useForm')) {
				const statement = 'const { form, errors, itemsBoolean, submitBtn, submitForm } = useForm();;';
				page = page.replace('// [init composables]', statement+'\n\t\t// [init composables]');
			}

			if(opts.composables.includes('useLanguage')) {
				const statement = 'const { langs } = useLanguage();';
				page = page.replace('// [init composables]', statement+'\n\t\t// [init composables]');
			}

			if(opts.composables.includes('useUser')) {
				const statement = 'const { user } = useUser();';
				page = page.replace('// [init composables]', statement+'\n\t\t// [init composables]');
			}
		}
		page = page.replace('\n\t\t// [import composables]', '');
		page = page.replace('\n\t\t// [init composables]', '');

		// save new page file
		fs.outputFile(__dirname + `/../../resources/js/${opts.context}/pages/${opts.path}Page${opts.name}.vue`, page, err => { if(err) {
			console.log('Unable to write file: ',err);
		}});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	ROUTER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	function updateRouter() {

		// read router file
		var routerPath = __dirname + '/../../resources/js/' + opts.context  + (opts.context == 'backend' ? '/BackendRouter.js' : '/AppRouter.js');
		var file = fs.readFileSync(routerPath, 'utf8');

		// update router
		var replace = "// "+opts.slug;
		replace += `\n\tconst Page${opts.name} = () => import('./pages/${opts.path}Page${opts.name}.vue');`;
		replace += "\n\n\t// [add model includes]"
		file = file.replace(/\/\/ \[add model includes\]/gm,replace);

		// save router file
		fs.outputFile(routerPath, file, err => { if(err) {
			console.log('Unable to write router file: ',err);
		}});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	TESTS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	function createTests() {

		let file = fs.readFileSync(__dirname + '/templates/TemplatePageTest.test.js', 'utf8');
		file = file.replaceAll('__NAME__', opts.name);
		file = file.replaceAll('__PATH__', opts.path);
		file = file.replaceAll('@global', opts.context=='app' ? '@app' : '@backend');

		fs.outputFile(__dirname + `/../../tests/Vitest/Unit/${opts.context}/pages/${opts.path}Page${opts.name}.test.js`, file, err => { if(err) {
			console.log('Unable to write file: ',err);
		}});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SASS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	function createSass() {

		let file = fs.readFileSync(__dirname + '/templates/_template-page.scss', 'utf8');
		file = file.replaceAll('__COMMENT__', opts.name.toUpperCase());
		file = file.replaceAll('__SLUG__', opts.slug);

		fs.outputFile(__dirname + `/../../resources/sass/${opts.context}/pages/${opts.path}_page-${opts.slug}.scss`, file, err => { if(err) {
			console.log('Unable to write file: ',err);
		}});
	}


