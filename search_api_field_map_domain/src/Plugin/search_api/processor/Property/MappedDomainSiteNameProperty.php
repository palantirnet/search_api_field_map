<?php

namespace Drupal\search_api_field_map_domain\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;

/**
 * Defines an "mapped domain site name field" property.
 *
 * @see \Drupal\search_api_field_map_domain\Plugin\search_api\processor\MappedDomainSiteName
 */
class MappedDomainSiteNameProperty extends ConfigurablePropertyBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'type' => 'union',
      'fields' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(FieldInterface $field, array $form, FormStateInterface $form_state) {
    $index = $field->getIndex();
    $configuration = $field->getConfiguration();

    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';
    $form['#tree'] = TRUE;

    $form['field_data'] = [
      '#type' => 'item',
      '#title' => $this->t('Mapped data'),
      '#description' => $this->t('Set the data to be sent to the index for each domain in the data sources set in your index configuration.'),
    ];

    // TODO: Dependency injection.
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultipleSorted(NULL, TRUE);

    foreach ($domains as $domain) {
        $form['field_data'][$domain->get('id')] = [
          '#type' => 'textfield',
          '#title' => $this->t('Field data for %domain', ['%domain' => $domain->get('name')]),
        ];

        // Set the default value if something already exists in our config.
        if (isset($configuration['field_data'][$domain->get('id')])) {
          $form['field_data'][$domain->get('id')]['#default_value'] = $configuration['field_data'][$domain->get('id')];
        }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(FieldInterface $field, array &$form, FormStateInterface $form_state) {
    $values = [
      'field_data' => array_filter($form_state->getValue('field_data')),
    ];

    $field->setConfiguration($values);
  }

}
