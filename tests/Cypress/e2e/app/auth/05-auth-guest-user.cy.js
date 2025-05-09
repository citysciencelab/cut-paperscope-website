/// <reference types="cypress" />
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	TESTS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


describe('Guest user', () => {

	var userMail = 'tester-cypress@hello-nasty.com';
	var userPassword = 'HN-Password123';


	before(() => {

		cy.task('setTestingEnv');
		cy.refreshDatabase();
	});


	after(() => cy.task('removeTestingEnv'));



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	GUEST USER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	it('create new user account',()=>{

		// arrange
		cy.visit('/register');

		// act: fill out form
		cy.get('#input-email-register').type(userMail);
		cy.get('#input-username').type("max.muster");
		cy.get('#input-name').type("Max");
		cy.get('#input-surname').type("Mustermann");
		cy.get('#input-gender').select("m", {force:true});
		cy.get('#input-password-register').type(userPassword);
		cy.get('#input-password-confirmation').type(userPassword);
		cy.get('#input-terms-0').check({force:true});
		cy.get('.btn-register').click();

		// assert
		cy.wait(10 * 1000);
		cy.url().should('include', '/verify');
	});


	it('no access to user home page', () => {

		// act
		cy.appLogin(userMail, userPassword);

		// assert
		cy.wait(10 * 1000);
		cy.url().should('include', '/verify');
	});



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


})
