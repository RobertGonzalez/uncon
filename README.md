# UnCon code examples
Definitive source for all your UnCon code examples and exercises.

Copyright (C) 2017 SugarCRM Inc.

## Repository Structure
The code from previous UnCon events are separated into different branches for each year.

| Year   | Branch|
---------|--------
| 2015   | [2015](https://github.com/sugarcrm/uncon/tree/2015) |
| 2016   | [2016](https://github.com/sugarcrm/uncon/tree/2016) |
| 2017   | [2017](https://github.com/sugarcrm/uncon/tree/2017) |
=======
UnCon 2017 Code Samples
----------------------

Prerequisites:
- Git and PHP installed
- access to a Sugar instance (does not need to be locally installed)

## Installing Sample Packages

To install these code sample packages into any Sugar instance, use the following steps:

1. Login to Sugar as an Administrator.
2. Go to Administration > Module Loader.
3. "Upload" the zip from PACKAGE_NAME/releases directory.
4. Click "Install" on your package in the list.

## Editing and Rebuilding a new version of a Package

- Check out this Git repository and make changes to the package.

        $ git clone git@github.com:sugarcrm/uncon.git
        $ git checkout origin/2017
        $ cd PACKAGE_NAME

- Follow the directions in the README for each package for build instructions.
