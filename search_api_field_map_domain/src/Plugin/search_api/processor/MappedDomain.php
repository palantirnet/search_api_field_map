<?php

namespace Drupal\search_api_field_map_domain\Plugin\search_api\processor;

use Drupal\domain\DomainNegotiator;
use Drupal\domain\DomainStorage;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api_field_map_domain\Plugin\search_api\processor\Property\MappedDomainProperty;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create a field that maps domains to site names.
 *
 * @see \Drupal\search_api_field_map_domain\Plugin\search_api\processor\Property\MappedDomainProperty
 *
 * @SearchApiProcessor(
 *   id = "mapped_domain",
 *   label = @Translation("Mapped domain"),
 *   description = @Translation("Create a field that maps domains to provided values."),
 *   stages = {
 *     "add_properties" = 20,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class MappedDomain extends ProcessorPluginBase {
  // @var $domainNegotiator DomainNegotiator.
  private $domainNegotiator;

  // @var $domainStorage DomainStorage.
  private $domainStorage;

  /**
   * @param ContainerInterface $container
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   *
   * @return MappedDomain
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    $domain_negotiator = $container->get('domain.negotiator');
    $domain_storage = $container->get('entity_type.manager')->getStorage('domain');

    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $domain_negotiator,
      $domain_storage
    );
  }

  /**
   * MappedDomain constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param DomainNegotiator $domain_negotiator
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DomainNegotiator $domain_negotiator, DomainStorage $domain_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->domainNegotiator = $domain_negotiator;
    $this->domainStorage = $domain_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Mapped domain'),
        'description' => $this->t('Create a field that maps domains to provided values.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['mapped_domain'] = new MappedDomainProperty($definition, $this->domainStorage);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Get all of the mapped fields on our item.
    $mapped_fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'mapped_domain');

    // Get the entity object, bail if there's somehow not one.
    $entity = $item->getOriginalObject()->getValue();
    if (!$entity) {
      // Apparently we were active for a wrong item.
      return;
    }

    foreach ($mapped_fields as $mapped_field) {

      // Get configuration for the field.
      $configuration = $mapped_field->getConfiguration();

      // Get the current domain.
      $domain = $this->domainNegotiator->getActiveId();

      // If there's a config item for the entity and bundle type we're in, set the value for the field.
      if(!empty($configuration['field_data'][$domain])) {
        // If the token replacement produces a value, add to this item.
        $value = $configuration['field_data'][$domain];

        // Do not use setValues(), since that doesn't preprocess the values according to their data type.
        $mapped_field->addValue($value);
      }
    }
  }
}
