## INTRODUCTION

The Octicons module provider for UI Icons.

## INSTALLATION

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/docs/extending-drupal/installing-modules for further
information.

Install [Octicons icons](https://github.com/primer/octicons) in your libraries folder under Drupal web directory.

```shell
mkdir -p libraries/octicons
cd libraries/octicons
npm init -y
npm i @primer/octicons
```

So your folder structure is:

libraries
  └── octicons
      └── node_modules
          └── @primer
            └── octicons
              └── build
                └── svg
