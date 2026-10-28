<?php

/**
 * @file plugins/generic/doiInSummary/DoiInSummaryPlugin.php
 *
 * Copyright (c) 2015-2023 Lepidus Tecnologia
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoiInSummaryPlugin
 *
 * @brief Shows the DOI of each article, preprint or book in the lists the readers see:
 *        issue tables of contents and the journal home page (OJS), the lists of preprints
 *        (OPS) and the catalogue, home page and search results of a press (OMP).
 */

namespace APP\plugins\generic\doiInSummary;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\core\VirtualArrayIterator;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class DoiInSummaryPlugin extends GenericPlugin
{
    /**
     * The template hook that ends each summary and the template variable holding its
     * submission, by application name (OJS is "ojs2"). OMP has no hook in its book summary
     * (monograph_summary.tpl), so its lists are handled by an output filter instead.
     */
    public const SUMMARY_HOOKS = [
        'ojs2' => ['hook' => 'Templates::Issue::Issue::Article', 'variable' => 'article', 'idPrefix' => 'doi_article-', 'class' => 'doiInSummary'],
        'ops' => ['hook' => 'Templates::Archive::Preprint', 'variable' => 'preprint', 'idPrefix' => 'doi_preprint-', 'class' => 'doi doiInSummary'],
    ];

    /**
     * Register the plugin and, where it is enabled, its hooks.
     *
     * @param string $category
     * @param string $path
     * @param null|int $mainContextId
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        // Only reader-facing pages of a journal, server or press reach these hooks.
        if (!$success || Application::isUnderMaintenance() || !$this->getEnabled($mainContextId)) {
            return $success;
        }

        $summaryHook = self::SUMMARY_HOOKS[Application::get()->getName()] ?? null;
        if ($summaryHook) {
            Hook::add($summaryHook['hook'], $this->addDoiToSummary(...));
        }
        Hook::add('TemplateManager::display', $this->addDoiAssets(...));

        return $success;
    }

    /**
     * Provide the plugin display name.
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.doiInSummary.displayName');
    }

    /**
     * Provide the plugin description, which names what the application lists.
     */
    public function getDescription(): string
    {
        return match (Application::get()->getName()) {
            'ops' => __('plugins.generic.doiInSummary.description.ops'),
            'omp' => __('plugins.generic.doiInSummary.description.omp'),
            default => __('plugins.generic.doiInSummary.description'),
        };
    }

    /**
     * Add DOI markup to an article (OJS) or preprint (OPS) summary.
     *
     * The Smarty {call_hook} helper still uses the legacy hook contract:
     * args[0] = Smarty params, args[1] = Smarty template object, args[2] = output by reference.
     * In 3.5 args[1] can be Smarty_Internal_Template instead of APP\template\TemplateManager,
     * so this callback intentionally avoids a strict TemplateManager type check.
     */
    public function addDoiToSummary($hookName, $args): bool
    {
        if (!isset($args[1]) || !array_key_exists(2, $args) || !is_object($args[1])) {
            return Hook::CONTINUE;
        }

        $smarty = $args[1];
        $output = &$args[2];

        if (!method_exists($smarty, 'getTemplateVars')) {
            return Hook::CONTINUE;
        }

        $summaryHook = self::SUMMARY_HOOKS[Application::get()->getName()] ?? self::SUMMARY_HOOKS['ojs2'];
        $submission = $smarty->getTemplateVars($summaryHook['variable']);
        if (!is_object($submission)) {
            return Hook::CONTINUE;
        }

        $doiUrl = $this->getDoiUrl($submission);
        if ($doiUrl === null) {
            return Hook::CONTINUE;
        }

        $submissionId = method_exists($submission, 'getId') ? (int) $submission->getId() : 0;
        $output .= $this->renderDoi($doiUrl, $submissionId, $summaryHook['idPrefix'] . $submissionId, $summaryHook['class']);

        return Hook::CONTINUE;
    }

