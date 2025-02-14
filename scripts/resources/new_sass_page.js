/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	import inquirer from 'inquirer';
	import fs from 'fs-extra';
	import Helper from '../helper.js';

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
			message: 'Name of page (without _page-): ',
		},
		{
			name: 'comment',
			type: 'input',
			message: 'Label of comment section: ',
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
			message: 'Path of page in "resources/sass/_env_/pages/": ',
		},
  	])
  	.then(input => {
		execute(input);
		console.log(`\n\nNew page created: resources/sass/${opts.context}/pages/${opts.path}_page-${opts.name}.scss`);
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
		opts.name			= input.name;
		opts.comment		= input.comment;
		opts.context 		= input.context;
		opts.path 			= input.pathPage;

		// validate user input
		if(!opts.name) 	{ return console.error('Aborting. Empty name for page.'); }
		opts.name = opts.name.toLowerCase();

		// prepare correct path
		opts.path = Helper.validatePath(opts.path);

		createPage();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	PAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	function createPage() {

		// set uppercase comment
		const comment = (opts.comment ? opts.comment.toUpperCase() : opts.name.toUpperCase()).replaceAll('-', ' ');

		// read template file
		var component = fs.readFileSync(__dirname + '/templates/_template-page.scss', 'utf8');
		component = component.replace('__COMMENT__', comment);
		component = component.replace('__SLUG__', opts.name);

		// save new component
		fs.outputFile(__dirname + `/../../resources/sass/${opts.context}/pages/${opts.path}_page-${opts.name}.scss`, component, err => { if(err) {
			console.log('Unable to write file: ',err);
		}});

		// append to entry file
		if(opts.context=='app') {
			fs.appendFileSync(__dirname + '/../../resources/sass/app/app.scss', `\n\t@import 'pages/${opts.path}page-${opts.name}';`);
		}
		else if(appEnv=='backend') {
			fs.appendFileSync(__dirname + '/../../resources/sass/backend/backend.scss', `\n\t@import 'pages/${opts.path}page-${opts.name}';`);
		}
	}
