<?php

namespace Drupal\unity_content_manager\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the LinkExternalProtocols constraint.
 */
class ContentManagerURLConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   *
   * phpcs:ignore Drupal.Commenting.FunctionComment.VoidReturn
   *
   * @return void
   */
  public function validate($value, Constraint $constraint) {
    if (isset($value)) {
      $url = $value->url;

      // Disallow non content manager links.
      if ($url && !preg_match('/^contentmanager:\/\/.*/', $url)) {
        // @phpstan-ignore-next-line
        $this->context->addViolation($constraint->message, ['@url' => $url]);
      }
    }
  }

}
