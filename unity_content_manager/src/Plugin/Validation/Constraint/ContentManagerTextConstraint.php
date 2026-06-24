<?php

namespace Drupal\unity_content_manager\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for links receiving data allowed by its settings.
 */
#[Constraint(
  id: 'ContentManagerText',
  label: new TranslatableMarkup('Content manager data valid for content manager type.', [], ['context' => 'Validation'])
)]
class ContentManagerTextConstraint extends SymfonyConstraint {

  public $message = "You must provide link text with the content manager URL";

}
