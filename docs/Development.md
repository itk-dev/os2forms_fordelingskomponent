# Development

We use [DDEV Drupal Contrib](https://github.com/ddev/ddev-drupal-contrib) for development[^1].

[^1]: The DDEV environment has been created by running

      ```shell
      ddev config --project-type=drupal10 --docroot=web
      ddev dotenv set .ddev/.env.web --drupal-core '^10.5.10'
      ```

Start the show by running

```shell
task ddev:start
```

Run `task` to see other useful development tasks.

## Translations

We use "interface translation project" (cf.
[os2forms_fordelingskomponent.info.yml](../os2forms_fordelingskomponent.info.yml)) and
<https://github.com/itk-dev/drupal_translation_extractor>.

Extract translations:

``` shell
composer require --dev itk-dev/drupal_translation_extractor:^1.0
drush pm:install drupal_translation_extractor

drush drupal_translation_extractor:translation:extract da --dump-messages --force module:os2forms_fordelingskomponent --output=%source/translations/%module.%locale.po

drush pm:uninstall drupal_translation_extractor --yes
composer remove --dev itk-dev/drupal_translation_extractor
```

> [!TIP]
> Open `translations/os2forms_fordelingskomponent.da.po` with [Poedit](https://poedit.com/) to translate messages and
> clean up the translations.

Import translations:

``` shell
drush locale:import --type=not-customized --override=not-customized da module://os2forms_fordelingskomponent/translations/os2forms_fordelingskomponent.da.po
```

## Example webforms

See [os2forms_fordelingskomponent_examples/README.md](../modules/os2forms_fordelingskomponent_examples/README.md).
