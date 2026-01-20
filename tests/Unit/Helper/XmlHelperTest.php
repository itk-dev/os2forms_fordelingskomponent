<?php

namespace Drupal\os2forms_fordelingskomponent\Test\Unit\Helper;

use Drupal\os2forms_fordelingskomponent\Exception\InvalidXmlTemplateException;
use Drupal\os2forms_fordelingskomponent\Helper\XmlHelper;
use Drupal\os2forms_fordelingskomponent\Test\Unit\AbstractTestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Xml helper test case.
 */
class XmlHelperTest extends AbstractTestCase {
  /**
   * The XML helper.
   */
  private XmlHelper $helper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // @todo Do this is the right way.
    $this->helper = new XmlHelper(new Environment(new ArrayLoader()));
  }

  /**
   * @covers       \Drupal\os2forms_fordelingskomponent\Helper\XmlHelper
   * @dataProvider provideRenderData
   */
  public function testRender(
    string $template,
    array $context,
    string|InvalidXmlTemplateException $expected,
  ) {
    if ($expected instanceof InvalidXmlTemplateException) {
      $this->expectException($expected::class);
    }

    $actual = $this->helper->render($template, $context);
    $this->assertXmlStringEqualsXmlString($expected, $actual);
  }

  /**
   * @covers       \Drupal\os2forms_fordelingskomponent\Helper\XmlHelper
   * @dataProvider provideValidateData
   */
  public function testValidate(
    string $template,
    string $xsdUrl,
    ?InvalidXmlTemplateException $expected = NULL,
  ) {
    if ($expected instanceof InvalidXmlTemplateException) {
      $this->expectException($expected::class);
    }

    $this->helper->validate($template, $xsdUrl);
    $this->assertTrue(TRUE);
  }

  /**
   * Data provider.
   */
  public static function provideRenderData(): iterable {
    yield [
      'This is not an XML document',
    [],
      new InvalidXmlTemplateException(),
    ];

    yield [
      <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Anmodning xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <Header>
        <Myndighed>urn:oio:cvr-nr:{{ handlers.settings.cvr }}</Myndighed>
        <ModtagetDato>{{ webform_submission.completed|date('Y-m-d') }}</ModtagetDato>
        <KLE>{{ handlers.settings.kle }}</KLE>
    </Header>
    <AnsoegerOplysninger>
        <Ansoeger>
            <Fornavn>{{ webform_submission.data.fornavn }}</Fornavn>
            <Efternavn>{{ webform_submission.data.efternavn }}</Efternavn>
            <Personnummer>urn:oio:cpr:0000000000</Personnummer>
        </Ansoeger>
    </AnsoegerOplysninger>
    <Sagstype>
        <AlmindeligtHelbredstillaeg>Medicin</AlmindeligtHelbredstillaeg>
    </Sagstype>
    <Underskriftsoplysninger>
        <Underskrift>Underskrift0</Underskrift>
        <Underskriftsdato>{{ webform_submission.completed|date('Y-m-d') }}</Underskriftsdato>
    </Underskriftsoplysninger>
</Anmodning>
XML,
      [
        'handlers' => [
          'settings' => [
            'cvr' => '12345678',
            'kle' => '01.02.03',
          ],
        ],
        'webform_submission' => self::createWebformSubmission([
          'completed' => (new \DateTimeImmutable('2001-01-01T00:00:00Z'))->getTimestamp(),
          'data' => [
            'fornavn' => 'Anders',
            'efternavn' => 'And',
          ],
        ]),
      ],
      <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Anmodning xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <Header>
        <Myndighed>urn:oio:cvr-nr:12345678</Myndighed>
        <ModtagetDato>2001-01-01</ModtagetDato>
        <KLE>01.02.03</KLE>
    </Header>
    <AnsoegerOplysninger>
        <Ansoeger>
            <Fornavn>Anders</Fornavn>
            <Efternavn>And</Efternavn>
            <Personnummer>urn:oio:cpr:0000000000</Personnummer>
        </Ansoeger>
    </AnsoegerOplysninger>
    <Sagstype>
        <AlmindeligtHelbredstillaeg>Medicin</AlmindeligtHelbredstillaeg>
    </Sagstype>
    <Underskriftsoplysninger>
        <Underskrift>Underskrift0</Underskrift>
        <Underskriftsdato>2001-01-01</Underskriftsdato>
    </Underskriftsoplysninger>
</Anmodning>
XML,
    ];
  }

  /**
   * Create webform submission.
   */
  private static function createWebformSubmission(array $values): array {
    return $values;
  }

  /**
   * Data provider.
   */
  public static function provideValidateData(): iterable {
    yield [
      <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Anmodning xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <Header>
        <Myndighed>urn:oio:cvr-nr:12345678</Myndighed>
        <ModtagetDato>2001-01-01</ModtagetDato>
        <KLE>01.02.03</KLE>
    </Header>
    <AnsoegerOplysninger>
        <Ansoeger>
            <Fornavn>Anders</Fornavn>
            <Efternavn>And</Efternavn>
            <Personnummer>urn:oio:cpr:0000000000</Personnummer>
        </Ansoeger>
    </AnsoegerOplysninger>
    <Sagstype>
        <AlmindeligtHelbredstillaeg>Medicin</AlmindeligtHelbredstillaeg>
    </Sagstype>
    <Underskriftsoplysninger>
        <Underskrift>Underskrift0</Underskrift>
        <Underskriftsdato>2001-01-01</Underskriftsdato>
    </Underskriftsoplysninger>
</Anmodning>
XML,
      'file://' . realpath(__DIR__ . '/../../resources/xsd/Anmodning.xsd'),
    ];

    yield [
      <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Anmodning xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <AnsoegerOplysninger>
        <Ansoeger>
            <Fornavn>Anders</Fornavn>
            <Efternavn>And</Efternavn>
            <Personnummer>urn:oio:cpr:0000000000</Personnummer>
        </Ansoeger>
    </AnsoegerOplysninger>
    <Sagstype>
        <AlmindeligtHelbredstillaeg>Medicin</AlmindeligtHelbredstillaeg>
    </Sagstype>
    <Underskriftsoplysninger>
        <Underskrift>Underskrift0</Underskrift>
        <Underskriftsdato>2001-01-01</Underskriftsdato>
    </Underskriftsoplysninger>
</Anmodning>
XML,
      'file://' . realpath(__DIR__ . '/../../resources/xsd/Anmodning.xsd'),
      new InvalidXmlTemplateException(),
    ];
  }

}
