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
    $host_server_name = parse_url($host, PHP_URL_HOST);
    $domain = $purl_service_data['domain'];
    \Drupal::logger("flvc_purl_validator")->info("host={$host}");
    \Drupal::logger("flvc_purl_validator")->info("domain={$domain}");

    $error_purls_host = '';
    $error_purls_domain = '';
    $error_purls_format = '';
    $error_purls_duplicate = '';

    foreach ($items as $item) {
      $purl = $item->getUrl()->toString();
      \Drupal::logger("flvc_purl_validator")->info("validate value={$purl}");

      $purl_components = parse_url($purl);

      // check purl has configured host
      if ($purl_components['host'] != $host_server_name) {
        $error_purls_host = $error_purls_host . $purl . ' ';
      }

      // check purl has configured domain
      if (!(strpos($purl_components['path'], $domain . '/') === 0)) {
        $error_purls_domain = $error_purls_domain . $purl . ' ';
      }

      // check purl path format
      if ((preg_match("/^\/[A-Za-z]+\/[A-Za-z0-9_\-()\.]+$/", $purl_components['path']) == 0) ||
          (isset($purl_components['query'])) ||
          (isset($purl_components['fragment']))) {
        $error_purls_format = $error_purls_format . $purl . ' ';
      }

      // if new ingest, check if purl exists
/*
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
*/
    }

    $error_msg_host = '';
    $error_msg_domain = '';
    $error_msg_format = '';
    $error_msg_duplicate = '';

    if (strlen($error_purls_host) > 0) {
      $error_msg_host = "\nThe following PURLs have incorrect server names: {$error_purls_host}. Allowable server names are {$host_server_name}.";
    }
    if (strlen($error_purls_domain) > 0) {
      $error_msg_domain = "The following PURLs have incorrect domains: {$error_purls_domain}. Allowable PURL domains are {$domain}.";
    }
    if (strlen($error_purls_format) > 0) {
      $error_msg_format = "The following PURLs have invalid characters: {$error_purls_format}. Characters can be alphanumeric, underscore, hyphen, period, or parentheses with no spaces.";
    }
    if (strlen($error_purls_duplicate) > 0) {
      $error_msg_duplicate = "The following PURLs already exist: {$error_purls_duplicate}.";
    }

    if ((strlen($error_purls_host) > 0)||(strlen($error_purls_domain) > 0)||
        (strlen($error_purls_format) > 0)||(strlen($error_purls_duplicate) > 0)) {
      $this->context->addViolation($constraint->errorMessage, ['%host_error' => $error_msg_host, '%domain_error' => $error_msg_domain, '%format_error' => $error_msg_format, '%duplicate_error' => $error_msg_duplicate]);
    }
  }
}
