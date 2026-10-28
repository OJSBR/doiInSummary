<?php

/**
 * @file plugins/generic/doiInSummary/tests/DoiInSummaryTest.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoiInSummaryTest
 *
 * @brief The resolving URL of a DOI, the DOI read from a submission, the hook of each
 *        application, and the DOI added to the book summaries of OMP.
 */

namespace APP\plugins\generic\doiInSummary\tests;

use APP\core\Application;
use APP\plugins\generic\doiInSummary\DoiInSummaryPlugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PKP\core\Core;
use PKP\core\VirtualArrayIterator;
use PKP\tests\PKPTestCase;

#[CoversClass(DoiInSummaryPlugin::class)]
class DoiInSummaryTest extends PKPTestCase
{
    /** A submission as the plugin reads it, with or without a DOI. */
    protected function submission(int $id, ?string $doi, ?string $urlPath = null): object
    {
        $publication = new class ($doi) {
            public function __construct(private ?string $doi)
            {
            }

            public function getData($name)
            {
                if ($name !== 'doiObject' || $this->doi === null) {
                    return null;
                }
                return new class ($this->doi) {
                    public function __construct(private string $doi)
                    {
                    }

                    public function getData($name)
                    {
                        return $name === 'doi' ? $this->doi : null;
                    }
                };
            }

            public function getStoredPubId($type)
            {
                return null;
            }
        };

        return new class ($id, $publication, $urlPath) {
            public function __construct(private int $id, private object $publication, private ?string $urlPath)
            {
            }

            public function getId()
            {
                return $this->id;
            }

            public function getBestId()
            {
                return $this->urlPath ?? (string) $this->id;
            }

            public function getCurrentPublication()
            {
                return $this->publication;
            }
        };
    }

    /** A book summary as monograph_summary.tpl of the default theme renders it. */
    protected function bookSummary(string $bestId, string $title, bool $withAuthors = true): string
    {
        $url = 'https://press.example/index.php/press/catalog/book/' . $bestId;
        return '<div class="obj_monograph_summary">' . "\n"
            . '<a href="' . $url . '" class="cover"><img src="cover.png" alt=""></a>' . "\n"
            . '<h2 class="title"><a href="' . $url . '">' . $title . '</a></h2>' . "\n"
            . ($withAuthors ? '<div class="author">Ana Silva, Bruno Costa</div>' . "\n" : '')
            . '<div class="date">October 1, 2026</div>' . "\n"
            . '</div><!-- .obj_monograph_summary -->' . "\n";
    }

