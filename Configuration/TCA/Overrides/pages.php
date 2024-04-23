<?php
$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
    'Content Preset folder',
    'content-presets',
    'tx_content_presets_preset',
];
$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-content-presets'] = 'tx_content_presets_preset_folder';