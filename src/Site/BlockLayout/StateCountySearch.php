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
use Doctrine\DBAL\Connection;

class StateCountySearch extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    protected $connection;

    public function getLabel()
    {
        return 'State + County Search'; // @translate
    }

    public function __construct(FormElementManager $formElements, ApiManager $api, Connection $connection)
    {
        $this->formElements = $formElements;
        $this->api = $api;
        $this->connection = $connection;
    }

    public function getPropertyIdByLabel($label)
    {
        try {
            $sql = "SELECT id FROM property WHERE label = :label LIMIT 1";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(':label', $label);
            $stmt->execute();
            $result = $stmt->fetch();

            return $result ? $result['id'] : null;
        } catch (\Exception $e) {
            echo "Error retrieving property_id: " . $e->getMessage();
            return null;
        }
    }

    public function getCountiesByState($state)
    {
        // Retrieve property IDs for state and county
        $statePropertyId = $this->getPropertyIdByLabel("state");
        $countyPropertyId = $this->getPropertyIdByLabel("county");
    
        if (!$statePropertyId || !$countyPropertyId) {
            // Return an empty response if either property ID is not found
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }
    
        // Fetch all rows with both state and county metadata
        $sql = "SELECT r.id AS resource_id, 
                       (SELECT value FROM value WHERE property_id = :statePropertyId AND resource_id = r.id LIMIT 1) AS state_value,
                       (SELECT value FROM value WHERE property_id = :countyPropertyId AND resource_id = r.id LIMIT 1) AS county_value
                FROM resource r
                WHERE r.id IN (
                    SELECT resource_id FROM value WHERE property_id = :statePropertyId
                ) AND r.id IN (
                    SELECT resource_id FROM value WHERE property_id = :countyPropertyId
                )";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':statePropertyId', $statePropertyId);
        $stmt->bindValue(':countyPropertyId', $countyPropertyId);
        $stmt->execute();
    
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Filter counties based on the selected state
        $counties = [];
        foreach ($rows as $row) {
            if ($row['state_value'] === $state) {
                $counties[] = $row['county_value'];
            }
        }
    
        // Return unique counties as JSON
        $uniqueCounties = array_values(array_unique($counties));
        header('Content-Type: application/json');
        echo json_encode($uniqueCounties);
        exit;
    }
    



    public function form(PhpRenderer $view, SiteRepresentation $site,
    SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
) { 
    $defaults = [
        'conditionalSelect' => 'and',
        'searchTypeSelect01' => 'sw',
        'searchTypeSelect02' => 'sw',
        'searchField_01' => '',
        'placeHolder_01' => '',
        'searchField_02' => '',
        'placeHolder_02' => '',
    ];

    $data = $block ? $block->data() + $defaults : $defaults;

    // Fetch the property_id for "state"
    $statePropertyId = $this->getPropertyIdByLabel("state");

    // Fetch unique states if the property_id is found
    $states = [];
    if ($statePropertyId) {
        try {
            $sql = "SELECT DISTINCT value FROM value WHERE property_id = :property_id";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(':property_id', $statePropertyId);
            $stmt->execute();
            $states = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            echo "Error fetching states: " . $e->getMessage();
        }
    }

    // Fetch the property_id for "county"
    $countyPropertyId = $this->getPropertyIdByLabel("county");

    // Fetch unique counties if the property_id is found
    $counties = [];
    if ($countyPropertyId) {
        try {
            $sql = "SELECT DISTINCT value FROM value WHERE property_id = :property_id";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(':property_id', $countyPropertyId);
            $stmt->execute();
            $counties = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            echo "Error fetching counties: " . $e->getMessage();
        }
    }

    $layoutForm = new Form();

    // State dropdown
    $stateSelect = new Element\Select('state');
    $stateSelect->setLabel('Select State');
    $stateSelect->setValueOptions(array_combine($states, $states));
    $layoutForm->add($stateSelect);

    // County dropdown
    $countySelect = new Element\Select('county');
    $countySelect->setLabel('Select County');
    $countySelect->setValueOptions(array_combine($counties, $counties));
    $layoutForm->add($countySelect);

    $layoutForm->prepare();
    $html = $view->formCollection($layoutForm);
    return $html;
}

public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = 'common/block-layout/empowered-custom-school-name')
{
    $blockData = ($block) ? $block->data() : [];

    // Fetch property IDs for "state" and "county"
    $statePropertyId = $this->getPropertyIdByLabel("state");
    $countyPropertyId = $this->getPropertyIdByLabel("county");

    $stateCountyPairs = [];
    $states = [];

    if ($statePropertyId && $countyPropertyId) {
        // Query to get all resources with both state and county values
        $sql = "SELECT
                    (SELECT value FROM value WHERE property_id = :statePropertyId AND resource_id = r.id LIMIT 1) AS state_value,
                    (SELECT value FROM value WHERE property_id = :countyPropertyId AND resource_id = r.id LIMIT 1) AS county_value
                FROM resource r
                WHERE r.id IN (SELECT resource_id FROM value WHERE property_id = :statePropertyId)
                AND r.id IN (SELECT resource_id FROM value WHERE property_id = :countyPropertyId)";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':statePropertyId', $statePropertyId);
        $stmt->bindValue(':countyPropertyId', $countyPropertyId);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($rows as $row) {
            $stateCountyPairs[] = [
                'state' => $row['state_value'],
                'county' => $row['county_value']
            ];
            $states[] = $row['state_value'];
        }
    }

    // Remove duplicate states
    $states = array_values(array_unique($states));

    // Pass state list and state-county pairs to the template
    $blockData['states'] = $states;
    $blockData['stateCountyPairs'] = $stateCountyPairs;

    return $view->partial($templateViewScript, $blockData);
}

}
