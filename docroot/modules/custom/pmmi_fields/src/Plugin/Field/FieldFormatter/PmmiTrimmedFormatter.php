<?php

namespace Drupal\pmmi_fields\Plugin\Field\FieldFormatter;

use Drupal\text\Plugin\Field\FieldFormatter\TextTrimmedFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase as ViewsPluginBase;

/**
 * Plugin implementation of the 'pmmi_trimmed' formatter.
 *
 * @FieldFormatter(
 *   id = "pmmi_trimmed",
 *   label = @Translation("PMMI trimmed"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   },
 *   quickedit = {
 *     "editor" = "form"
 *   }
 * )
 */
class PmmiTrimmedFormatter extends TextTrimmedFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'html' => TRUE,
      'word_boundary' => TRUE,
      'ellipsis' => FALSE,
      'strip_tags' => FALSE,
      'preserve_tags' => '',
      'nl2br' => FALSE,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $triggering_element = $form_state->getTriggeringElement();
    $element['word_boundary'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Trim only on a word boundary'),
      '#description' => $this->t('If checked, this field be trimmed only on a word boundary. This is guaranteed to be the maximum characters stated or less. If there are no word boundaries this could trim a field to nothing.'),
      '#default_value' => $this->getSetting('word_boundary'),
    );
    $element['ellipsis'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Add "…" at the end of trimmed text'),
      '#default_value' => $this->getSetting('ellipsis'),
    );
    $element['html'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Field can contain HTML'),
      '#description' => $this->t('An HTML corrector will be run to ensure HTML tags are properly closed after trimming.'),
      '#default_value' => $this->getSetting('html'),
    );
    $element['strip_tags'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Strip HTML tags'),
      '#default_value' => $this->getSetting('strip_tags'),
    );
    $element['preserve_tags'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Preserve certain tags'),
      '#default_value' => $this->getSetting('preserve_tags'),
      '#description' => $this->t('List the tags that need to be preserved during the stripping process. example &quot;&lt;p&gt; &lt;br&gt;&quot; which will preserve all p and br elements'),
      '#states' => array(
        'visible' => array(
          ":input[name='fields[{$triggering_element['#field_name']}][settings_edit_form][settings][strip_tags]']" => array(
            'checked' => TRUE,
          ),
        ),
      ),
    );
    $element['nl2br'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Convert newlines to HTML &lt;br&gt; tags'),
      '#default_value' => $this->getSetting('nl2br'),
    );

    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();


    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#type' => 'processed_text',
        '#text' => NULL,
        '#format' => $item->format,
        '#langcode' => $item->getLangcode(),
      );

      $elements[$delta]['#text'] = $item->value;
      $alter = [
        'max_length' => $this->getSetting('trim_length'),
        'html' => $this->getSetting('html'),
        'ellipsis' => $this->getSetting('ellipsis'),
        'word_boundary' => $this->getSetting('word_boundary'),
        'strip_tags' => $this->getSetting('strip_tags'),
        'preserve_tags' => $this->getSetting('preserve_tags'),
        'nl2br' => $this->getSetting('nl2br'),
      ];

      $value = $item->value;

      // New line to <br>.
      if (!empty($alter['nl2br'])) {
        $value = nl2br($value);
      }

      // Strip tags.
      if (!empty($alter['strip_tags'])) {
        $value = strip_tags($value, $alter['preserve_tags']);
      }

      // Trim text.
      $elements[$delta]['#text'] = ViewsPluginBase::trimText($alter, $value);
    }

    return $elements;
  }

}
