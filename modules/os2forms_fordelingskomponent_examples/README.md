# OS2Forms Fordelingskomponent examples

Example forms for OS2Forms Fordelingskomponent

## Installation

``` shell
drush pm:enable os2forms_fordelingskomponent_examples
```

Go to `/admin/structure/webform?category=Fordelingskomponent` to see the example forms.

## Updating the examples

Run

``` shell
drush os2forms_fordelingskomponent_examples:export-examples
```

to update all example forms.

Test the newly exported config by reinstalling the `os2forms_fordelingskomponent_examples` module

``` shell
drush pm:uninstall os2forms_fordelingskomponent_examples
drush pm:install os2forms_fordelingskomponent_examples
```
