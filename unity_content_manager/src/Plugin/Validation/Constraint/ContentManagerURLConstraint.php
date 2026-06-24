<?php

namespace Drupal\unity_content_manager\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for links receiving data allowed by its settings.
 */
#[Constraint(
  id: 'ContentManagerURL',
  label: new TranslatableMarkup('Content manager data valid for content manager type.', [], ['context' => 'Validation'])
)]
class ContentManagerURLConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = "The path '@url' is invalid. This link must begin with contentmanager://";

}
