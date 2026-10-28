/**
 * @file cypress/tests/functional/DoiInSummary.cy.js
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Functional tests on OJS, OPS and OMP: published submissions are given a DOI through the
 * REST API, the list a reader sees shows no DOI while the plugin is off, and once it is on
 * shows each DOI once, as the resolving link of the DOI the API reports, in its place.
 *
 * Parameters (--env): contextPath, adminUser, adminPassword (captcha on login must be off
 * for the run), doiPrefix (used only when the context has none; default 10.99999), listPath
 * (the page with the list; default issue/current in OJS, the home page in OPS, catalog in
 * OMP). The defaults match the data set of PKP's continuous integration, where the plugin
 * starts off: there the list is checked without the plugin before it is enabled.
 */

describe('DOI in Summary plugin', function() {
	const contextPath = Cypress.env('contextPath') || 'publicknowledge';
	const adminUser = Cypress.env('adminUser') || 'admin';
	const adminPassword = Cypress.env('adminPassword') || 'admin';
	const doiPrefix = Cypress.env('doiPrefix') || '10.99999';

	// What each application lists, and where the plugin puts the DOI in each summary.
	const applications = {
		ojs: {listPath: 'issue/current', summary: '.obj_article_summary'},
		ops: {listPath: '', summary: '.obj_preprint_summary'},
		omp: {listPath: 'catalog', summary: '.obj_monograph_summary'},
	};

	// Filled by the first test: the running application, whether the plugin was off, and the
	// DOI the API reports for each published submission (by submission id).
	let application = null;
	let pluginWasOff = false;
	const dois = {};

	// ---- OJSBR spec helpers (padrão v2): work on OJS/OMP/OPS 3.3, 3.4 and 3.5 and in PKP's CI ----

	const pageUrl = (path) => '/index.php/' + contextPath + (path ? '/' + path : '');

	// Same as PKP's cy.waitJQuery(), which the support files of OJS 3.3 test sites may lack.
	// The Plugins tab can keep requests open for a while (the plugin gallery), hence the timeout.
	const waitJQuery = () => cy.window().its('jQuery.active', {timeout: 60000}).should('eq', 0);

	// Requests carry the browser's User-Agent: OJS 3.3 drops a session whose agent changes.
	const request = (options) => cy.window({log: false}).then((win) => cy.request(Object.assign(
		typeof options === 'string' ? {url: options} : options,
		{headers: Object.assign({'User-Agent': win.navigator.userAgent}, (typeof options === 'string' ? {} : options.headers) || {})}
	)));

	// Signs in through requests (the login page can re-render while it is typed into), then
	// falls back to the form when the session did not stick (OJS 3.3 cookie handling).
	const login = (username, password) => {
		cy.clearCookies();
		request(pageUrl('login')).then((response) => {
			const token = /name="csrfToken" value="([^"]+)"/.exec(response.body)[1];
			// The form posts to the URL with the language: a redirect would turn the POST into a GET.
			const action = /<form[^>]*id="login"[^>]*action="([^"]+)"/.exec(response.body)[1];
			request({method: 'POST', url: action, form: true, body: {csrfToken: token, username: username, password: password}, log: false});
		});
		cy.visit(pageUrl('submissions') + '?reload=' + Date.now());
		cy.get('body').then(($body) => {
			if ($body.find('form#login').length) {
				cy.get('form#login input[name="username"]').type(username, {delay: 0});
				cy.get('form#login input[name="password"]').type(password, {delay: 0, log: false});
				cy.get('form#login').submit();
				cy.get('form#login', {timeout: 30000}).should('not.exist');
			}
		});
	};

	// REST API calls made from the page itself, so they carry the browser's own session.
	const api = (path, options = {}) => cy.window({log: false}).then((win) => cy.wrap(
		win.fetch(path, Object.assign({credentials: 'same-origin'}, options)).then((response) => {
			if (!response.ok) {
				return response.text().then((text) => {
					throw new Error(path + ' answered ' + response.status + ': ' + text.slice(0, 300));
				});
			}
			return response.json();
		}),
		{log: false, timeout: 30000}
	));

	// The website settings page on its Plugins tab (a new query string forces a load). Load it
	// once per test: loading it again while its plugin gallery request is pending stalls the
	// web server of PKP's CI; API calls and settings modals work on the page already open.
	const openPluginsTab = () => {
		cy.visit(pageUrl('management/settings/website') + '?reload=' + Date.now() + '#plugins');
		cy.get('button[id="plugins-button"]', {timeout: 60000}).click();
		cy.get('button[id="plugins-button"]').should('have.attr', 'aria-selected', 'true');
		waitJQuery();
	};

	// Enables the plugin in the grid when it is off (never turns it off).
	const enablePlugin = (rowName) => {
		cy.get('input[id^="select-cell-' + rowName + '-enabled"]', {timeout: 30000}).then(($checkbox) => {
			if (!$checkbox.is(':checked')) {
				cy.wrap($checkbox).click();
				waitJQuery();
			}
		});
		cy.get('input[id^="select-cell-' + rowName + '-enabled"]').should('be.checked');
	};

	// Opens the settings modal from the grid, without reloading the page: a reload right
	// after saving can stall the web server of PKP's CI. The form is fetched each time.
	const openPluginSettings = (rowName, formSelector) => {
		cy.get('a[id*="-row-' + rowName + '-settings-button-"]', {timeout: 30000}).then(($link) => {
			if (!$link.is(':visible')) {
				cy.get('tr[id$="-row-' + rowName + '"] a.show_extras').first().click();
			}
		});
		// The grid may still be animating the extras row: the link is clicked once it exists.
		cy.get('a[id*="-row-' + rowName + '-settings-button-"]').first().click({force: true});
		waitJQuery();
		cy.window().should((win) => {
			expect(win.jQuery(formSelector).data('pkp.handler')).to.exist;
		});
	};

	// ---- end of helpers ----

	const apiUrl = (path) => pageUrl('api/v1/' + path);

	// Writes through the REST API carry the CSRF token of the page.
	const apiWrite = (method, path, body) => cy.window({log: false}).then((win) => api(apiUrl(path), {
		method: method,
		headers: {'Content-Type': 'application/json', 'X-Csrf-Token': win.pkp.currentUser.csrfToken},
		body: JSON.stringify(body),
	}));

	const listUrl = () => pageUrl(Cypress.env('listPath') ?? applications[application].listPath);

	// The list as an anonymous reader sees it, never a cached copy.
	const visitListAsReader = () => {
		cy.clearCookies();
		cy.visit(listUrl() + (listUrl().includes('?') ? '&' : '?') + 'reload=' + Date.now());
		cy.get(applications[application].summary, {timeout: 30000}).should('have.length.at.least', 1);
	};

	it('Gives the published submissions a DOI', function() {
		request(pageUrl('')).then((response) => {
			const generator = /<meta name="generator" content="([^"]+)"/.exec(response.body);
			const name = generator ? generator[1] : '';
			application = /Monograph/i.test(name) ? 'omp' : (/Preprint/i.test(name) ? 'ops' : 'ojs');
			cy.log('Application: ' + application + ' (' + name + ')');
		});

		login(adminUser, adminPassword);
		openPluginsTab();
		cy.get('input[id^="select-cell-doiinsummaryplugin-enabled"]', {timeout: 30000}).then(($checkbox) => {
			pluginWasOff = !$checkbox.is(':checked');
		});

		// DOIs on, for publications, with the prefix of the context or the test one.
		api(apiUrl('contexts')).then((contexts) => {
			const context = contexts.items.find((item) => item.urlPath === contextPath);
			expect(context, 'context ' + contextPath).to.exist;
			api(apiUrl('contexts/' + context.id)).then((settings) => {
				const types = settings.enabledDoiTypes || [];
				apiWrite('PUT', 'contexts/' + context.id, {
					enableDois: true,
					doiPrefix: settings.doiPrefix || doiPrefix,
					enabledDoiTypes: types.includes('publication') ? types : types.concat(['publication']),
				});
			});
		});

		// Published submissions (status 3) without a DOI get one; then every DOI is read back.
		api(apiUrl('submissions?status=3&count=100')).then((published) => {
			expect(published.items, 'published submissions').to.have.length.at.least(1);
			const current = (submission) => submission.publications.find((publication) => publication.id === submission.currentPublicationId);
			const withoutDoi = published.items.filter((submission) => !current(submission).doiObject).map((submission) => submission.id);
			if (withoutDoi.length) {
				apiWrite('POST', 'dois/submissions/assignDois', {ids: withoutDoi});
			}
			api(apiUrl('submissions?status=3&count=100')).then((reloaded) => {
				reloaded.items.forEach((submission) => {
					const doi = current(submission).doiObject;
					expect(doi && doi.doi, 'DOI of submission ' + submission.id).to.match(/^10\.\d{4,}\//);
					dois[submission.id] = doi.doi;
				});
			});
		});
	});

	it('Shows no DOI in the list while the plugin is off', function() {
		if (!pluginWasOff) {
			cy.log('The plugin was already on: nothing to compare with.');
			return;
		}
		visitListAsReader();
		cy.get('.doiInSummary').should('have.length', 0);
		cy.get('script[src*="/doiInSummary/js/doiInSummary.js"]').should('have.length', 0);
	});

	it('Enables the plugin', function() {
		login(adminUser, adminPassword);
		openPluginsTab();
		enablePlugin('doiinsummaryplugin');
	});

	it('Shows each DOI once, as its resolving link, in its place in the summary', function() {
		const summary = applications[application].summary;
		visitListAsReader();
		cy.get('script[src*="/doiInSummary/js/doiInSummary.js"]').should('have.length', 1);
		cy.get('.doiInSummary script').should('have.length', 0);

		cy.get(summary + ' .doiInSummary', {timeout: 30000}).should('have.length.at.least', 1).each(($doi) => {
			const $summary = $doi.closest(summary);
			expect($summary.find('.doiInSummary')).to.have.length(1);

			const submissionId = $doi.attr('data-doi-in-summary');
			expect(dois[submissionId], 'DOI of submission ' + submissionId + ' in the API').to.be.a('string');
			const $link = $doi.find('a');
			expect($link.attr('href')).to.eq('https://doi.org/' + dois[submissionId]);
			expect($link.text().trim()).to.eq($link.attr('href'));
			// One colon after the label, whichever application's label is used.
			expect($doi.find('strong').text().trim()).to.match(/^DOI\s?:$/);

			if (application === 'ojs') {
				// Right under the title (text for screen readers only may sit in between).
				expect($doi.prevAll(':visible').first().is('.title, .article__title, h2, h3, h4')).to.eq(true);
			} else if (application === 'ops') {
				// In the metadata, right after the authors (or first when they are hidden).
				expect($doi.parent().is('.meta')).to.eq(true);
				expect($doi.prev().length === 0 || $doi.prev().is('.authors')).to.eq(true);
			} else {
				// Right after the authors, or after the title when the theme shows none.
				expect($doi.prev().is('.author, h2, h3, h4')).to.eq(true);
			}
		});

		// Every published submission has a DOI now, so every summary shows one.
		cy.get(summary).then(($summaries) => {
			cy.get(summary + ' .doiInSummary').should('have.length', $summaries.length);
		});
	});
});
