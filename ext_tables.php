<?php
defined('TYPO3_MODE') or die('Access denied.');

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'tools',
        'redirect',
        '',
        '',
        array(
            'routeTarget' => \MFC\RestructureRedirect\Controller\RedirectModuleController::class . '::mainAction',
            'access' => 'admin',
            'name' => 'tools_redirect',
            'labels' => array(
                'tabs_images' => array(
                    'tab' => 'EXT:restructure_redirect/Resources/Public/Icon/redirect.gif',
                ),
                'll_ref' => 'LLL:EXT:restructure_redirect/Resources/Private/Language/locallang_mod.xlf',
            ),
        )
    );
}
