<?php

namespace Drupal\flvc_purl_validator\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Validates the Unique PURL constraint.
 */
class UniquePurlConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {

    $client = \Drupal::httpClient();

    $entity = $items->getEntity();
    if ($entity->isNew()) {
      \Drupal::logger("flvc_purl_validator")->info("entity is new ingest");
    }
    else {
      \Drupal::logger("flvc_purl_validator")->info("entity is existing edit");
      if (isset($entity->original)) {
        \Drupal::logger("flvc_purl_validator")->info("entity has original as value");
      }
      $entityOriginal = \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged($entity->id());
      if (isset($entityOriginal)) {
        \Drupal::logger("flvc_purl_validator")->info("entity has original in storage");
        if ($entityOriginal->hasField('field_purl_identifier')) {
          \Drupal::logger("flvc_purl_validator")->info("original entity has field_purl_identifier");
          $purlList =  $entityOriginal->get('field_purl_identifier')->getValue();
          foreach ($purlList as $purl) {
            \Drupal::logger("flvc_purl_validator")->info("original purl={$purl['uri']}");
          }
        }
      }

    }

    $purl_service_data = \Drupal::service('entity_type.manager')->getStorage('dgiactions_servicedata')->load('purl_service_data')->getData();
    $host = $purl_service_data['host'];
    $domain = $purl_service_data['domain'];
    \Drupal::logger("flvc_purl_validator")->info("host={$host}");
    \Drupal::logger("flvc_purl_validator")->info("domain={$domain}");

    foreach ($items as $item) {
      //$purl = $item->getString();
      $purl = $item->getUrl()->toString();
      \Drupal::logger("flvc_purl_validator")->info("validate value={$purl}");

      // check format of value with configured host -> invalid host error
      // parse purl path
      // check purl path format -> invalid characters
      // on new ingest, enforce domain -> invalid domain

    $request = new Request('GET', "{$host}/admin/purl/flvc/demo/en/node/1056");
    $requestParams = array([
      'headers' => [
        'Content-Type' => 'application/json;charset=UTF-8'
    ]]);
    $purlId = 0;
    try {
      $response = $client->send($request, $requestParams);
      $body = $response->getBody();
      \Drupal::logger("flvc_purl_validator")->info("DEBUG body={$body}");
      if ($body) {
        $json = json_decode($body, TRUE);
        $purlId = $json['purlId'] ?? 0;
      }
    }
    catch (RequestException $e) {
      $purlId = 0;
    }
    \Drupal::logger("flvc_purl_validator")->info("DEBUG purlId={$purlId}");

      if ($purl == 'http://badpurl.flvc.org') {
        $this->context->addViolation($constraint->errorMessage);
      }
    }
  }
}