    protected function render(): callable
    {
        return fn (string $doiUrl, int $id) => '<div class="doiInSummary" data-doi-in-summary="' . $id . '"><a href="' . $doiUrl . '">' . $doiUrl . '</a></div>';
    }

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
        $plugin = new DoiInSummaryPlugin();
        $this->assertSame('https://doi.org/10.5555/xyz', $plugin->getDoiUrl($this->submission(7, '10.5555/xyz')));
        $this->assertSame(null, $plugin->getDoiUrl($this->submission(8, null)), 'A submission without DOI shows nothing.');
    }

    public function testTheHookOfThisApplicationIsInItsSummaryTemplate(): void
    {
        // Each application ends its summary with its own hook, and OMP has none: the plugin
        // relies on exactly what the summary template of the running application offers.
        $application = Application::get()->getName();
        $base = Core::getBaseDir() . '/templates/frontend/objects/';
        $templates = ['ojs2' => 'article_summary.tpl', 'ops' => 'preprint_summary.tpl', 'omp' => 'monograph_summary.tpl'];
        $this->assertArrayHasKey($application, $templates);
        $source = (string) file_get_contents($base . $templates[$application]);

        if ($application === 'omp') {
            $this->assertArrayNotHasKey('omp', DoiInSummaryPlugin::SUMMARY_HOOKS);
            $this->assertStringNotContainsString('call_hook', $source);
            $this->assertStringContainsString('class="obj_monograph_summary', $source);
            $this->assertStringContainsString('op="book" path=$monograph->getBestId()', $source);
            $this->assertStringContainsString('class="author"', $source);
            return;
        }

        $hook = DoiInSummaryPlugin::SUMMARY_HOOKS[$application];
        $this->assertStringContainsString('{call_hook name="' . $hook['hook'] . '"}', $source);
        $this->assertStringContainsString('$' . $hook['variable'] . '->', $source);
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

            public array $vars = [];

            public function getTemplateVars()
            {
                return $this->vars;
            }

            public function registerFilter($type, $callback, $name = null)
            {
                $this->added[] = $type . ':' . $name;
            }
        };
        $plugin = new DoiInSummaryPlugin();

        $plugin->addDoiAssets('TemplateManager::display', [$templateMgr, 'management/settings/website.tpl']);
        $this->assertSame([], $templateMgr->added, 'Nothing on editorial pages.');

        $plugin->addDoiAssets('TemplateManager::display', [$templateMgr, 'frontend/pages/index.tpl']);
        $this->assertSame(['doiInSummaryCSS', 'doiInSummaryJS'], $templateMgr->added, 'No output filter on a page without books with a DOI.');

        $templateMgr->added = [];
        $templateMgr->vars = ['publishedSubmissions' => [$this->submission(1, '10.1234/one')]];
        $plugin->addDoiAssets('TemplateManager::display', [$templateMgr, 'frontend/pages/catalog.tpl']);
        $expected = ['doiInSummaryCSS', 'doiInSummaryJS'];
        if (Application::get()->getName() === 'omp') {
            // Only OMP, whose book summary has no hook, filters the page.
            $expected[] = 'output:doiInSummaryMonographs';
        }
        $this->assertSame($expected, $templateMgr->added);
    }

    public function testTheBooksOfThePageAreFoundInTheTemplateVariables(): void
    {
        $plugin = new DoiInSummaryPlugin();
        $vars = [
            'publishedSubmissions' => [$this->submission(1, '10.1234/one'), $this->submission(2, null)],
            'featuredMonographs' => [$this->submission(1, '10.1234/one')],
            'newReleases' => [$this->submission(3, 'doi:10.1234/three', 'a-book')],
            'results' => new VirtualArrayIterator([['publishedSubmission' => $this->submission(4, '10.1234/four'), 'press' => null]], 1, 1, 1),
            'emptyResults' => new VirtualArrayIterator([], 0),
            'featuredMonographIds' => [1 => 1],
            'currentContext' => null,
            'pageTitle' => 'Catalog',
        ];

        $this->assertSame([
            '1' => ['url' => 'https://doi.org/10.1234/one', 'id' => 1],
            'a-book' => ['url' => 'https://doi.org/10.1234/three', 'id' => 3],
            '4' => ['url' => 'https://doi.org/10.1234/four', 'id' => 4],
        ], DoiInSummaryPlugin::collectDoiUrls($vars, $plugin->getDoiUrl(...)));
    }

    public function testEachBookSummaryGetsItsDoiAfterTheAuthors(): void
    {
        $page = '<main>' . $this->bookSummary('1', 'First') . $this->bookSummary('2', 'Second') . $this->bookSummary('a-book', 'Third') . '</main>';
        $doiUrls = ['1' => ['url' => 'https://doi.org/10.1234/one', 'id' => 1], 'a-book' => ['url' => 'https://doi.org/10.1234/three', 'id' => 3]];

        $result = DoiInSummaryPlugin::insertIntoMonographSummaries($page, $doiUrls, $this->render());

        $this->assertSame(2, substr_count($result, 'class="doiInSummary"'));
        $this->assertStringContainsString('Ana Silva, Bruno Costa</div><div class="doiInSummary" data-doi-in-summary="1"><a href="https://doi.org/10.1234/one">', $result);
        $this->assertStringContainsString('Ana Silva, Bruno Costa</div><div class="doiInSummary" data-doi-in-summary="3"><a href="https://doi.org/10.1234/three">', $result);
        // The book without DOI keeps its summary as it was.
        $this->assertStringContainsString($this->bookSummary('2', 'Second'), $result);
        // Nothing else of the page changes.
        $this->assertSame($page, preg_replace('/<div class="doiInSummary"[^>]*><a [^>]*>[^<]*<\/a><\/div>/', '', $result));
    }

    public function testWithoutAuthorsTheDoiFollowsTheTitle(): void
    {
        $page = $this->bookSummary('5', 'No authors', false);
        $doiUrls = ['5' => ['url' => 'https://doi.org/10.1234/five', 'id' => 5]];

        $result = DoiInSummaryPlugin::insertIntoMonographSummaries($page, $doiUrls, $this->render());

        $this->assertStringContainsString('No authors</a></h2><div class="doiInSummary" data-doi-in-summary="5">', $result);
    }

    public function testABookListedTwiceGetsItsDoiInBothSummariesAndOnlyOnce(): void
    {
        // The home page of a press can show a book as featured and as a new release.
        $page = $this->bookSummary('1', 'First') . '<h2>New releases</h2>' . $this->bookSummary('1', 'First');
        $doiUrls = ['1' => ['url' => 'https://doi.org/10.1234/one', 'id' => 1]];

        $once = DoiInSummaryPlugin::insertIntoMonographSummaries($page, $doiUrls, $this->render());
        $this->assertSame(2, substr_count($once, 'data-doi-in-summary="1"'));
        $this->assertSame($once, DoiInSummaryPlugin::insertIntoMonographSummaries($once, $doiUrls, $this->render()), 'Filtering twice adds nothing.');
    }

    public function testTheMarkupIsInsertedLiterally(): void
    {
        // "$1" or "\1" in the DOI markup must not be read as references of preg_replace().
        $page = $this->bookSummary('1', 'First');
        $doiUrls = ['1' => ['url' => 'https://doi.org/10.1234/$1\\0', 'id' => 1]];

        $result = DoiInSummaryPlugin::insertIntoMonographSummaries($page, $doiUrls, $this->render());

        $this->assertStringContainsString('<a href="https://doi.org/10.1234/$1\\0">https://doi.org/10.1234/$1\\0</a>', $result);
    }

    public function testAPageWithoutBookSummariesIsLeftAlone(): void
    {
        $page = '<main><h2><a href="https://press.example/index.php/press/catalog/book/1">A book</a></h2></main>';
        $doiUrls = ['1' => ['url' => 'https://doi.org/10.1234/one', 'id' => 1]];

        $this->assertSame($page, DoiInSummaryPlugin::insertIntoMonographSummaries($page, $doiUrls, $this->render()));
    }
}
