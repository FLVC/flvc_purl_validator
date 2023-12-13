<?php

namespace Drupal\flvc_purl_validator\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Validates the PURL character constraint.
 */
class PurlCharacterConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {

    $error_purls = '';

    foreach ($items as $item) {
      $purl = $item->getUrl()->toString();
      \Drupal::logger("flvc_purl_validator")->info("CHAR validate value={$purl}");

      $purl_components = parse_url($purl);
      \Drupal::logger("flvc_purl_validator")->info("CHAR validate path={$purl_components['path']}");

      // check format of value with configured host -> invalid host error
      //if ((preg_match("/[^A-Za-z0-9_\-()\.]/", $purl_components['path'])) ||
      if ((preg_match("/^\/[A-Za-z]+\/[A-Za-z0-9_\-()\.]+$/", $purl_components['path']) == 0) ||
          (strlen($purl_components['query']) > 0) ||
          (strlen($purl_components['fragment']) > 0)) {
        $error_purls = $error_purls . $purl . ' ';
      }
    }

    if (strlen($error_purls) > 0) {
      $this->context->addViolation($constraint->errorInvalidCharacters, ['%purls' => $error_purls, '%domain' => $domain]);
    }
  }
}
