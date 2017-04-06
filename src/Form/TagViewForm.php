<?php

namespace Drupal\xbbcode\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\TwigEnvironment;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A form for viewing a read-only BBCode tag.
 */
class TagViewForm extends TagFormBase {

  /**
   * The twig service.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  /**
   * TagViewForm constructor.
   *
   * @param \Drupal\Core\Template\TwigEnvironment $twig
   *   The twig service.
   */
  public function __construct(TwigEnvironment $twig) {
    $this->twig = $twig;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('twig')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Twig_Error_Loader
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\xbbcode\Entity\TagInterface $tag */
    $tag = $this->entity;

    // Load the template code from a file if necessary.
    if (!$form['template_code']['#default_value'] && $file = $tag->getTemplateFile()) {
      // The source must be loaded directly, because the template class won't
      // have it unless it is loaded from the file cache.
      $source = $this->twig->getLoader()->getSource($file);
      $form['template_code']['#default_value'] = $source;
      $form['template_code']['#rows'] = max(5, substr_count($source, "\n"));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Disable all form elements.
    foreach (Element::children($form) as $key) {
      $form[$key]['#required'] = FALSE;
      // Actually disabling text fields makes their content non-selectable.
      // Just make them look like it, and read-only.
      $type = $form[$key]['#type'];
      if ($type === 'textfield' || $type === 'textarea') {
        $form[$key]['#attributes']['readonly'] = 'readonly';
        $form[$key]['#wrapper_attributes']['class']['form-disabled'] = 'form-disabled';
      }
      else {
        $form[$key]['#disabled'] = TRUE;
      }
    }
    return $form;
  }

  /**
   * Intercepting the submit as a precaution.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Intercepting the save as a precaution.
   *
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {}

}
