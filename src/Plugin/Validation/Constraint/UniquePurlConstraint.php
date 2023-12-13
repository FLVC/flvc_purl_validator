<?php

namespace Drupal\flvc_purl_validator\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides an Unique PURL constraint.
 *
 * @Constraint(
 *   id = "PurlValidatorUniquePurl",
 *   label = @Translation("Unique PURL", context = "Validation"),
 * )
 *
 * @DCG
 * To apply this constraint on a particular field implement
 * hook_entity_type_build().
 */
class UniquePurlConstraint extends Constraint {

  public $errorMessage = 'The PURL Identifier field has errors listed below:<br>%host_error<br>%domain_error<br>%format_error<br>%duplicate_error';

}
