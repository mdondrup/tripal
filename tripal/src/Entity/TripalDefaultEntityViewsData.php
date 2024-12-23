<?php

namespace Drupal\tripal\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Tripal Content entities.
 */
class TripalDefaultEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $fieldStorageDefinitions = $this->fieldStorageDefinitions(); // do something sensible here....
    $entityType = $this->entityType() ? $this->entityType->id() : NULL;
    error_log(message: "TripalDefaultEntityViewsData::getViewsData");
    error_log($entityType) ;
    error_log(var_export($fieldStorageDefinitions, TRUE));
    return $data;
  }

}
