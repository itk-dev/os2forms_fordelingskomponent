# Fordelingskomponent for OS2Forms

## Installation

1. Create two keys (on `/admin/config/system/keys`)

   | Key                     | Type           | Provider |
   |-------------------------|----------------|----------|
   | SF2900 Certificate      | Certificate    | File     |
   | SF2900 SFTP private key | Authentication | File     |

   Note: The "SFTP private key" key must be passwordless[^1].

   You can use `ssh-keygen` to remove the password from a certificate:

   ``` shell
   cp cert/sf2900-sftp cert/sf2900-sftp-nopass
   ssh-keygen -p -N "" -f cert/sf2900-sftp-nopass
   ```

2. Create a queue (on `/admin/config/system/queues`) for Fordelingskomponent handler jobs.
3. Go to `/admin/os2forms_fordelingskomponent/settings` and configure the Fordelingskomponent module.

[^1] It takes a very long time to read a key with a password (reference?)

## Console commands

``` shell
drush os2forms-fordelingskomponent:sftp:ls
```

---

``` shell name=key-create-sf2900_certificate
drush config:set --input-format=yaml key.key.sf2900_certificate '?' - <<'EOF'
langcode: da
status: true
dependencies:
  module:
    - os2web_key
id: sf2900_certificate
label: 'SF2900 Certificate'
description: ''
key_type: os2web_key_certificate
key_type_settings:
  passphrase: …
  input_format: pfx
  output_format: pem
key_provider: file
key_provider_settings:
  file_location: /app/cert/OS2Forms_FordelingUdvikling.p12
  strip_line_breaks: false
key_input: none
key_input_settings: {  }
EOF
```

``` shell name=key-create-sf2900_sftp_private_key
drush config:set --input-format=yaml key.key.sf2900_sftp_private_key '?' - <<'EOF'
langcode: da
status: true
dependencies: {  }
id: sf2900_sftp_private_key
label: 'SF2900 SFTP private key'
description: ''
key_type: authentication
key_type_settings: {  }
key_provider: file
key_provider_settings:
  file_location: /app/cert/OS2Forms_FordelingUdvikling-sftp-nopass
  strip_line_breaks: false
key_input: none
key_input_settings: {  }
```

## Development

``` shell
task composer:install
```

### Composer install hacks

``` shell name=composer-install-hack
# Create a temporary composer file to install https://github.com/mglaman/composer-drupal-lenient before the real install needs it.
docker compose run --rm --env COMPOSER=composer.lenient.json phpfpm composer init --no-interaction
docker compose run --rm --env COMPOSER=composer.lenient.json phpfpm composer config --no-plugins allow-plugins.mglaman/composer-drupal-lenient true
docker compose run --rm --env COMPOSER=composer.lenient.json phpfpm composer require mglaman/composer-drupal-lenient
docker compose run --rm --env COMPOSER=composer.lenient.json phpfpm rm composer.lenient.*

# Now we can install what we actually need.
docker compose run --rm phpfpm composer install
```

### Kvittering

[Kom godt i gang - Fordelingskomponenten, side
27](https://digitaliseringskataloget.dk/files/integration-files/150620210923/Kom%20godt%20i%20gang%20-%20Fordelingskomponenten.pdf#page=27)

``` shell name=test-anvender-FordelingskvitteringModtag substitutions='«base url»: http://selvbetjening.local.itkdev.dk'
# curl --verbose --insecure --location «base url»/os2forms-fordelingskomponent/sf2900/2.4/FordelingskvitteringModtag --head

curl --verbose --insecure --location «base url»/os2forms-fordelingskomponent/sf2900/2.4/FordelingskvitteringModtag --header 'content-type: application/soap+xml' --data @- <<'XML'
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <FordelingskvitteringModtagAnvenderRequest xmlns="http://serviceplatformen.dk/xml/wsdl/soap11/DistributionService/2/types" xmlns:ns2="http://serviceplatformen.dk/xml/schemas/CallContext/1/" xmlns:ns3="http://serviceplatformen.dk/xml/schemas/AuthorityContext/1/">
      <Forretningskvittering>
        <ForretningsValideringsKode>ACCEPTERET</ForretningsValideringsKode>
        <Kvitteringstype>Forretning</Kvitteringstype>
      </Forretningskvittering>
      <DistributionContext>
        <AnvenderTransaktionsID>752bc93a-37cb-46db-9fb1-d5f4f7e3964e</AnvenderTransaktionsID>
        <DistributionTransktionsID>d8101c99-0262-4a97-ac75-5685a6c6494a</DistributionTransktionsID>
        <!-- ... -->
      </DistributionContext>
    </FordelingskvitteringModtagAnvenderRequest>
  </soap:Body>
</soap:Envelope>
XML
```

## References

* [Fælleskommunal Filudveksling](https://digitaliseringskataloget.dk/l%C3%B8sninger/filudveksling)
  * [Vejledning til Serviceplatformens SFTP-service](https://docs.kombit.dk/latest/d312b273)

## KP-formularer

* <https://rimi-itk.github.io/digitaliseringskataloget.dk/digitaliseringskataloget.dk/sf1415/0.6/Integrationsbeskrivelse_SF1415.pdf#page=14>

## Debugging

``` shell
# Latest receipts
drush sql:query "
SELECT k.anvender_transaktions_id, FROM_UNIXTIME(k.created_at) AS created_at
FROM os2forms_fordelingskomponent_anvender_kvittering AS k
ORDER BY k.created_at DESC
LIMIT 10
"

# Latest receipts with submission IDs
drush sql:query "
SELECT k.anvender_transaktions_id, FROM_UNIXTIME(k.created_at) AS created_at, f.webform_id, f.webform_submission_id,
CONCAT('/admin/structure/webform/manage/', f.webform_id, '/submission/', f.webform_submission_id,'/os2forms-fordelingskomponent-debug-forsendelse') AS path
FROM os2forms_fordelingskomponent_anvender_kvittering AS k JOIN os2forms_fordelingskomponent_anvender_forsendelse AS f ON f.anvender_transaktions_id = k.anvender_transaktions_id
ORDER BY k.created_at DESC
LIMIT 10
"

drush sql:query "
SELECT
    (SELECT COUNT(*) FROM os2forms_fordelingskomponent_anvender_forsendelse) AS os2forms_fordelingskomponent_anvender_forsendelse,
    (SELECT COUNT(*) FROM os2forms_fordelingskomponent_anvender_kvittering) AS os2forms_fordelingskomponent_anvender_kvittering
"

# Forsendelser
drush sql:query "
SELECT webform_id, webform_submission_id, anvender_transaktions_id, distribution_transaktions_id
FROM os2forms_fordelingskomponent_anvender_forsendelse
"

# Kvitteringer
drush sql:query "
SELECT anvender_transaktions_id, distribution_transaktions_id
FROM os2forms_fordelingskomponent_anvender_kvittering
"

# Forsendelser med kvitteringer
drush sql:query "
SELECT webform_id, webform_submission_id, anvender_transaktions_id, distribution_transaktions_id
FROM os2forms_fordelingskomponent_anvender_forsendelse
WHERE anvender_transaktions_id IN (SELECT anvender_transaktions_id FROM os2forms_fordelingskomponent_anvender_kvittering)
"
```
