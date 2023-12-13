<?php

namespace Drupal\flvc_purl_validator\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Validates the PURL host constraint.
 */
class PurlHostConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {

    $purl_service_data = \Drupal::service('entity_type.manager')->getStorage('dgiactions_servicedata')->load('purl_service_data')->getData();
    $host = $purl_service_data['host'];
    $host_server_name = parse_url($host, PHP_URL_HOST);
    \Drupal::logger("flvc_purl_validator")->info("HOST host={$host}");

    $error_purls = '';

    foreach ($items as $item) {
      $purl = $item->getUrl()->toString();
      \Drupal::logger("flvc_purl_validator")->info("HOST validate value={$purl}");

      $purl_components = parse_url($purl);

      // check format of value with configured host -> invalid host error
      if ($purl_components['host'] != $host_server_name) {
        $error_purls = $error_purls . $purl . ' ';
      }
    }

    if (strlen($error_purls) > 0) {
      $this->context->addViolation($constraint->errorInvalidHost, ['%purls' => $error_purls, '%host' => $host_server_name]);
    }
  }
}
