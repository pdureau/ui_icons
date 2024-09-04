## INTRODUCTION

The Material Symbols module provider for UI Icons.

## INSTALLATION

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/docs/extending-drupal/installing-modules for further
information.

Install [Material symbols](https://github.com/marella/material-design-icons/tree/main/svg) in your libraries folder under Drupal web directory.

```shell
mkdir -p libraries/material
cd libraries/material
npm init -y
npm i @material-design-icons/svg@latest
```

So your folder structure is:

libraries
  └── material
      └── node_modules
          └── @material-design-icons
              └── svg
