/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const fs = require('fs');
	const path = require('path');
	const { defineConfig } = require("cypress");

	// get .env.testing or .env file for dotenv
	const envFile = fs.existsSync(path.resolve(__dirname,'../../.env.testing')) ? path.resolve(__dirname,'../../.env.testing') : path.resolve(__dirname,'../../.env');
	require('dotenv').config({ path: envFile })



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CONFIG
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


module.exports = defineConfig({


	projectId:  		process.env.CYPRESS_PROJECT_ID,

	fixturesFolder: 	"tests/Cypress/fixtures",
	screenshotsFolder: 	"tests/Cypress/screenshots",
	videosFolder: 		"tests/Cypress/videos",
	downloadsFolder: 	"tests/Cypress/downloads",

	e2e: {

		setupNodeEvents(on, config) {

			if(process.env.FEATURE_SHOP=='false') 			{ config.e2e.excludeSpecPattern.push("**/shop/*.js"); }
			if(process.env.FEATURE_APP_ACCOUNTS=='false') 	{ config.e2e.excludeSpecPattern.push("**/auth/*.js"); }
			if(process.env.FEATURE_BACKEND=='false') 		{ config.e2e.excludeSpecPattern.push("**/backend/*.js"); }

			on('task', {

				setTestingEnv() {
					if(fs.existsSync('../../.env.testing')) {
						fs.renameSync('../../.env', '../../.env.backup');
						fs.renameSync('../../.env.testing', '../../.env');
					}
					return null;
				},

				removeTestingEnv() {
					if(fs.existsSync('../../.env.backup')) {
						fs.renameSync('../../.env', '../../.env.testing');
						fs.renameSync('../../.env.backup', '../../.env');
					}
					return null;
				},

				setSharedData({ key, value }) {
					if(!global.sharedData) { global.sharedData = {}; }
					global.sharedData[key] = value;
					return null;
				},

				getSharedData(key) {
					return global.sharedData ? global.sharedData[key] : null;
				}
			});
		},

		env: {
			// Laravel settings
			ROOT_PASSWORD: 		process.env.ROOT_PASSWORD,
			ROOT_EMAIL: 		process.env.ROOT_EMAIL,
			MAILSLURP_API_KEY: 	process.env.MAILSLURP_API_KEY,
		},

		baseUrl: 		process.env.APP_URL,
		specPattern: 	"tests/Cypress/e2e/**/*.cy.{js,jsx,ts,tsx}",
		supportFile:	"tests/Cypress/support/e2e/e2e.js",
		experimentalRunAllSpecs: true,
	},



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


});
