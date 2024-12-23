<?/**
 * @var Drupal\Core\PathProcessor\PathProcessorDecode
 */

 use Drupal\Core\Routing\RouteMatchInterface;
 use Drupal\field\Entity\FieldConfig;
 use Drupal\field\Entity\FieldStorageConfig;

 $path_processor_decode_service = \Drupal::service('path_processor_decode');

$collections = [
    'test_chado',
  ];

  // Import the content types.
  $content_type_setup = \Drupal::service('tripal.tripalentitytype_collection');
  $content_type_setup->install($collections);

  // Import the fields.
  $fields = \Drupal::service('tripal.tripalfield_collection');
  $fields->install($collections);
  print("Done\n");