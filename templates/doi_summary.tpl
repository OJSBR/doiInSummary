{**
 * plugins/generic/doiInSummary/templates/doi_summary.tpl
 *
 * Copyright (c) 2015-2023 Lepidus Tecnologia
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * The DOI of an article in a summary. js/doiInSummary.js moves it under the title.
 *}
<div id="doi_article-{$doiInSummaryArticleId|escape}" class="doiInSummary" data-doi-in-summary="{$doiInSummaryArticleId|escape}">
    <strong>
        {capture assign=translatedDOI}{translate key="doi.readerDisplayName"}{/capture}
        {translate key="semicolon" label=$translatedDOI}
    </strong>
    <a href="{$doiInSummaryUrl|escape}" rel="noopener noreferrer">
        {$doiInSummaryUrl|escape}
    </a>
</div>
