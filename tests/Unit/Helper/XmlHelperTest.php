<?php

namespace Drupal\os2forms_fordelingskomponent\Test\Unit\Helper;

use Drupal\os2forms_fordelingskomponent\Exception\InvalidXmlException;
use Drupal\os2forms_fordelingskomponent\Helper\XmlHelper;
use Drupal\os2forms_fordelingskomponent\Test\Unit\AbstractTestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Xml helper test case.
 */
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
   * @covers       \Drupal\os2forms_fordelingskomponent\Helper\XmlHelper
   */
  public function testStrictVariables(): void {
    $this->assertFalse($this->twig->isStrictVariables());
    $this->helper->validateTemplate('<e/>');
    $this->helper->validateXml('<e/>');
    $this->helper->render('<e/>', []);
    $this->assertFalse($this->twig->isStrictVariables());
  }

  /**
   * @covers       \Drupal\os2forms_fordelingskomponent\Helper\XmlHelper
   * @dataProvider  \Drupal\os2forms_fordelingskomponent\Test\Unit\Helper\XmlHelperTestDataProvider::provideRenderData
   */
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
   * @covers       \Drupal\os2forms_fordelingskomponent\Helper\XmlHelper
   * @dataProvider  \Drupal\os2forms_fordelingskomponent\Test\Unit\Helper\XmlHelperTestDataProvider::provideValidateTemplateData()
   */
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
   * @covers       \Drupal\os2forms_fordelingskomponent\Helper\XmlHelper
   * @dataProvider  \Drupal\os2forms_fordelingskomponent\Test\Unit\Helper\XmlHelperTestDataProvider::provideValidateXmlData()
   */
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
