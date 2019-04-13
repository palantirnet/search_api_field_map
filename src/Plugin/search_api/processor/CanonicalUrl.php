<?php

namespace Drupal\search_api_field_map\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api_field_map\Plugin\search_api\processor\Property\CanonicalUrlProperty;


/**
 * Adds the Urls to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "search_api_canonical_url",
 *   label = @Translation("Canonical URL"),
 *   description = @Translation("Adds a canonical flag to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class CanonicalUrl extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Canonical URL'),
        'description' => $this->t('Preferred URL for this content'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['search_api_canonical_url'] = new CanonicalUrlProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'search_api_canonical_url');
    $source = NULL;
    if ($this->useDomainAccess()) {
      $id = $item->getDatasource()->getItemId($item->getOriginalObject());
      // @TODO This entity load routine is failing.
      if ($entity = $item->getDatasource()->load($id) && $entity instanceof FieldableEntityInterface) {
        $source = domain_source_get($entity);
      }
    }
    if (is_null($source)) {
      foreach ($fields as $field) {
        $field->addValue('null');
      }
    }
    else {
      $url = $item->getDatasource()->getItemUrl($item->getOriginalObject());
      if ($url) {
        foreach ($fields as $field) {
          $url = $url->setAbsolute()->toString();
          $field->addValue($url);
        }
      }
    }
  }

  /**
   * Whether to use the canonical value from Domain Source.
   *
   * @return bool
   */
  protected function useDomainAccess() {
    return defined('DOMAIN_SOURCE_FIELD');
  }
}
