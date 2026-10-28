{**
 * plugins/generic/doiInSummary/templates/doi_summary.tpl
 *
 * Copyright (c) 2015-2023 Lepidus Tecnologia
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * The DOI of an article, preprint or book in a list. js/doiInSummary.js moves the DOI of
 * an article under its title and the DOI of a preprint after its authors.
 *}
<div{if $doiInSummaryElementId} id="{$doiInSummaryElementId|escape}"{/if} class="{$doiInSummaryClass|escape}" data-doi-in-summary="{$doiInSummarySubmissionId|escape}">
    <strong>
        {capture assign=translatedDOI}{translate key="doi.readerDisplayName"}{/capture}
        {* The label of OMP already ends with a colon, the one of OJS and OPS does not. *}
        {translate key="semicolon" label=$translatedDOI|trim|regex_replace:"/\s*[:：]$/u":""}
    </strong>
    <a href="{$doiInSummaryUrl|escape}" rel="noopener noreferrer">
        {$doiInSummaryUrl|escape}
    </a>
</div>