    /**
     * Add the DOI to every book summary of a page of OMP.
     *
     * Output filter of the page. The books are the ones the page handler gave the template,
     * collected before the page is rendered: their publications and DOIs are already loaded,
     * so nothing is read from the database here.
     *
     * @param array<string,array{url:string,id:int}> $doiUrls
     */
    public function addDoiToMonographSummaries(string $output, array $doiUrls): string
    {
        if (!str_contains($output, 'obj_monograph_summary')) {
            return $output;
        }

        return self::insertIntoMonographSummaries(
            $output,
            $doiUrls,
            // No id: the same book can be listed twice on a page (featured and new releases).
            fn (string $doiUrl, int $submissionId) => $this->renderDoi($doiUrl, $submissionId, null, 'doiInSummary')
        );
    }

    /**
     * The resolving DOI URLs of the books found in the template variables, by the id
     * the catalogue uses in their URL (Submission::getBestId()).
     *
     * Books come in arrays (publishedSubmissions, featuredMonographs, newReleases...) and,
     * in search results, as the 'publishedSubmission' of each result, in an iterator that
     * drops its items once the page went through them: they are read before the page is
     * rendered. Lazy collections are left alone: going through one would run its query again.
     *
     * @param callable $doiUrl function (object $submission): ?string
     *
     * @return array<string,array{url:string,id:int}>
     */
    public static function collectDoiUrls(array $vars, callable $doiUrl): array
    {
        $doiUrls = [];
        $add = function ($candidate) use (&$doiUrls, $doiUrl) {
            if (is_array($candidate) && isset($candidate['publishedSubmission'])) {
                $candidate = $candidate['publishedSubmission'];
            }
            if (!is_object($candidate) || !method_exists($candidate, 'getBestId') || !method_exists($candidate, 'getCurrentPublication')) {
                return;
            }
            $bestId = (string) $candidate->getBestId();
            if ($bestId === '' || isset($doiUrls[$bestId])) {
                return;
            }
            $url = $doiUrl($candidate);
            if ($url !== null) {
                $doiUrls[$bestId] = ['url' => $url, 'id' => (int) $candidate->getId()];
            }
        };

        foreach ($vars as $value) {
            if ($value instanceof VirtualArrayIterator) {
                $value = $value->eof() ? [] : $value->toArray();
            }
            if (is_array($value)) {
                foreach ($value as $item) {
                    $add($item);
                }
            } else {
                $add($value);
            }
        }

        return $doiUrls;
    }

    /**
     * Insert the DOI into each book summary of the page, after its authors (or after its
     * title when the theme shows no authors), once per summary.
     *
     * The book is recognised by the catalog/book URL of its cover and title links, the
     * same URL in every theme, so a summary whose book has no DOI is left as it is.
     *
     * @param array<string,array{url:string,id:int}> $doiUrls
     * @param callable $render function (string $doiUrl, int $submissionId): string
     */
    public static function insertIntoMonographSummaries(string $output, array $doiUrls, callable $render): string
    {
        $parts = preg_split('/(?=<div\b[^>]*\bclass="obj_monograph_summary\b)/', $output);
        if (!is_array($parts) || count($parts) < 2) {
            return $output;
        }

        foreach ($parts as $i => $part) {
            if ($i === 0 || !preg_match('/\bhref="[^"]*\/catalog\/book\/([^"\/?#]+)"/', $part, $book)) {
                continue;
            }
            $bestId = rawurldecode(html_entity_decode($book[1], ENT_QUOTES));
            if (!isset($doiUrls[$bestId]) || str_contains($part, 'data-doi-in-summary=')) {
                continue;
            }
            $doi = $render($doiUrls[$bestId]['url'], $doiUrls[$bestId]['id']);
            $link = preg_quote($book[1], '/');

            // After the authors, else after the heading that holds the title link.
            $placed = preg_replace('/(<div\b[^>]*\bclass="[^"]*\bauthor\b[^"]*"[^>]*>.*?<\/div>)/s', '${1}' . self::replacementSafe($doi), $part, 1, $count);
            if (!$count) {
                $placed = preg_replace('/(<(h[1-6])\b[^>]*>\s*<a\b[^>]*\bhref="[^"]*\/catalog\/book\/' . $link . '"[^>]*>.*?<\/a>\s*<\/\2>)/s', '${1}' . self::replacementSafe($doi), $part, 1, $count);
            }
            if ($count && is_string($placed)) {
                $parts[$i] = $placed;
            }
        }

        return implode('', $parts);
    }

