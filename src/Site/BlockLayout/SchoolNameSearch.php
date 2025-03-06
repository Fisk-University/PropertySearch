<?php
namespace PropertySearch\Site\BlockLayout;

use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Site\BlockLayout\TemplateableBlockLayoutInterface;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Form\Element as OmekaElement;
use Laminas\Form\Element;
use Laminas\Form\Form;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Manager as ApiManager;
use Laminas\Form\FormElementManager;

class SchoolNameSearch extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    protected \FormElementManager $_formElements;

    protected ApiManger $_apiManager;

    public function getLabel()
    {
        return 'School Name Search'; // @translate
    }

    public function __construct(FormElementManager $formElements, ApiManager $api)
    {
        $this->formElements = $formElements;
        $this->api = $api;
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
    SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null) 
    { 
    $defaults = [
        'itemSetId' => '', // Item set selection
        'propertyId' => '', // Property selection
        'placeHolder_01' => '',
    ];

    $data = $block ? $block->data() + $defaults : $defaults;
    
    $layoutForm = new Form();

    // Item Set Selector
    $itemSetSelect = new Element\Select('o:block[__blockIndex__][o:data][itemSetId]');
    $itemSetSelect->setLabel('Select an Item Set');
    $itemSetOptions = [];
    $itemSets = $this->api->search('item_sets')->getContent();
    foreach ($itemSets as $itemSet) {
        $itemSetOptions[$itemSet->id()] = $itemSet->displayTitle();
    }
    $itemSetSelect->setValueOptions($itemSetOptions);
    $itemSetSelect->setValue($data['itemSetId']);
    $layoutForm->add($itemSetSelect);

    // Property Selector
    $propertySelect = new Element\Select('o:block[__blockIndex__][o:data][propertyId]');
    $propertySelect->setLabel('Select a Property to Search Within');
    $propertyOptions = [];
    $properties = $this->api->search('properties')->getContent();
    foreach ($properties as $property) {
        $propertyOptions[$property->id()] = $property->label();
    }
    $propertySelect->setValueOptions($propertyOptions);
    $propertySelect->setValue($data['propertyId']);
    $layoutForm->add($propertySelect);

    // Placeholder Text Field
    $layoutForm->add([
        'name' => 'o:block[__blockIndex__][o:data][placeHolder_01]',
        'type' => Element\Text::class,
        'options' => [
            'label' => 'Placeholder (aria) text for the search field', // @translate
        ],
        'attributes' => [
            'value' => $data['placeHolder_01'],
        ]
    ]);

    $layoutForm->prepare();

    return $view->formCollection($layoutForm);
    }


    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = 'common/block-layout/empowered-custom-item-set')
    {
        $blockData = ($block) ? $block->data() : [];
        return $view->partial($templateViewScript, $blockData);
    }


}