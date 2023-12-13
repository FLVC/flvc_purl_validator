<?php

namespace Drupal\flvc_purl_validator\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a PURL host constraint.
 *
 * @Constraint(
 *   id = "PurlValidatorPurlHost",
 *   label = @Translation("PURL Host", context = "Validation"),
 * )
 *
 * @DCG
 * To apply this constraint on a particular field implement
 * hook_entity_type_build().
 */
class PurlHostConstraint extends Constraint {

  public $errorInvalidHost = 'The following PURLs have incorrect server names: %purls.  Allowable server names are %host.';

}
