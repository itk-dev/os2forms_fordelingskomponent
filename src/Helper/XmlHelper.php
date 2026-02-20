<?php

namespace Drupal\os2forms_fordelingskomponent\Helper;

use Drupal\os2forms_fordelingskomponent\Exception\InvalidXmlTemplateException;
use Drupal\os2forms_fordelingskomponent\Settings\HandlerSettings;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Twig\TemplateWrapper;

/**
 * XML helper.
 */
class XmlHelper {

  /**
   * Constructor.
   */
  public function __construct(
    #[Autowire(service: 'twig')]
    private readonly Environment $twig,
  ) {
  }

  /**
   * Wrapper to enable strict variables on the Twig environment.
   *
   * To not break the Twig instance for others, we restore the strict variables
   * state on the instance after running our code.
   *
   * Important: All code in this class that uses the Twig instance must be run
   * with this wrapper.
   */
  private function useTwig(callable $callback): mixed {
    try {
      $strictVariables = $this->twig->isStrictVariables();
      $this->twig->enableStrictVariables();
      return $callback();
    } finally {
      if (isset($strictVariables) && $strictVariables) {
        $this->twig->enableStrictVariables();
      }
      else {
        $this->twig->disableStrictVariables();
      }
    }
  }

  /**
   * Render XML template.
   */
  public function render(string $template, array $context, bool $validateXml = TRUE): string {
    try {
      if ($validateXml) {
        $this->checkXml($template);
      }

      return $this->useTwig(
        fn () => $this->createTemplate($template)->render($context)
      );
    }
    catch (\Throwable $exception) {
      throw new InvalidXmlTemplateException($exception->getMessage(), $exception->getCode(), $exception);
    }
  }

  /**
   * Get render context.
   */
  public function getRenderContext(HandlerSettings $handlerSettings, WebformSubmissionInterface $submission) {
    return [
      'handler' => ['settings' => $handlerSettings],
      'submission' => $submission,
      'webform_submission' => $submission,
    ];
  }

  /**
   * Check that Twig template is valid, i.e. has no syntax errors.
   */
  public function validateTemplate(string $template): void {
    try {
      $this->createTemplate($template);
    }
    catch (\Throwable $exception) {
      throw new InvalidXmlTemplateException($exception->getMessage(), $exception->getCode(), $exception);
    }
  }

  /**
   * Check that XML is valid. Optionally validate using an XSD.
   *
   * @throws \Drupal\os2forms_fordelingskomponent\Exception\InvalidXmlTemplateException
   *   An exception.
   */
  public function validateXml(string $xml, ?string $xsdUrl = NULL, bool $loadXsdContent = FALSE): void {
    $this->checkXml($xml);

    if (NULL === $xsdUrl) {
      return;
    }

    // https://www.php.net/manual/en/function.libxml-use-internal-errors.php
    $useInternalErrors = libxml_use_internal_errors(TRUE);

    if ($loadXsdContent) {
      $content = file_get_contents($xsdUrl);
      if (FALSE === $content) {
        throw new InvalidXmlTemplateException(sprintf('Error loading XSD: %s', $xsdUrl));
      }
    }

    try {
      $reader = new \XMLReader();
      $path = tempnam(sys_get_temp_dir(), 'os2forms_fordelingskomponent');
      file_put_contents($path, $xml);
      $reader->open($path);

      $reader->setParserProperty(\XMLReader::VALIDATE, TRUE);
      $reader->setSchema($xsdUrl);

      $errors = [];

      while ($reader->read()) {
        if (!$reader->isValid()) {
          $error = \libxml_get_last_error();
          if ($error instanceof \libXMLError) {
            if (!str_contains($error->message, 'no DTD found')) {
              $errors[] = $error;
            }
          }
        }
      }

      if ($errors) {
        $message = $this->formatLibXmlErrors($errors);

        libxml_clear_errors();

        throw new InvalidXmlTemplateException('Error validating XML:' . PHP_EOL . $message);
      }

    } finally {
      if (isset($path) && is_file($path)) {
        unlink($path);
      }
      libxml_clear_errors();
      libxml_use_internal_errors($useInternalErrors);
    }
  }

  /**
   * Check that XML is well-formed.
   */
  private function checkXml(string $template): void {
    // https://www.php.net/manual/en/function.libxml-use-internal-errors.php
    $useInternalErrors = \libxml_use_internal_errors(TRUE);
    try {
      $doc = new \DomDocument();
      if (!$doc->loadXML($template)) {
        $message = $this->formatLibXmlErrors(libxml_get_errors());

        libxml_clear_errors();

        throw new InvalidXmlTemplateException('Error loading XML:' . PHP_EOL . $message);
      }
    }
    catch (\Throwable $exception) {
      throw new InvalidXmlTemplateException($exception->getMessage(), $exception->getCode(), $exception);
    } finally {
      libxml_use_internal_errors($useInternalErrors);
    }
  }

  /**
   * Create a Twig template.
   */
  private function createTemplate(string $template): TemplateWrapper {
    return $this->useTwig(
      fn() => $this->twig->createTemplate($template)
    );
  }

  /**
   * Format a list of XML errors.
   */
  private function formatLibXmlErrors(array $errors): string {
    return implode(
      PHP_EOL,
      array_unique(
        array_map(
          static fn (\LibXMLError $error): string =>   sprintf('%d:%d: %s', $error->line, $error->column, $error->message),
          $errors
        )
      )
    );
  }

}
