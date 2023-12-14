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
    $institution = $purl_service_data['institution'];
    \Drupal::logger("flvc_purl_validator")->info("host={$host}");
    \Drupal::logger("flvc_purl_validator")->info("domain={$domain}");
    \Drupal::logger("flvc_purl_validator")->info("institution={$institution}");

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
      if ((preg_match("/^\/[A-Za-z0-9_\-()\.\/]+$/", $purl_components['path']) == 0) ||
          (isset($purl_components['query'])) ||
          (isset($purl_components['fragment']))) {
        $error_purls_format = $error_purls_format . $purl . ' ';
      }

      // look for existing PURL
      $purlInfo = $this->getPurlInfo($client, $host, $purl_components['path']);
      if (isset($purlInfo)) {
        \Drupal::logger("flvc_purl_validator")->info("DEBUG purlId={$purlInfo['purlId']}");
        if ($entity->isNew()) {
          // if ingest of new entity, then this is a duplicate violation
          $error_purls_duplicate = $error_purls_duplicate . $purl . ' ';
        }
        else if (($institution != $purlInfo['institutionCode'])) {
          // if edit of existing entity and institution not match, then this is a duplicate violation
          $error_purls_duplicate = $error_purls_duplicate . $purl . ' ';
        }
      }
      else {
        \Drupal::logger("flvc_purl_validator")->info("DEBUG purl not found");
      }
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

  protected function getPurlInfo(\GuzzleHttp\ClientInterface $client, string $host, string $purlPath): ?array {
    //$client = \Drupal::httpClient();
    $request = new Request('GET', "{$host}/admin/purl{$purlPath}");
    $requestParams = array([
      'headers' => [
        'Content-Type' => 'application/json;charset=UTF-8'
    ]]);
    try {
      $response = $client->send($request, $requestParams);
      $body = $response->getBody();
      \Drupal::logger("flvc_purl_validator")->info("DEBUG body={$body}");
      if ($body) {
        $json = json_decode($body, TRUE);
        //return $json['purlId'] ?? 0;
        return $json;
      }
    }
    catch (RequestException $e) {
      return null;
    }
  }
}
