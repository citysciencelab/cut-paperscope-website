/// <reference types="cypress" />
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	TEST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


describe('Register new user', () => {

	var slurpId = '';
	var slurpAddress = '';


	before(() => {

		cy.task('setTestingEnv');
		cy.refreshDatabase();

		// look for existing mailslurp within 1 hour
		cy.task('getSharedData', 'slurpId').then(id => {

			// use existing mail address
			if(id) {
				slurpId = id;
				cy.task('getSharedData', 'slurpAddress').then(addr => slurpAddress = addr);
				return;
			}

			// create new mail address
			return cy.mailslurp()
			.then(mailslurp => mailslurp.createInbox())
			.then(inbox => {

				// check valid mail
				expect(inbox.emailAddress).to.contain("@mailslurp");

				// save inbox id and email address
				slurpId = inbox.id;
				slurpAddress = inbox.emailAddress;
				cy.task('setSharedData', { key: 'slurpId', value: slurpId});
				cy.task('setSharedData', { key: 'slurpAddress', value: slurpAddress});
			});
		});
	});


	after(() => {

		cy.task('removeTestingEnv');
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	REGISTER FORM
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	it('fill out form with missing required fields',()=>{

		// arrange
		cy.visit('/register');

		// act
		cy.get('#input-email-register').type(slurpAddress);
		cy.get('#input-username').type("max.muster");
		cy.get('#input-name').type("Max");
		// skip surname
		cy.get('#input-gender').select("m", {force:true});
		cy.get('#input-password-register').type("HN-Password123");
		cy.get('#input-terms-0').check({force:true});
		cy.get('.btn-register').click();

		// assert: error messages
		cy.get('p.error').should('to.exist');

		// assert: error styling
		cy.get('#input-surname').should('have.class','error');
		cy.get('label[for="input-surname"]').should('have.class','error');
		cy.get('#input-password-register').should('have.class','error');
		cy.get('label[for="input-password-register"]').should('have.class','error');
	});


	it('fill out form with correct data',()=>{

		// arrange
		cy.visit('/register');

		// act
		cy.get('#input-email-register').type(slurpAddress);
		cy.get('#input-username').type("max.muster");
		cy.get('#input-name').type("Max");
		cy.get('#input-surname').type("Mustermann");
		cy.get('#input-gender').select("m", {force:true});
		cy.get('#input-password-register').type("HN-Password123");
		cy.get('#input-password-confirmation').type("HN-Password123");
		cy.get('#input-terms-0').check({force:true});
		cy.get('.btn-register').click();

		// assert: redirect to verify page
		cy.url({timeout:120*1000}).should('include', '/verify');
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	DOUBLE OPTIN MAIL
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	it('confirm double optin mail',{defaultCommandTimeout: 120*1000},()=>{

		// arrange: get double optin mail
		cy.mailslurp()
		.then(mailslurp => mailslurp.waitForLatestEmail(slurpId, 120*1000, true))
		.then(email => {

			// get verify link from mail
			const regex = new RegExp('\>(' + 'http' + '.*)<\/a>');
			var results = regex.exec(email.body);
			results = results[1].replaceAll('&amp;','&')

			// assert: verification link
			expect(results).to.contain('verify');
			expect(results).to.contain('signature');

			// act: visit verify url
			cy.visit(results);

			// assert: user home after verification
			cy.url({timeout:5*1000}).should('include', '/home?verified=1');
			cy.contains('Max Mustermann');
		});
	});



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


})
