<?php
namespace PropertySearch\Site\BlockLayout;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use PropertySearch\Site\BlockLayout\SchoolNameSearch ;

class SchoolNameFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new SchoolNameSearch($services->get('FormElementManager'), $services->get('Omeka\ApiManager'));
    }
}
