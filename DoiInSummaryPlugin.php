<?php

/**
 * @file plugins/generic/doiInSummary/DoiInSummaryPlugin.php
 *
 * Copyright (c) 2015-2023 Lepidus Tecnologia
 * Adapted for OJS 3.4 by OJSBR/STNT Tecnologia da Informação LTDA.
 *
 * Distributed under the GNU GPL v3. For full terms see LICENSE or
 * https://www.gnu.org/licenses/gpl-3.0.txt.
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
     * Register the plugin.
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);

        if (!$success || Application::isUnderMaintenance()) {
            return $success;
        }

        if ($this->getEnabled($mainContextId)) {
            $this->addLocaleData();
            // OJS 3.4: array callable (avoids PHP 8.1 first-class callable syntax).
            Hook::add('Templates::Issue::Issue::Article', [$this, 'addDoiToArticleSummary']);
            $this->addDoiStyleSheet();
        }

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
    public function addDoiToArticleSummary(string $hookName, array $args): bool
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
            'doiUrl' => $doiUrl,
            'doiInSummaryArticleId' => $articleId,
            'doiInSummaryUrl' => $doiUrl,
        ]);

        $output .= $templateMgr->fetch($this->getTemplateResource('doi_summary.tpl'));

        return Hook::CONTINUE;
    }

    /**
     * Get the resolving DOI URL from the current publication.
     */
    private function getArticleDoiUrl(object $article): ?string
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

        if (!is_string($doi) || trim($doi) === '') {
            return null;
        }

        $doi = trim($doi);
        if (preg_match('/^https?:\/\//i', $doi)) {
            return $doi;
        }

        return 'https://doi.org/' . ltrim($doi, '/');
    }

    /**
     * Register the plugin stylesheet on frontend requests.
     */
    private function addDoiStyleSheet(): void
    {
        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);
        $url = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/doi.css';

        $templateMgr->addStyleSheet('doiInSummaryCSS', $url);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\\APP\\plugins\\generic\\doiInSummary\\DoiInSummaryPlugin', '\\DoiInSummaryPlugin');
}
