<?php

/**
 * @file plugins/generic/doiInSummary/tests/DoiInSummaryTest.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoiInSummaryTest
 *
 * @brief The resolving URL of a DOI, and the markup added to each summary.
 */

namespace APP\plugins\generic\doiInSummary\tests;

use APP\plugins\generic\doiInSummary\DoiInSummaryPlugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PKP\tests\PKPTestCase;

#[CoversClass(DoiInSummaryPlugin::class)]
class DoiInSummaryTest extends PKPTestCase
{
    public function testEveryFormOfADoiBecomesItsResolvingUrl(): void
    {
        $this->assertSame('https://doi.org/10.1234/abc.5', DoiInSummaryPlugin::resolvingUrl('10.1234/abc.5'));
        $this->assertSame('https://doi.org/10.1234/abc.5', DoiInSummaryPlugin::resolvingUrl(' doi:10.1234/abc.5 '));
        $this->assertSame('https://doi.org/10.1234/abc.5', DoiInSummaryPlugin::resolvingUrl('https://doi.org/10.1234/abc.5'));
        $this->assertSame(null, DoiInSummaryPlugin::resolvingUrl('   '));
    }

    public function testASummaryCarriesNoScriptOfItsOwn(): void
    {
        // One script file for the page, not one inline script per article.
        $template = (string) file_get_contents(dirname(__DIR__) . '/templates/doi_summary.tpl');
        $this->assertStringNotContainsString('<script', $template);
        $this->assertStringContainsString('{$doiInSummaryUrl|escape}', $template);
        $this->assertTrue(is_file(dirname(__DIR__) . '/js/doiInSummary.js'));
    }

    public function testTheDoiIsReadFromTheCurrentPublication(): void
    {
        $publication = new class () {
            public function getData($name)
            {
                return $name === 'doiObject' ? new class () {
                    public function getData($name)
                    {
                        return $name === 'doi' ? '10.5555/xyz' : null;
                    }
                } : null;
            }
        };
        $article = new class ($publication) {
            public function __construct(private $publication)
            {
            }

            public function getCurrentPublication()
            {
                return $this->publication;
            }
        };

        $this->assertSame('https://doi.org/10.5555/xyz', (new DoiInSummaryPlugin())->getArticleDoiUrl($article));
    }

    public function testTheAssetsAreOnlyAddedToReaderPages(): void
    {
        $templateMgr = new class () {
            public array $added = [];

            public function addStyleSheet($name, $url, $args = [])
            {
                $this->added[] = $name;
            }

            public function addJavaScript($name, $url, $args = [])
            {
                $this->added[] = $name;
            }
        };
        $plugin = new DoiInSummaryPlugin();

        $plugin->addDoiAssets('TemplateManager::display', [$templateMgr, 'management/settings/website.tpl']);
        $this->assertSame([], $templateMgr->added, 'Nothing on editorial pages.');

        $plugin->addDoiAssets('TemplateManager::display', [$templateMgr, 'frontend/pages/issue.tpl']);
        $this->assertSame(['doiInSummaryCSS', 'doiInSummaryJS'], $templateMgr->added);
    }
}
