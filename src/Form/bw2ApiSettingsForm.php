<?php

namespace Drupal\bw2_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide the settings form for entity clone.
 */
class bw2ApiSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['bw2_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bw2_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bw2_api.settings');

    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base url'),
      '#default_value' => $config->get('base_url'),
      '#description' => $this->t('The URL of bw2 API with protocol (https).'),
      '#required' => TRUE,
    ];
    /** GPY: IS IT NEEDED? */
    $form['newsletter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Newsletter Group ID'),
      '#default_value' => $config->get('newsletter'),
      '#description' => $this->t('The newsletter group ID that will be used for bw2.'),
      '#required' => TRUE,
    ];

    $form['portaluid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BW2 Portal ID'),
      '#default_value' => $config->get('portaluid'),
      '#description' => $this->t('The portal uid that will be used to connect to bw2 API.'),
      '#required' => TRUE,
    ];

    $form['objectuid_get'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Object UID for GET Requests'),
      '#default_value' => $config->get('objectuid_get'),
      '#description' => $this->t('The object ID that will be used for all GET requests to bw2 API.'),
      '#required' => TRUE,
    ];

    $form['objectuid_post'] = [
      #type' => 'textfield',
      '#title' => $this->t('Object UID for POST Requests'),
      '#default_value' => $config->get('objectuid_post'),
      '#description' => $this->t('The object ID that will be used for all POST / PUT / PATCH requests to bw2 API.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bw2_api.settings');
    $form_state->cleanValues();

    $config->set('base_url', $form_state->getValue('base_url'));
    $config->set('portaluid', $form_state->getValue('portaluid'));
    $config->set('objectuid_get', $form_state->getValue('objectuid_get'));
    $config->set('objectuid_post', $form_state->getValue('objectuid_post'));
    $config->set('newsletter', $form_state->getValue('newsletter'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
