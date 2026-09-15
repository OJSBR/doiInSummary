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
 * @brief Shows the DOI of each article in the issue table of contents and in the
 *        current issue on the journal home page.
 */

namespace APP\plugins\generic\doiInSummary;

use APP\core\Application;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class DoiInSummaryPlugin extends GenericPlugin
{
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
        // Only reader-facing pages of a journal reach these hooks.
        if (!$success || Application::isUnderMaintenance() || !$this->getEnabled($mainContextId)) {
            return $success;
        }

        Hook::add('Templates::Issue::Issue::Article', $this->addDoiToArticleSummary(...));
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
     * Provide the plugin description.
     */
    public function getDescription(): string
    {
        return __('plugins.generic.doiInSummary.description');
    }

    /**
     * Add DOI markup to article summaries in issue tables of contents.
     *
     * The Smarty {call_hook} helper still uses the legacy hook contract:
     * args[0] = Smarty params, args[1] = Smarty template object, args[2] = output by reference.
     * In OJS 3.5 args[1] can be Smarty_Internal_Template instead of APP\template\TemplateManager,
     * so this callback intentionally avoids a strict TemplateManager type check.
     */
    public function addDoiToArticleSummary($hookName, $args): bool
    {
        if (!isset($args[1]) || !array_key_exists(2, $args) || !is_object($args[1])) {
            return Hook::CONTINUE;
        }

        $smarty = $args[1];
        $output = &$args[2];

        if (!method_exists($smarty, 'getTemplateVars')) {
            return Hook::CONTINUE;
        }

        $article = $smarty->getTemplateVars('article');
        if (!$article instanceof Submission && !is_object($article)) {
            return Hook::CONTINUE;
        }

        $doiUrl = $this->getArticleDoiUrl($article);
        if ($doiUrl === null || trim($doiUrl) === '') {
            return Hook::CONTINUE;
        }

        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);

        $articleId = method_exists($article, 'getId') ? (int) $article->getId() : 0;

        $templateMgr->assign([
            'doiInSummaryArticleId' => $articleId,
            'doiInSummaryUrl' => $doiUrl,
        ]);

        $output .= $templateMgr->fetch($this->getTemplateResource('doi_summary.tpl'));

        return Hook::CONTINUE;
    }

    /**
     * Get the resolving DOI URL from the current publication.
     */
    public function getArticleDoiUrl(object $article): ?string
    {
        if (!method_exists($article, 'getCurrentPublication')) {
            return null;
        }

        $publication = $article->getCurrentPublication();
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

        if (!$doi && method_exists($publication, 'getData')) {
            $doi = $publication->getData('doi') ?: $publication->getData('pub-id::doi');
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
     * Add the stylesheet and the script to reader pages.
     * The script, loaded once per page, moves every DOI right under the title of
     * its article.
     *
     * @param string $hookName
     * @param array $args [$templateMgr, $template, $sendContentType, $charset, $output]
     */
    public function addDoiAssets($hookName, $args): bool
    {
        // Summaries appear in the table of contents, on the home page, in search results and in
        // pages of themes; the files are small and only reader pages load them.
        if (!str_starts_with((string) ($args[1] ?? ''), 'frontend/')) {
            return Hook::CONTINUE;
        }

        $request = Application::get()->getRequest();
        $templateMgr = $args[0];
        $baseUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();

        $templateMgr->addStyleSheet('doiInSummaryCSS', $baseUrl . '/styles/doi.css', ['contexts' => 'frontend']);
        $templateMgr->addJavaScript('doiInSummaryJS', $baseUrl . '/js/doiInSummary.js', ['contexts' => 'frontend']);

        return Hook::CONTINUE;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\\APP\\plugins\\generic\\doiInSummary\\DoiInSummaryPlugin', '\\DoiInSummaryPlugin');
}
