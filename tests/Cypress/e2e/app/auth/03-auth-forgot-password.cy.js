/// <reference types="cypress" />
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	TESTS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


describe('User forgot password', () => {

	var slurpId = '';
	var slurpAddress = '';


	before(() => {

		cy.task('setTestingEnv')

		cy.task('getSharedData', 'slurpId').then(id => {

			if(id) {
				slurpId = id;
				cy.task('getSharedData', 'slurpAddress').then(addr => slurpAddress = addr);
				return;
			}
		});
	});


	after(() => {

		cy.task('removeTestingEnv');
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FORGOT PASSWORD FORM
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	it('access from login page and fill with valid data', ()=>{

		// arrange: forgot password page
		cy.visit('/login')
		cy.get('.btn-forgot').click();
		cy.url().should('include', '/password/forgot');

		// act
		cy.get('#input-email-forgot').type(slurpAddress ?? Cypress.env('ROOT_EMAIL'));
		cy.get('.btn-forgot').click();

		// assert
		cy.get('.forgot-password-success',{timeout:120*1000}).should('exist');
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	RESET PASSWORD MAIL
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	it('confirm password forgot mail',{defaultCommandTimeout: 120*1000},()=>{

		if(!slurpAddress) { return; }

		cy.task('getSharedData', 'slurpId').then(slurpId => {

			// arrange: get reset password mail
			cy.mailslurp()
			.then(mailslurp => mailslurp.waitForLatestEmail(slurpId, 120*1000, true))
			.then(email => {

				// get reset link from mail
				const regex = new RegExp('\>(' + 'http' + '.*)<\/a>');
				var results = regex.exec(email.body);
				results = results[1].replaceAll('&amp;','&')

				// act: visit reset link
				cy.visit(results);
				cy.url({timeout:5*1000}).should('include', '/password/reset?token=');

				// act: fill out reset form
				cy.get('#input-password-reset').type("HN-newPassword123");
				cy.get('#input-password-confirmation').type("HN-newPassword123");
				cy.get('.btn-reset').click();

				// assert
				cy.get('.reset-password-success',{timeout:120*1000}).should('exist');
			});
		});
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	RESET PASSWORD
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	it('login with new password if mailslurp',()=>{

		if(!slurpAddress) { return; }

		// act
		cy.appLogin(slurpAddress, "HN-newPassword123");

		// assert
		cy.url({timeout:20*1000}).should('include', '/home');
	});




/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


})
