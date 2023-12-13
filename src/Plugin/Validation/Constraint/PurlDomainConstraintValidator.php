<?php

namespace Drupal\flvc_purl_validator\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Validates the PURL domain constraint.
 */
class PurlDomainConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {

    $purl_service_data = \Drupal::service('entity_type.manager')->getStorage('dgiactions_servicedata')->load('purl_service_data')->getData();
    $domain = $purl_service_data['domain'];
    \Drupal::logger("flvc_purl_validator")->info("DOMAIN host={$domain}");

    $error_purls = '';

    foreach ($items as $item) {
      $purl = $item->getUrl()->toString();
      \Drupal::logger("flvc_purl_validator")->info("DOMAIN validate value={$purl}");

      $purl_components = parse_url($purl);
      \Drupal::logger("flvc_purl_validator")->info("DOMAIN validate path={$purl_components['path']}");

      // check format of value with configured host -> invalid host error
      if (!(strpos($purl_components['path'], $domain . '/') === 0)) {
        $error_purls = $error_purls . $purl . ' ';
      }
    }

    if (strlen($error_purls) > 0) {
      $this->context->addViolation($constraint->errorInvalidDomain, ['%purls' => $error_purls, '%domain' => $domain]);
    }
  }
}
