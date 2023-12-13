<?php

namespace Drupal\flvc_purl_validator\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a PURL host constraint.
 *
 * @Constraint(
 *   id = "PurlValidatorPurlDomain",
 *   label = @Translation("PURL Host", context = "Validation"),
 * )
 *
 * @DCG
 * To apply this constraint on a particular field implement
 * hook_entity_type_build().
 */
class PurlDomainConstraint extends Constraint {

  public $errorInvalidDomain = 'Allowable PURL domains are %domain.  The following PURLs have incorrect formats: %purls';

}
