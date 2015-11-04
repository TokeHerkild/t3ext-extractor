<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\Extractor\Report;

use Causal\Extractor\Utility\ExtensionHelper;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class providing a report displaying a list of all extraction services.
 * Code inspired by EXT:sv/Classes/Report/ServicesListReport.php by Francois Suter
 * which in turn was inspired by EXT:dam/lib/class.tx_dam_svlist.php by René Fritz.
 *
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ServicesListReport implements \TYPO3\CMS\Reports\ReportInterface
{

    /**
     * Back-reference to the calling reports module
     *
     * @var \TYPO3\CMS\Reports\Controller\ReportController
     */
    protected $reportsModule;

    /**
     * Constructor for class \Causal\Extractor\Report\ServicesListReport
     *
     * @param \TYPO3\CMS\Reports\Controller\ReportController $reportsModule Back-reference to the calling reports module
     */
    public function __construct(\TYPO3\CMS\Reports\Controller\ReportController $reportsModule)
    {
        $this->reportsModule = $reportsModule;
        $this->getLanguageService()->includeLLFile('EXT:extractor/Resources/Private/Language/locallang_reports.xlf');
    }

    /**
     * This method renders the report
     *
     * @return string The status report as HTML
     */
    public function getReport()
    {
        $content = '';
        $content .= $this->renderHelp();
        $content .= $this->renderExtractorsList();
        return $content;
    }

    /**
     * Renders the help comments at the top of the module.
     *
     * @return string The help content for this module.
     */
    protected function renderHelp()
    {
        $help = '<p class="help">' . $this->getLanguageService()->getLL('report_explanation', true) . '</p>';
        return $help;
    }

    /**
     * This method assembles a list of all installed services
     *
     * @return string HTML to display
     */
    protected function renderExtractorsList()
    {
        $language = $this->getLanguageService();
        $header = '<h4>' . $language->getLL('extractors')  . '</h4>';

        if (version_compare(TYPO3_version, '6.99.99', '<=')) {
            $tableClass = 'services';
        } else {
            $tableClass = 'table table-striped table-hover';
        }

        $extractorsList = '
		<table cellspacing="1" cellpadding="2" border="0" class="' . $tableClass .'">
		    <thead>
                <tr class="t3-row-header">
                    <td style="width: 35%">' . $language->getLL('class', true) . '</td>
                    <td>' . $language->getLL('driver_restrictions', true) . '</td>
                    <td>' . $language->getLL('priority', true) . '</td>
                    <td>' . $language->getLL('execution_priority', true) . '</td>
                    <td style="width: 35%">' . $language->getLL('file_types', true) . '</td>
                </tr>
            </thead>
            <tbody>';

        $extractorRegistry = \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance();
        $extractors = $extractorRegistry->getExtractors();
        foreach ($extractors as $extractor) {
            $extractorsList .= $this->renderExtractorRow($extractor);
        }

        $extractorsList .= '
            </tbody>
        </table>';

        return $header . $extractorsList;
    }

    /**
     * Renders a single extractor's row.
     *
     * @param ExtractorInterface $extractor
     * @return string
     */
    protected function renderExtractorRow(ExtractorInterface $extractor)
    {
        $class = get_class($extractor);
        if (strpos($class, '\\') !== false) {
            $parts = explode('\\', $class);
            $extensionName = $parts[1] !== 'CMS'
                ? GeneralUtility::camelCaseToLowerCaseUnderscored($parts[1])
                : strtolower($parts[1]);
            $class = end($parts);
        } else {
            // tx, <extension>, ... , <class>
            $parts = explode('_', $class);
            $extensionName = $parts[1];
            $class = end($parts);
        }
        $driverRestrictions = $extractor->getDriverRestrictions()
            ? implode(', ', $extractor->getDriverRestrictions())
            : '*';
        $fileTypes = $extractor->getFileTypeRestrictions() ?: array('*');

        if (version_compare(TYPO3_version, '6.99.99', '<=')) {
            $rowClass = 'cell typo3-message message-ok';
        } else {
            $rowClass = '';
        }

        $serviceRow = '
        <tr class="service">
            <td class="' . $rowClass .'">' . $class . ' (EXT:' . $extensionName . ')</td>
            <td class="' . $rowClass .'">' . htmlspecialchars($driverRestrictions) . '</td>
            <td class="' . $rowClass .'">' . (int)$extractor->getPriority() . '</td>
            <td class="' . $rowClass .'">' . (int)$extractor->getExecutionPriority() . '</td>
            <td class="' . $rowClass .'">' . $this->groupFileTypes($fileTypes) . '</td>
        </tr>';

        return $serviceRow;
    }

    /**
     * Groups file types by kind (taking for granted that file types
     * are sorted by group of document types.
     *
     * @param array $fileTypes
     * @return string HTML snippet
     */
    protected function groupFileTypes(array $fileTypes)
    {
        if ($fileTypes[0] === '*') {
            return '*';
        }

        $groups = array();
        foreach ($fileTypes as $fileType) {
            $category = ExtensionHelper::getExtensionCategory($fileType);
            $groups[$category][] = $fileType;
        }
        ksort($groups);

        $groupedFileTypes = '';
        foreach ($groups as $extensions) {
            sort($extensions);
            $groupedFileTypes .= implode(', ', $extensions) . LF . LF;
        }

        $groupedFileTypes = nl2br(trim($groupedFileTypes));
        return $groupedFileTypes;
    }

    /**
     * Returns the language service instance.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

}