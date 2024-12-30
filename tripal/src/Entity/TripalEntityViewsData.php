<?php

namespace Drupal\tripal\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\Sql\TableMappingInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Provides Views data for Tripal Content entities.
 */
class TripalEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {



  protected function mapFieldDefinition($table, $field_name, FieldDefinitionInterface $field_definition, 
                                        TableMappingInterface $table_mapping, &$table_data) {
    error_log('TripalEntityViewsData::mapFieldDefinition');
    error_log($table .' : '. $field_name .'');
    //error_log(var_export($field_definition, TRUE));
    //error_log(var_export($table_mapping, TRUE));

    parent::mapFieldDefinition($table, $field_name, $field_definition, $table_mapping, $table_data);  

  }

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    error_log(message: "TripalEntityViewsData::getViewsData");
    error_log(message:"run 'drush cr' to see this message");
    $data['tripal_entity']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Tripal Content'),
      'help' => $this->t('The Tripal Content ID.'),
    );
    $field_name = "test_organism_genus_2"; // try with one arbitrary field first
    $entityType = $this->entityType ? $this->entityType->id() : NULL;
    $storage = $this->storage;
    $base_table = $this->entityType
    ->getBaseTable() ?: $this->entityType
    ->id();
    
    // demonstrate (in) accessibility of field definitions
    // gets the field definitions and table map for TripalEntity:
    $field_definitions = $this->entityFieldManager
            ->getBaseFieldDefinitions($this->entityType
           ->id());
    // get the field map for our field. This is possible for any field
    $field_map = $this->entityFieldManager->getFieldMap()[$entityType][$field_name];
    // now try to get the fieldDefinition object. This will return only the base fields....
    $my_field_definitions = $this->entityFieldManager->getFieldDefinitions('tripal_entity', $field_name);
    //var_dump(array_keys($my_field_definitions));
    $table_mapping = $this->storage
            ->getTableMapping($field_definitions);

    // Try to do the same for tripal_entity_type:        
    // In EntityFieldManager.php line 225:                                                                             
    // Getting the base fields is not supported for entity type Tripal Content Type.                                                                                   
    // $this->entityFieldManager->getBaseFieldDefinitions('tripal_entity_type');

    // this is because tripal_entity_type is a ConfigiEntity Type
    
    error_log($entityType) ;
    error_log($storage->getEntityTypeId());
    error_log($base_table);
    $fieldStorageDefinitions = $this->fieldStorageDefinitions;
    $table = 'chado.organism';
    // error_log("retrieving field map and storage definition for '$field_name':");
    //error_log(var_export($field_map, return: true));
    //error_log(var_export($fieldStorageDefinitions[$field_name]->getSettings(), TRUE));

    // If we could map each (foreign) Tripal content field, this might generate the join operations we need
    // We have access to the fieldStoragedefinitions but not the field definitions

    // create the field _definition:
    $new_field_definition = BaseFieldDefinition::createFromFieldStorageDefinition($fieldStorageDefinitions[$field_name]);


    $this->mapFieldDefinition($table, $field_name, $new_field_definition, 
        $table_mapping,  $data[$base_table]);

  
    return $data;
  }

}
