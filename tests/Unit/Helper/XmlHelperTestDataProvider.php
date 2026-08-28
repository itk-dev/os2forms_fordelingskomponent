<?php

namespace Drupal\os2forms_fordelingskomponent\Test\Unit\Helper;

use Drupal\os2forms_fordelingskomponent\Exception\InvalidXmlException;

/**
 * Xml helper test data provider.
 */
final class XmlHelperTestDataProvider {
  private const string RESOURCE_PATH = 'file://' . __DIR__ . '/../../../resources';

  /**
   * Data provider.
   */
  public static function provideRenderData(): iterable {
    yield [
      'This is not an XML document',
    [],
      new InvalidXmlException(),
    ];

    yield [
      <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Anmodning xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    {{ name }}
</Anmodning>
XML,
      [],
      new InvalidXmlException(),
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
{% if webform_submission.data.fornavn == "And" %}
        <Ansoeger>
            <Fornavn>{{ webform_submission.data.fornavn }}</Fornavn>
            <Efternavn>{{ webform_submission.data.efternavn }}</Efternavn>
            <Personnummer>urn:oio:cpr:0000000000</Personnummer>
        </Ansoeger>
{% else %}
        <AnsoegningUdenLogin>
            <Fornavn>{{ webform_submission.data.fornavn }}</Fornavn>
            <Efternavn>{{ webform_submission.data.efternavn }}</Efternavn>
            <Personnummer>urn:oio:cpr:0000000000</Personnummer>
        </AnsoegningUdenLogin>
{% endif %}
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
        <AnsoegningUdenLogin>
            <Fornavn>Anders</Fornavn>
            <Efternavn>And</Efternavn>
            <Personnummer>urn:oio:cpr:0000000000</Personnummer>
        </AnsoegningUdenLogin>
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
  public static function provideValidateTemplateData(): iterable {
    yield [
      <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Anmodning xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    {{ name }
</Anmodning>
XML,
      new InvalidXmlException(),
    ];

    yield [
      <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Anmodning xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    {% if name == "And" %}
       {{ name }}
</Anmodning>
XML,
      new InvalidXmlException(),
    ];

  }

  /**
   * Data provider.
   */
  public static function provideValidateXmlData(): iterable {
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
      self::RESOURCE_PATH . '/SP/SF2900_XSD/Anmodning.xsd',
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
      self::RESOURCE_PATH . '/SP/SF2900_XSD/Anmodning.xsd',
      new InvalidXmlException(),
    ];
  }

}
