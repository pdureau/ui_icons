# Introduction

The UI Icons module is a generic icon manager for Drupal.

It aims to seamlessly integrate most third-party icon packs into Drupal.

It's architecture make it possible to work with a lot of available Icon pack
available on the web or custom Icons.  
They even can be mix together seamlessly.

## Installation

Install as you would normally install a contributed Drupal module.  
See: [Installing Modules](https://www.drupal.org/docs/extending-drupal/installing-modules)
for further information.

## Usage

To add an **Icon Pack**, you need to declare in your **module** or **theme** a
specific file with suffix `*.icons.yml`:

- `MY_MODULE_NAME.icons.yml`
- `MY_THEME_NAME.icons.yml`

The definition file can declare multiple definitions.

### Starter for icon pack provider

You can find examples in external project [UI Icons Example](https://gitlab.com/ui-icons/ui-icons-example).

These examples are starting point to add icons to your Drupal installation.  
We try to provide ready to use examples that can be easily adapted to your use
case.

These examples include widely used third party Icon pack like:

- [Bootstrap Icons](https://icons.getbootstrap.com)
- [FontAwesome](https://fontawesome.com/icons)
- [Feather Icons](https://feathericons.com)
- [Heroicons](https://heroicons.com)
- [Material Symbols](https://fonts.google.com/icons)
- [Octicons](https://primer.style/foundations/icons)
- [Phosphor Icons](https://phosphoricons.com)
- [Remix icon](https://remixicon.com)
- [Delta icons](https://delta-icons.github.io)
- [Evil icons](https://evil-icons.io)
- [Maki icons](https://labs.mapbox.com/maki-icons)
- [Lucide icons](https://lucide.dev)

Icon packs builder:

- [IcoMoon](https://icomoon.io) - Import your icons or pick from existing sets
- [Iconify](https://iconify.design) - All popular icon sets, one framework.

Specific Design system Icons:

- [USWDS](https://designsystem.digital.gov) - US federal government Design System
- [DSFR](https://www.systeme-de-design.gouv.fr) - French government Design System

Even make [Drupal core Icon](https://gitlab.com/ui-icons/ui-icons-example/-/tree/main/ui_icons_drupal) available!

You can add a PR for any third party Icon pack to the project!

### Create an icon pack

#### Examples

##### Minimal example

```yaml
my_icons:
  extractor: path
  config:
    sources:
      - icons/*.png
  template: >-
    <img src="{{ source }}" width="32" height="32" role="presentation">

my_icons_svg:
  extractor: svg
  config:
    sources:
      - icons/*.svg
  template: >-
    <svg
      xmlns="https://www.w3.org/2000/svg"
      width="32"
      height="32"
      aria-hidden="true"
    >
      {{ content }}
    </svg>

my_icons_svg_sprite:
  extractor: svg_sprite
  config:
    sources:
      - icons/icons.svg
  template: >-
    <svg
      width="32"
      height="32"
      aria-hidden="true"
    >
      <use xlink:href="{{ source }}#{{ icon_id }}"/>
    </svg>
```

##### Standard example

```yaml
my_icons:
  label: My Icons
  extractor: path
  config:
    sources:
      - icons/*.svg
  settings:
    size:
      title: "Size"
      type: "integer"
      default: 32 # Recommended, must match the template default
  template: >-
    <img
      class="icon icon-{{ icon_id|clean_class }}"
      src="{{ source }}"
      width="{{ size|default(32) }}"
      height="{{ size|default(32) }}"
      role="presentation"
      aria-hidden="true"
    >

my_icons_svg:
  label: My Icons
  extractor: svg
  config:
    sources:
      - icons/*.svg
  settings:
    size:
      title: "Size"
      type: "integer"
      default: 32
    color:
      title: "Color"
      type: "string"
      format: "color"
  template: >-
    <svg
      xmlns="https://www.w3.org/2000/svg"
      fill="{{ color|default(currentColor) }}"
      class="icon icon-{{ icon_id|clean_class }}"
      width="{{ size|default(32) }}"
      height="{{ size|default(32) }}"
      aria-hidden="true"
    >
      {{ content }}
    </svg>

my_icons_svg_sprite:
  label: My Icons
  extractor: svg_sprite
  config:
    sources:
      - icons/icons.svg
  settings:
    size:
      title: "Size"
      type: "integer"
      default: 32
    color:
      title: "Color"
      type: "string"
      format: "color"
  template: >-
    <svg
      class="icon icon-{{ icon_id|clean_class }}"
      width="{{ size|default(32) }}"
      height="{{ size|default(32) }}"
      fill="{{ color|default(currentColor) }}"
      aria-hidden="true"
    >
      <use xlink:href="{{ source }}#{{ icon_id }}"/>
    </svg>
```

#### Definition values

All possible values for a definition:

```yaml
_ICON_PACK_MACHINE_NAME_:
  # REQUIRED values:
  extractor: (string) _PLUGIN_ID_ # Included: path, svg, svg_sprite, and font
  template: (string) # Twig template to render the icon
                     # Icon values are passed to the template:
                     # - icon_id: Icon ID based on filename or {icon_id} value
                     # - source: Icon path or url resolved
                     # - ... all specific values from extractor
                     # - ... all settings from settings if set

  # REQUIRED values by extractors: path, svg, svg_sprite
  config:
    sources: (array) # REQUIRED for extractors: path, svg, svg_sprite
      - path/to/relative/*.svg
      - /path/relative/drupal/web/*.svg
      - http://www.my_domain.com/my_icon.png
      - ...
    # ... Other keys for a custom extractor plugin

  # Recommended values:
  label: (string)
  license: (string)

  # Optional values:
  description: (string)
  license_url: (string)
  links: (array)
    - (string)
    - ...
  version: (string)
  enabled: (boolean)
  preview: (string) # Twig template for preview on admin backend (selector, lists...).
  library: (string) # Drupal library machine name to include

  # Optional values to be passed to the template:
  settings: (array)
    _FORM_KEY_: (string)
      _KEY_ (string): _VALUE_ (mixed)
    ...
```

You can find examples in external project [UI Icons Example](https://gitlab.com/ui-icons/ui-icons-example).

#### Settings

The `settings` key allow to define any setting specific to the Icon Pack that
will be generated as a Drupal Form when the Icon is used and pass to the
Twig template.

The format follow the [JSON Schema reference](https://json-schema.org/understanding-json-schema/reference/type).

For example a common usage is to include a `size` or `width` and `height` option
to control the icon. For example:

```yaml
settings:
  size:
    title: "Size"
    description: "Set a size for this icon."
    type: "integer"
    default: 32
```

This will allow the user to fill a `size` form alongside the Icon form. And the
value will be passed to the `template`, so you can use them:

```twig
<img class="icon icon-{{ icon_id|clean_class }}" src="{{ source }}" width="{{ size|default(24) }}" height="{{ size|default(24) }}">
```

It is highly recommended to provide default in the Twig template as default
values in the `settings` form are just indicative.

#### Template

The key `template` provide a Twig template to render the Icon.
Available variables in the template:

- `source`: The Icon path or url resolved
- `icon_id`: The Icon name extracted
- ... Any other variable from `settings` definition or extractor, like `content`
for svg.

A lot of examples are located in [UI Icons Example](https://gitlab.com/ui-icons/ui-icons-example).

For example with `img` extractor and a `size` setting:

```yaml
  settings:
    size:
      title: "Size"
      type: "integer"
      default: 32
  template: >-
    <img
      class="icon icon-{{ icon_id|clean_class }}"
      src="{{ source }}"
      width="{{ size|default(32) }}"
      height="{{ size|default(32) }}"
      role="presentation"
    >
```

To keep an accessible content, check the
[ARIA: presentation role](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Roles/presentation_role)
documentation.

For example with `svg` extractor and a `size` setting:

```yaml
  settings:
    size:
      title: "Size"
      type: "integer"
      default: 32
  template: >-
    <svg
      xmlns="http://www.w3.org/2000/svg"
      class="icon icon-{{ icon_id|clean_class }}"
      width="{{ size|default(32) }}"
      height="{{ size|default(32) }}"
      fill="currentColor"
      aria-hidden="true"
    >
      {{ content }}
    </svg>
```

#### Preview

The key `preview` provide a Twig template to render the Icon in the back office
context. This could be the Icon selector preview, the library...

This template access the same variables as the `template` but it must allow to
display the icon in a square ratio of **48x48px** to allow consistent preview.

This is optional as core extractors already include a preview template, this
value is intended for custom extractor that display the icon in a different way.

For example font based icons need this value as example for
[Material icons](https://gitlab.com/ui-icons/ui-icons-example/-/blob/main/ui_icons_material/ui_icons_material.icons.yml?ref_type=heads).

### Extractors (discover icons)

An extractor is a Plugin based class to allow discovering icons to make them
available in your Drupal.

This module provide multiple extractors, you an provide other extractor with
other modules or custom code.

An extractor must implement the [IconExtractorInterface](https://git.drupalcode.org/project/ui_icons/-/blob/1.0.x/src/Plugin/IconExtractorInterface.php?ref_type=heads).

#### Core extractors

Available extractors with this module:

- `path`: icons as images files (limited to png, gif, svg)
- `svg`: icons as svg files
- `svg_sprite`: icons in a svg sprite file

You must provide a `config > sources` array to indicate the physical path(s) or
url(s) to the icons.

Url must simply be the direct access to the Icon, only **http(s)** is allowed.

If the path do not start with a slash `/`, it will resolve to the module or
theme, else it will resolve to Drupal web root.

For path(s), it can include the keyword `{icon_id}` to identify icons name and
optionally `{group}` to group icons. For example:

```yaml
- /libraries/icon_pack/icons/{icon_id}-24.svg
```

Icons are located in the Drupal web root `libraries` folder, only
with suffix `-24`.

```yaml
- assets/icons/{icon_id}.svg
```

Icons located in the Module or Theme where the `*.icons.yml` file is:
`my_module/assets/icons/`.

##### Web font Extractor

Provided by sub module `UI Icons Font`.

Allow to use a Web Font, the discovery of icons can be done with different
format: json, yml, codepoints or reading TTF or Woff file.

Definition `config > sources` key must reference any supported format to load
the list of icons.

An optional key `config > offset` allow to load only from a starting point, can
be useful with TTF discovery that include numbers and letters.

Examples:

- [Bootstrap](https://gitlab.com/ui-icons/ui-icons-example/-/blob/main/ui_icons_bootstrap/ui_icons_bootstrap.icons.yml)
- [Material](https://gitlab.com/ui-icons/ui-icons-example/-/blob/main/ui_icons_material/ui_icons_material.icons.yml)
- [Feather](https://gitlab.com/ui-icons/ui-icons-example/-/blob/main/ui_icons_feather/ui_icons_feather.icons.yml)
- [Font awesome](https://gitlab.com/ui-icons/ui-icons-example/-/tree/main/ui_icons_fontawesome)
  _Note_: Font awesome JavaScript loader is not compatible with `ui_icons_ckeditor`.

##### Iconify Extractor

- [Iconify](https://iconify.design) with `UI Icons Iconify API`.

Allow to add collections from Iconify through the API.

A key `config > collections` allow to get a list of icons from Iconify API.
This must be the machine name of the set found in
[https://icon-sets.iconify.design](https://icon-sets.iconify.design/material-symbols/).

Machine name is the url address, hor example `Lets Icons` package has machine
name: `lets-icons`.

An example is located in project [UI Icons Iconify](https://gitlab.com/ui-icons/ui-icons-example/-/tree/main/ui_icons_iconify).

### Drupal Implementations

Different submodules provide implementations for Field, Field Link, Menu,
CKEditor, Twig, Render API, Form API and UI Patterns.

#### Field UI

Enable `UI Icons Fields` module to add a new field of type **Icon** available
with specific options and formatter.

For integration with field of type **Link**, be sure to select the `Link Icon`
widget and formatter under **Manage form display** and **Manage display**.

For support with [Link Attributes widget](https://www.drupal.org/project/link_attributes),
enable `UI Icons Link Attributes` module.

#### Menu

Enable `UI Icons for Menu` module to be able to add an Icon to a menu item.

After enabling the module, edit a menu item to have access to the Icon
selection.

#### CKEditor5

Enable the `UI Icons CKEditor 5` module, go to:

- Administration >> Configuration >> Content authoring

Configure your text format to add the `Icon` button and enable the `Embed icon`
filter.

#### UI Patterns

Enable the submodule `UI Icons for UI Patterns` to allow usage with
[UI Patterns 1 or 2](https://www.drupal.org/project/ui_patterns).

#### Twig

`UI Icons` module provide a specific Twig function is available anywhere:

```twig
{{ icon('my_pack_id', 'my_icon_id') }}
```

Assuming a settings `size` is declared:

```twig
{{ icon('my_pack_id', 'my_icon_id', {size: 64}) }}
```

#### Render API

`UI Icons` module provide a `RenderElement` with type: `icon` to allow usage
of an icon with the Drupal Render API.

```php
<?php
$build['icon'] = [
  '#type' => 'icon',
  '#pack_id' => 'my_pack_id',
  '#icon_id' => 'my_icon_id',
  '#settings' => [
    'size' => 64,
  ],
];
```

Specific properties:

- `#pack_id`: (string) Icon Pack provider plugin id.
- `#icon_id`: (string) Id of the icon.
- `#settings`: (array) Settings sent to the inline Twig template.

#### Form API

`UI Icons` module provide a `FormElement` with type `icon_autocomplete` to be
used with the Drupal Form API.

```php
<?php
$form['icon'] = [
  '#type' => 'icon_autocomplete',
  '#title' => $this->t('Select icon'),
  '#default_value' => 'my_pack_id:my_icon_id_default',
  '#allowed_icon_pack' => [
    'my_pack_id',
    'other_icon_pack',
  ],
  '#show_settings' => TRUE,
];
```

Specific properties:

- `#default_value` (_string_): Icon value as pack_id:icon_id.
- `#show_settings` (_bool_): Enable extractor settings, default FALSE.
- `#default_settings` (_array_): Settings for the extractor settings.
- `#settings_title` (_string_): Extractor settings details title.
- `#allowed_icon_pack` (_array_): Icon pack to limit the selection.
- `#return_id` (_bool_): Form return icon id instead of icon object as default.

Some base properties from `FormElementBase`:

- `#description` (_string_): Help or description text for the input element.
- `#placeholder` (_string_): Placeholder text for the input.
- `#required` (_bool_): Whether or not input is required on the element.
- `#size` (_int_): Textfield size, default 55.
- `#attributes` (_array_): Attributes to the global element.

#### Icon selector

Default Icon selector is based on an autocomplete Drupal field.

Submodule `UI Icons Picker` provide a more fancy selector of type `icon_picker`.

## Maintainers

Current maintainers:

- Jean Valverde - [mogtofu33](https://www.drupal.org/u/mogtofu33)
- Pierre Dureau - [pdureau](https://www.drupal.org/user/1903334)
- Florent Torregrosa - [Grimreaper](https://www.drupal.org/user/2388214)

Supporting organizations:

- [Beyris](https://www.drupal.org/beyris) - We are leading impactful open-source
projects and we are providing coding, training, audit and consulting.
