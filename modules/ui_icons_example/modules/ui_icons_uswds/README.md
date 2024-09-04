## INTRODUCTION

The USWDS icons module provider for UI Icons.

## INSTALLATION

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/docs/extending-drupal/installing-modules for further
information.

Install [USWDS](https://www.npmjs.com/package/@uswds/uswds) in your libraries folder under Drupal web directory.

```shell
mkdir -p libraries/uswds
cd libraries/uswds
npm init -y
npm i @uswds/uswds
```

So your folder structure is:

libraries
  └── uswds
      └── node_modules
          └── @uswds
              └── uswds
                  └── dist
                      └── img
