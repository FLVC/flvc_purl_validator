<?php

namespace Drupal\flvc_purl_validator\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a PURL character constraint.
 *
 * @Constraint(
 *   id = "PurlValidatorPurlCharacter",
 *   label = @Translation("PURL Host", context = "Validation"),
 * )
 *
 * @DCG
 * To apply this constraint on a particular field implement
 * hook_entity_type_build().
 */
class PurlCharacterConstraint extends Constraint {

  public $errorInvalidCharacters = 'The following PURL identifiers have invalid characters (characters can be alphanumeric, underscore, hyphen, period, or parentheses with no spaces): %purls';
}
