<?php

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'tx_content_presets_preset' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:content_presets/Resources/Public/Icons/Backend/Preset.svg'
    ],
    'tx_content_presets_preset_folder' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:content_presets/Resources/Public/Icons/Backend/PresetFolder.svg'
    ]
];