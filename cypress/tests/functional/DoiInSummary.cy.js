/**
 * @file cypress/tests/functional/DoiInSummary.cy.js
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Functional test: the DOI a reader sees in an issue table of contents.
 *
 * Parameters (--env): contextPath, issuePath (a published issue whose articles
 * have DOIs; default "issue/current"). The plugin must be enabled. No login,
 * nothing changed on the server.
 */

describe('DOI in Summary plugin', function() {
	const contextPath = Cypress.env('contextPath') || 'publicknowledge';
	const issuePath = Cypress.env('issuePath') || 'issue/current';

	it('Shows each DOI once, as a resolving link, right under the article title', function() {
		cy.visit('/index.php/' + contextPath + '/' + issuePath, {headers: {Cookie: 'OJSSID=cypress' + Date.now()}});
		cy.get('script[src*="/doiInSummary/js/doiInSummary.js"]').should('have.length', 1);
		cy.get('.doiInSummary script').should('have.length', 0);

		cy.get('.obj_article_summary .doiInSummary', {timeout: 30000}).should('have.length.at.least', 1).each(($doi) => {
			const summary = $doi.closest('.obj_article_summary');
			expect(summary.find('.doiInSummary')).to.have.length(1);
			expect($doi.find('a').attr('href')).to.match(/^https:\/\/doi\.org\/10\.\d{4,}\//);
			expect($doi.find('a').text().trim()).to.eq($doi.find('a').attr('href'));
			// Moved right after the title.
			expect($doi.prev().is('.title, .article__title, h2, h3, h4')).to.eq(true);
		});
	});
});
