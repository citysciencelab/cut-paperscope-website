/// <reference types="cypress" />
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	TESTS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


describe('Edit Page model in backend', () => {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	EDIT PAGE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	it('overwrite name', () => {

		// arrange: got to list page
		cy.backendLogin();
		cy.visit('/backend/page');
		cy.wait(2000);

		// arrange: find cypress test page
		var pageName;
		var page = cy.get('.data-list-item[data-draggable] a').filter(':contains("Cypress Test-Page")').first();
		page.should(target => pageName = target.text().trim().replace(' - edited',''));
		page.click();
		cy.wait(2000);

		// act
		cy.get('#input-name').then(el => cy.get(el).clear().type(pageName + ' - edited'));
		cy.get('.btn-model-confirm').click();

		// assert
		cy.url().should('include', '/backend/page');
		cy.get('.data-list-item[data-draggable] a').then(e => cy.get(e).filter(':contains("'+pageName+' - edited")').should('exist'));
	});


	it('add new fragment',() => {

		const random = Math.random().toString(36).substring(7);

		// arrange: got to list page
		cy.backendLogin();
		cy.visit('/backend/page');
		cy.wait(2000);

		// arrange: find cypress test item
		var page = cy.get('.data-list-item[data-draggable] a').filter(':contains("Cypress Test-Page")').first();
		page.click();
		cy.wait(2000);

		// act: fragment edit page
		cy.get('a[href*="fragment/page"]').click();
		cy.wait(2000);
		cy.url().should('include', '/backend/fragment/page');

		// act: fill out form
		cy.get('#input-name').type('Cypress Test-Fragment ' + random);
		cy.get('#input-published-start').click();
		cy.wait(1000); // popup opening
		cy.get('.date-calendar-day.today').first().click();
		cy.get('.date-selector-confirm').click();
		cy.wait(1000); // popup closing
		cy.get('#input-public-0').check({force:true});
		// fragment template
		cy.get('#input-template').select('text', {force:true});
		cy.wait(1000); // ckeditor loading
		// template content
		cy.get('.ck-editor__editable').then(el => {
			const editor = el[0].ckeditorInstance;
			editor.setData('Cypress Test-Fragment Content');
		})
		cy.get('.btn-model-confirm').click();

		// assert: page edit
		cy.url().should('include', '/backend/page/edit');
		cy.get('.data-list-item').last().find('td').first().should(target => {
			expect(target).to.contain('Cypress Test-Fragment ' + random);
		});
	});



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


})
