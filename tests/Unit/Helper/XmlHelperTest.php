<?php

namespace Drupal\os2forms_fordelingskomponent\Test\Unit\Helper;

use Drupal\os2forms_fordelingskomponent\Exception\InvalidXmlException;
use Drupal\os2forms_fordelingskomponent\Helper\XmlHelper;
use Drupal\os2forms_fordelingskomponent\Test\Unit\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Xml helper test case.
 */
#[CoversClass(XmlHelper::class)]
class XmlHelperTest extends AbstractTestCase {
  /**
   * The Twig environment.
   */
  private Environment $twig;

  /**
   * The XML helper.
   */
  private XmlHelper $helper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // @todo Do this is the right way.
    $this->twig = new Environment(new ArrayLoader());
    $this->helper = new XmlHelper($this->twig);
  }

  /**
   * Test strict variables.
   */
  public function testStrictVariables(): void {
    $this->assertFalse($this->twig->isStrictVariables());
    $this->helper->validateTemplate('<e/>');
    $this->helper->validateXml('<e/>');
    $this->helper->render('<e/>', []);
    $this->assertFalse($this->twig->isStrictVariables());
  }

  /**
   * Test render.
   */
  #[DataProviderExternal(XmlHelperTestDataProvider::class, 'provideRenderData')]
  public function testRender(
    string $template,
    array $context,
    string|InvalidXmlException $expected,
  ) {
    if ($expected instanceof InvalidXmlException) {
      $this->expectException($expected::class);
    }

    $actual = $this->helper->render($template, $context);
    $this->assertXmlStringEqualsXmlString($expected, $actual);
  }

  /**
   * Test validate template.
   */
  #[DataProviderExternal(XmlHelperTestDataProvider::class, 'provideValidateTemplateData')]
  public function testValidateTemplate(
    string $template,
    ?InvalidXmlException $expected = NULL,
  ) {
    if ($expected instanceof InvalidXmlException) {
      $this->expectException($expected::class);
    }

    $this->helper->validateTemplate($template);
    $this->assertTrue(TRUE);
  }

  /**
   * Test validate XML.
   */
  #[DataProviderExternal(XmlHelperTestDataProvider::class, 'provideValidateXmlData')]
  public function testValidateXml(
    string $template,
    string $xsdUrl,
    ?InvalidXmlException $expected = NULL,
  ) {
    if ($expected instanceof InvalidXmlException) {
      $this->expectException($expected::class);
    }

    $this->helper->validateXml($template, $xsdUrl);
    $this->assertTrue(TRUE);
  }

}
