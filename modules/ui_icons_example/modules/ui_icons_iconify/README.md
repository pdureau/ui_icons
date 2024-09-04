## INTRODUCTION

The [Iconify](https://icon-sets.iconify.design) icons module provider for UI
Icons.

## INSTALLATION

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/docs/extending-drupal/installing-modules for further
information.

## USAGE

This module provide example definition to allow integration of Icons from
[Iconify](https://iconify.design).

Inspect file `ui_icons_iconify.ui_icons.yml` to define your icons in your own
***.ui_icons.yml** file. See module `UI Icons` README for more information.

Key `config: collections` allow integration of [Icon sets](https://icon-sets.iconify.design/).
A collection must be added as ID, ID can be found in the url from Iconify Sets
page.

Multiple collections can be set, but if the Icon names collide only icons from
the last collection in order will be available.

For example Sets `Flags Icons`[flag] and `Circle Flags`[circle-flags] have the
same names and cannot be used in the same Icon pack definition.
