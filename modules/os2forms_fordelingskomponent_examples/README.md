# OS2Forms Fordelingskomponent examples

Example forms for OS2Forms Fordelingskomponent

## Installation

``` shell
drush pm:enable os2forms_fordelingskomponent_examples
```

Go to `/admin/structure/webform?category=Fordelingskomponent` to see the example forms.

## Updating the examples

All example webforms have IDs that match the regular expressions `/^os2forms_fdk_/` or `/^o2f_fdk_/`, i.e. if you want
to create a new example webform it must be have an ID like `os2forms_fdk_my_example`, say.

Run

``` shell
drush os2forms_fordelingskomponent_examples:export-examples
```

to export all example webforms.

Test the newly exported config by reinstalling the `os2forms_fordelingskomponent_examples` module

``` shell
drush pm:uninstall os2forms_fordelingskomponent_examples
drush pm:install os2forms_fordelingskomponent_examples
```

Alternatively, import a single webform, e.g.

``` shell
drush config:set --input-format=yaml webform.webform.os2forms_fdk_kp_anmoding '?' - < config/install/webform.webform.os2forms_fdk_kp_anmoding.yml
# drush config:get webform.webform.os2forms_fdk_kp_anmoding
```
