# User's manual

## The handler

The "Fordelingskomponent (sf2900)" handler is used to send webform submissions to Fordelingskomponenten.

### Handler settings

Most handler settings should make sense to people familiar with [Integration SF2900 – Fordelingsmekanisme
version 2](https://rimi-itk.github.io/digitaliseringskataloget.dk/digitaliseringskataloget.dk/sf2900/2.4/SF2900%20-%20Fordelingskomponent%20V2.4.pdf).
The "XML template" settings is explained in the [XML templates section](#xml-templates) below.

## XML templates

We use [Twig](https://twig.symfony.com/) to generate the XML we need to send as part of "Formular“ distributions.

An example XML template for the [SP241: Ansøgning om helbredstillæg](../resources/SP/SF2900_XSD/SP241.xsd) can look
like:

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<SP241 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <Header>
        <Myndighed>urn:oio:cvr-nr:{{ handler.settings.sender.sender_id }}</Myndighed>
        <ModtagetDato>{{ submission.completed|date("Y-m-d") }}</ModtagetDato>
        <KLE>{{ handler.settings.distribution_context.kle_emne }}</KLE>
        {% for file in files.dokumenter_overslag %}
        <Dokumenter>
            <Dokumentnavn>{{ file.sftp_filename }}</Dokumentnavn>
            <Dokumenttype>Overslag</Dokumenttype>
        </Dokumenter>
        {% endfor %}
        {% for file in files.dokumenter_faktura %}
        <Dokumenter>
            <Dokumentnavn>{{ file.sftp_filename }}</Dokumentnavn>
            <Dokumenttype>Faktura</Dokumenttype>
        </Dokumenter>
        {% endfor %}
        {% for file in files.dokumenter_bilag %}
        <Dokumenter>
            <Dokumentnavn>{{ file.sftp_filename }}</Dokumentnavn>
            <Dokumenttype>Bilag</Dokumenttype>
        </Dokumenter>
        {% endfor %}
    </Header>
    <AnsoegerOplysninger>
        <Ansoeger>
            <Fornavn>{{ submission.data.ansoeger_fornavn }}</Fornavn>
            {% if submission.data.ansoeger_mellemnavn|default(false) %}
            <Mellemnavn>{{ submission.data.ansoeger_mellemnavn }}</Mellemnavn>
            {% endif %}
            <Efternavn>{{ submission.data.ansoeger_efternavn }}</Efternavn>
            <Personnummer>urn:oio:cpr:{{ submission.data.ansoeger_personnummer }}</Personnummer>
        </Ansoeger>
    </AnsoegerOplysninger>
    <Erklaering>Accepteret</Erklaering>
    <Underskriftsoplysninger>
        <Underskrift>{{ submission.data.ansoeger_fornavn }}{% if submission.data.ansoeger_mellemnavn|default(false) %} {{ submission.data.ansoeger_mellemnavn }}{% endif %} {{ submission.data.ansoeger_efternavn }}</Underskrift>
        <Underskriftsdato>{{ submission.data.underskriftsoplysninger_underskriftsdato|date("Y-m-d") }}</Underskriftsdato>
    </Underskriftsoplysninger>
    {% if submission.data.fuldmagt|default(false) %}
    <Fuldmagt>
        <FuldmagtDokumentnavn>{{ submission.data.fuldmagt_fuldmagtdokumentnavn }}</FuldmagtDokumentnavn>
        <FuldmagthaversPersonnummer>urn:oio:cpr:{{ submission.data.fuldmagt_fuldmagthaverspersonnummer }}</FuldmagthaversPersonnummer>
    </Fuldmagt>
    {% endif %}
</SP241>
```

When sending a webform submission to Fordelingskomponenten, the XML template will be rendered using a [*render
context*](#the-render-context) containing the submission data, the handler settings and information on any file elements
on the form.

Using the XML template above and the render context (some values omitted for brevity)

``` json
{
    "submission": {
        "serial": "1",
        "sid": "1",
        "uuid": "2b801c0b-9e36-4cc5-b5d1-c46239cb78ce",
        "token": "zJzDLIytIPCzUxCbPRhaaomH2UNGg1mZZnF71eV-jUc",
        "uri": "/da/form/os2forms-fdk-kp-sp241",
        "created": "1778586315",
        "completed": "1778586315",
        "changed": "1778586315",
        "in_draft": "0",
        "current_page": "",
        "remote_addr": "",
        "uid": "1",
        "langcode": "da",
        "webform_id": "os2forms_fdk_kp_sp241",
        "entity_type": "",
        "entity_id": "",
        "locked": "0",
        "sticky": "0",
        "notes": "",
        "webform_revision": "3",
        "data": {
            "ansoeger_efternavn": "Neuman",
            "ansoeger_fornavn": "Alfred",
            "ansoeger_mellemnavn": "E.",
            "ansoeger_personnummer": "1234567890",
            "ansoeger_telefonnummer": "12345678",
            "dokumenter_bilag": "3",
            "dokumenter_faktura": "4",
            "dokumenter_overslag": "1",
            "erklaering": "1",
            "fuldmagt": "1",
            "fuldmagt_fuldmagtdokumentnavn": "Harvey Kurtzman",
            "fuldmagt_fuldmagthaverspersonnummer": "8765432109",
            "sygeforsikring": "1",
            "sygeforsikring_gruppe": "GRUPPE_2",
            "underskriftsoplysninger_underskriftsdato": "2035-01-10"
        }
    },
    "files": {
        "dokumenter_overslag": [
            {
                "sftp_filename": "os2forms_fordelingskomponent_fordelingskomponent_sf2900_2b801c0b-9e36-4cc5-b5d1-c46239cb78ce_dokumenter_overslag.rtf",
                "file": {}
            }
        ],
        "dokumenter_faktura": [
            {
                "sftp_filename": "os2forms_fordelingskomponent_fordelingskomponent_sf2900_2b801c0b-9e36-4cc5-b5d1-c46239cb78ce_dokumenter_faktura.txt",
                "file": {}
            }
        ],
        "dokumenter_bilag": [
            {
                "sftp_filename": "os2forms_fordelingskomponent_fordelingskomponent_sf2900_2b801c0b-9e36-4cc5-b5d1-c46239cb78ce_dokumenter_bilag_0.xlsx",
                "file": {}
            }
        ]
    },
    "handler": {
        "settings": {
            "handler_id": "fordelingskomponent_sf2900",
            "sender": {
                "sender_id": 55133018,
                "routing_myndighed": 55133018,
                "registrering_it_system": "cfcf2769-9a1f-4d3b-b6d2-ddfb3a2f9dd6",
                "certificate": "sf2900_certificate",
                "sftp": {
                    "username": "OS2Forms_FordelingUdvikling",
                    "private_key": "sf2900_sftp_private_key"
                }
            },
            "distribution_context": {
                "routing_modtager_aktoer": null,
                "kle_emne": "32.03.12",
                "handling_facet": "G01",
                "titel": "SP241: Ansøgning om helbredstillæg",
                "beskrivelse": "…"
            },
            "distribution_object": {
                "distribution_type": "FORMULAR",
                "journalpost_message": "",
                "attachment_element": "kvittering",
                "formular_type": "HelbredstillægAnsøgningFormular_1",
                "files": {
                    "filspecifikation": "HelbredstillægAnsøgningBilag_1",
                    "recipient_it_system_look_up": 1,
                    "recipient_it_system": "",
                    "recipient_authority": "55133018"
                },
                "xml_template": "…",
                "xsd_url": "module://os2forms_fordelingskomponent/resources/SP/SF2900_XSD/SP241.xsd"
            }
        }
    }
}
```

</details>

will result in a final XML document looking like

``` xml
<SP241 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <Header>
        <Myndighed>urn:oio:cvr-nr:55133018</Myndighed>
        <ModtagetDato>2026-05-12</ModtagetDato>
        <KLE>32.03.12</KLE>
        <Dokumenter>
            <Dokumentnavn>os2forms_fordelingskomponent_fordelingskomponent_sf2900_2b801c0b-9e36-4cc5-b5d1-c46239cb78ce_dokumenter_overslag.rtf</Dokumentnavn>
            <Dokumenttype>Overslag</Dokumenttype>
        </Dokumenter>
        <Dokumenter>
            <Dokumentnavn>os2forms_fordelingskomponent_fordelingskomponent_sf2900_2b801c0b-9e36-4cc5-b5d1-c46239cb78ce_dokumenter_faktura.txt</Dokumentnavn>
            <Dokumenttype>Faktura</Dokumenttype>
        </Dokumenter>
        <Dokumenter>
            <Dokumentnavn>os2forms_fordelingskomponent_fordelingskomponent_sf2900_2b801c0b-9e36-4cc5-b5d1-c46239cb78ce_dokumenter_bilag_0.xlsx</Dokumentnavn>
            <Dokumenttype>Bilag</Dokumenttype>
        </Dokumenter>
    </Header>
    <AnsoegerOplysninger>
        <Ansoeger>
            <Fornavn>Alfred</Fornavn>
            <Mellemnavn>E.</Mellemnavn>
            <Efternavn>Neuman</Efternavn>
            <Personnummer>urn:oio:cpr:1234567890</Personnummer>
        </Ansoeger>
    </AnsoegerOplysninger>
    <Erklaering>Accepteret</Erklaering>
    <Underskriftsoplysninger>
        <Underskrift>Alfred E. Neuman</Underskrift>
        <Underskriftsdato>2035-01-10</Underskriftsdato>
    </Underskriftsoplysninger>
    <Fuldmagt>
        <FuldmagtDokumentnavn>Harvey Kurtzman</FuldmagtDokumentnavn>
        <FuldmagthaversPersonnummer>urn:oio:cpr:8765432109</FuldmagthaversPersonnummer>
    </Fuldmagt>
</SP241>
```

If "XSD URL" is filled, the XSD on the URL will be used to validate the generated XML document before sending it.

XSDs for "KP-formularer" are available in the [`resources/SP/SF2900_XSD/`](../resources/SP/SF2900_XSD/) folder and they
can be referenced in the "XSL URL" setting like
'module://os2forms_fordelingskomponent/resources/SP/SF2900_XSD/SP241.xsd`, say.

> [!TIP]
> On a webform's handler list (`/admin/structure/webform/manage/…/handlers`), you can find links to previews of
>
> * the routing info and
> * distribution objects (including the generated XML documents)
>
> in effect for the current handler settings. The distribution object preview is useful for testing and debugging the
> XML Twig template.

### The render context

The render context contains three main parts:

1. The submission data: This is the same data as shown on the details of a submission.
2. Information on files attached (uploaded) to the submission (grouped by element key)
3. The handler settings: This includes the global OS2Forms Fordelingskomponent module settings and the settings on the
   handler

## Twig extensions

This modules adds some useful Twig functions,

`os2forms_fordelingskomponent_intval`
`os2forms_fordelingskomponent_floatval`
`os2forms_fordelingskomponent_gettype`

that basically wrap the build PHP functions [`intval`](https://www.php.net/manual/en/function.intval.php),
[`floatval`](https://www.php.net/manual/en/function.floatval.php) and
[`gettype`](https://www.php.net/manual/en/function.gettype.php).

However, `os2forms_fordelingskomponent_floatval` accepts a second argument `langcode` that's used to help extract the
float value based on a language code:

``` twig
{{ os2forms_fordelingskomponent_floatval('1,23') == 123 }}
{{ os2forms_fordelingskomponent_floatval('1.23') == 1.23 }}

{{ os2forms_fordelingskomponent_floatval('1,23', langcode: 'da') == 1.23 }}
{{ os2forms_fordelingskomponent_floatval('1.23', langcode: 'da') == 123}}
```

A Twig filter, `os2forms_fordelingskomponent_xml_encode`, can be used to convert an array value to an XML fragment:

``` twig
{% set value = {
  Person: {
    firstname: 'Lucky',
    lastname: 'Luke',
  },
  Horse: {
    name: 'Jolly Jumper',
  },
} %}
{{ value|os2forms_fordelingskomponent_xml_encode }}
```

will render

``` xml
<Person><firstname>John</firstname><lastname>Doe</lastname></Person><Horse><name>Jolly Jumper</name></Horse>
```

Notice that `os2forms_fordelingskomponent_xml_encode` is "[safe for
HTML](https://twig.symfony.com/doc/3.x/advanced.html#automatic-escaping)", i.e. you don't have to use
[`raw`](https://twig.symfony.com/doc/3.x/filters/raw.html) to render the XML.
