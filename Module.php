<?php
namespace PropertySearch;

use Omeka\Module\AbstractModule;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return [
            'block_layouts' => [
                'factories' => [
                    'schoolNameSearch' => Site\BlockLayout\SchoolNameFactory::class,
                    'stateCountySearch' => Site\BlockLayout\StateCountyFactory::class,
                ],
            ],
            'view_manager' => [
                'template_path_stack' => [
                    OMEKA_PATH.'/modules/PropertySearch/view',
                ],
            ],
        ];
    }
}