<?php
namespace PropertySearch\Site\BlockLayout;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use PropertySearch\Site\BlockLayout\StateCountySearch;

class StateCountyFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        // Directly pass services to the constructor with minimal edits
        return new StateCountySearch(
            $services->get('FormElementManager'),
            $services->get('Omeka\ApiManager'),
            $services->get('Omeka\Connection') // Add database connection as the third parameter
        );
    }
}
