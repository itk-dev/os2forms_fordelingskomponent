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

2. Go to `/admin/os2forms_fordelingskomponent/settings` and configure the Fordelingskomponent module.

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
