/**
 * @file plugins/generic/doiInSummary/js/doiInSummary.js
 *
 * Copyright (c) 2015-2023 Lepidus Tecnologia
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Moves the DOI of each article summary right under the article title. Without
 * JavaScript the DOI stays at the end of the summary, where the core hook puts it.
 */
(function () {
	'use strict';

	function place() {
		var dois = document.querySelectorAll('.doiInSummary[data-doi-in-summary]');
		for (var i = 0; i < dois.length; i++) {
			var doi = dois[i];
			var summary = doi.closest('.obj_article_summary') || doi.parentNode;
			var title = summary ? summary.querySelector('.title, .article__title, h2, h3, h4') : null;
			if (title && title.parentNode && title.nextSibling !== doi) {
				title.parentNode.insertBefore(doi, title.nextSibling);
			}
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', place);
	} else {
		place();
	}
})();