    /**
     * Get the resolving DOI URL from the current publication of a submission.
     */
    public function getDoiUrl(object $submission): ?string
    {
        if (!method_exists($submission, 'getCurrentPublication')) {
            return null;
        }

        $publication = $submission->getCurrentPublication();
        if (!$publication || !is_object($publication)) {
            return null;
        }

        $doi = null;
        $doiObject = method_exists($publication, 'getData') ? $publication->getData('doiObject') : null;
        if (is_object($doiObject) && method_exists($doiObject, 'getData')) {
            $doi = $doiObject->getData('resolvingUrl') ?: $doiObject->getData('doi');
        }

        if (!$doi && method_exists($publication, 'getStoredPubId')) {
            $doi = $publication->getStoredPubId('doi');
        }

        return is_string($doi) ? self::resolvingUrl($doi) : null;
    }

    /**
     * The resolving URL of a DOI given as a URL, as "doi:..." or bare.
     */
    public static function resolvingUrl(string $doi): ?string
    {
        $doi = trim($doi);
        if ($doi === '') {
            return null;
        }
        if (preg_match('/^https?:\/\//i', $doi)) {
            return $doi;
        }

        return 'https://doi.org/' . ltrim(preg_replace('/^doi:\s*/i', '', $doi), '/');
    }

    /**
     * Add the stylesheet and the script to reader pages and, in OMP, the output filter
     * that adds the DOI to the book summaries.
     * The script, loaded once per page, moves every DOI of an article right under its
     * title and every DOI of a preprint right after its authors.
     *
     * @param string $hookName
     * @param array $args [$templateMgr, $template, $sendContentType, $charset, $output]
     */
    public function addDoiAssets($hookName, $args): bool
    {
        // Summaries appear in tables of contents, on home pages, in catalogues, in search
        // results and in pages of themes; the files are small and only reader pages load them.
        if (!str_starts_with((string) ($args[1] ?? ''), 'frontend/')) {
            return Hook::CONTINUE;
        }

        $request = Application::get()->getRequest();
        $templateMgr = $args[0];
        $baseUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();

        $templateMgr->addStyleSheet('doiInSummaryCSS', $baseUrl . '/styles/doi.css', ['contexts' => 'frontend']);
        $templateMgr->addJavaScript('doiInSummaryJS', $baseUrl . '/js/doiInSummary.js', ['contexts' => 'frontend']);

        if (Application::get()->getName() === 'omp' && method_exists($templateMgr, 'registerFilter')) {
            $doiUrls = self::collectDoiUrls((array) $templateMgr->getTemplateVars(), $this->getDoiUrl(...));
            if ($doiUrls) {
                // A named filter: registering it again on the same page replaces it.
                $templateMgr->registerFilter('output', fn (string $output) => $this->addDoiToMonographSummaries($output, $doiUrls), 'doiInSummaryMonographs');
            }
        }

        return Hook::CONTINUE;
    }

    /**
     * The markup of one DOI.
     */
    protected function renderDoi(string $doiUrl, int $submissionId, ?string $elementId, string $class): string
    {
        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
        $templateMgr->assign([
            'doiInSummaryElementId' => $elementId,
            'doiInSummaryClass' => $class,
            'doiInSummarySubmissionId' => $submissionId,
            'doiInSummaryUrl' => $doiUrl,
        ]);

        return $templateMgr->fetch($this->getTemplateResource('doi_summary.tpl'));
    }

    /**
     * Text to use as the replacement of preg_replace() without its $n and \n references.
     */
    protected static function replacementSafe(string $text): string
    {
        return str_replace(['\\', '$'], ['\\\\', '\\$'], $text);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\\APP\\plugins\\generic\\doiInSummary\\DoiInSummaryPlugin', '\\DoiInSummaryPlugin');
}
