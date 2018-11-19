<?php
defined('TYPO3_MODE') or die('Access denied.');


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_restructureredirect_redirects');


if (TYPO3_MODE === 'BE') {
    $version = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
    if ($version < 7006000) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
            'tools',
            'redirect',
            '',
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('restructure_redirect').'mod/'
        );
    } else {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
            'tools',
            'redirect',
            '',
            '',
            array(
                'routeTarget' => 'Mfc\\RestructureRedirect\\Controller\\RedirectModuleController::mainAction',
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
}
