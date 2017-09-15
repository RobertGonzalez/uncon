# UnCon 2017

## Building code customizations for Advanced Workflow

Copyright (C) 2017 SugarCRM Inc.

### Requirements
- SugarCRM 7.9.0
  - SugarCRM 7.9.1 recommended
- Hint 1.0.0
- PHP 5.4+
  - PHP 7.1 required for use with SugarCRM 7.9.2

### Installation
- Install SugarCRM
- Install Hint
- Use module loader to install the latest release zip from this repository
- Import the BPM file and enable the process definition

### Notes
This repository contains a number of different icons that can be used in Process Definition. To change to a different icon, simply update the custom.less file and use a different image name from one of the choices.

### Use of included code
After installation of this code, you must run a Repair and Rebild and you must hard refresh your browser so that all changes to the CSS and Javascript are picked up in your instance.
