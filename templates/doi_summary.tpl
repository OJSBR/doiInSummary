<div id="doi_article-{$doiInSummaryArticleId|escape}" class="doiInSummary" data-doi-in-summary="{$doiInSummaryArticleId|escape}">
    <strong>
        {capture assign=translatedDOI}{translate key="doi.readerDisplayName"}{/capture}
        {translate key="semicolon" label=$translatedDOI}
    </strong>
    <a href="{$doiInSummaryUrl|escape}" rel="noopener noreferrer">
        {$doiInSummaryUrl|escape}
    </a>
</div>

<script>
(function () {ldelim}
    var doiDiv = document.getElementById('doi_article-{$doiInSummaryArticleId|escape:javascript}');
    if (!doiDiv || !doiDiv.parentNode) {ldelim}
        return;
    {rdelim}

    var articleSummary = doiDiv.closest('.obj_article_summary') || doiDiv.parentNode;
    var title = articleSummary.querySelector('.title, .article__title, h2, h3, h4');

    if (title && title.parentNode) {ldelim}
        title.parentNode.insertBefore(doiDiv, title.nextSibling);
    {rdelim}
{rdelim}());
</script>
