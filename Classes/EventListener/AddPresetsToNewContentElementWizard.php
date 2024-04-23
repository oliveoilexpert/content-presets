<?php
namespace UBOS\ContentPresets\EventListener;

use TYPO3\CMS\Backend\Controller\Event\ModifyNewContentElementWizardItemsEvent;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Cache\Backend\BackendInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

final class AddPresetsToNewContentElementWizard {

    public function __construct(
        private readonly BackendInterface $cache,
    ) {}

    private function getCachedWizardItemsArray(string $cacheIdentifier = 'auto', array $tags = [], int|null $lifetime = null): array
    {
        $value = $this->cache->get($cacheIdentifier);
        if ($value === false) {
            $this->cache->set($cacheIdentifier, $this->generateWizardItemsArrayFromDatabase(), $tags, $lifetime);
        }
        return $value;
    }

    public function generateWizardItemsArrayFromDatabase(): array
    {

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilderPages = $connectionPool->getQueryBuilderForTable('pages');
        $queryBuilderPages->getRestrictions()->removeAll();
        $presetFolders = $queryBuilderPages
            ->select('uid','doktype','module')
            ->from('pages')
            ->where(
                $queryBuilderPages->expr()->eq('doktype', $queryBuilderPages->createNamedParameter(254)),
                $queryBuilderPages->expr()->eq('module', $queryBuilderPages->createNamedParameter('content-presets'))
            )
            ->execute()
            ->fetchAll();
        if (empty($presetFolders)) {
            return [];
        }
        $presetFolderIds = '';
        foreach($presetFolders as $presetFolder) {
            $presetFolderIds .= $presetFolder['uid'].',';
        }
        $queryBuilderContent = $connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilderContent->getRestrictions()->removeAll();
        $contentElements = $queryBuilderContent
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilderContent->expr()->in('pid', $queryBuilderContent->createNamedParameter($presetFolderIds)),
                $queryBuilderContent->expr()->eq('hidden', $queryBuilderContent->createNamedParameter(0)),
                $queryBuilderContent->expr()->eq('deleted', $queryBuilderContent->createNamedParameter(0))
            )
            ->execute()
            ->fetchAll();
        $excludeColumns = 'deleted,colPos,l10n_source,l10n_state,tx_impexp_origuid,t3_origuid,l18n_diffsource,t3ver_oid,t3ver_wsid,t3ver_state,t3ver_stage,l18n_parent,sys_language_uid,uid,pid,rowDescription,tstamp,crdate,cruser_id,starttime,endtime,sorting,hidden,tx_container_parent';

        $wizardItems = [];

        foreach($contentElements as $index => $row) {
            $elementKey = 'preset_'.$row['CType'].'_'.$row['uid'];
            $modelNameLowercaseUnderscored = str_replace('puck_','',$row['CType']);
            $defValues = [];
            foreach($row as $col => $val) {
                if($val === '' || $val === null || GeneralUtility::inList($excludeColumns, $col)) {
                    continue;
                }
                $defValues[$col] = $val;
            }
            $wizardItems[] = [
                'key' => $elementKey,
                'config' => [
                    'iconIdentifier' => $modelNameLowercaseUnderscored,
                    'title' => 'Preset: '.$row['header'],
                    'description' => 'Custom preset for '.LocalizationUtility::translate('LLL:EXT:puck/Resources/Private/Language/locallang_be.xlf:wizard.'.$modelNameLowercaseUnderscored).' element.',
                    'tt_content_defValues' => $defValues,
                ],
            ];
        }

        return $wizardItems;
    }

    public function __invoke(
        ModifyNewContentElementWizardItemsEvent $event
    ): void
    {
        $event->setWizardItem(
            '00_presets',
            [
                'header' => 'Presets'
            ],
            ['before' => '01_content']
        );
        foreach($this->getCachedWizardItemsArray() as $item) {

            $event->setWizardItem(
                $item['key'],
                $item['config'],
                ['after' => '00_presets']
            );
        }

    }
}