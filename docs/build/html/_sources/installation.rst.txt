.. _installation:

Installation
===============================
Using composer is the preferred way of managing your modules and themes as composer handles dependencies automatically and there is less margin for error. You can find out more about composer and how to install it here: https://getcomposer.org/. It is not recommended to edit your composer.json file manually.


Download using composer
-----------------------

Open up your terminal and navigate to your project root directory.

Run the following command  to require the module:

**composer require acquia-pso/dst-entity-generate**

DST Entity Generate module will install along with several module dependencies from drupal.org.

You can now enable the modules via drush with the following commands:

**drush cr**
**drush pm:enable dst_entity_generate -y**

Create a Google spreadsheet for defining all drupal entities like  https://docs.google.com/spreadsheets/d/1xJFEeIqTAC-Au02PEwPVS1zLLnwhsYaqqYPsbF8fv30

Configure DEG on Drupal
------------------------
* Login as Administrator on site
* Go to menu Configuration -> Development -> Drupal Spec tool: Entity Generate -> Google Sheet API (/admin/config/dst_entity_generate/settings/google_sheet_api)
* And follow the steps for configuration fo Google spreadsheet in DEG tool.
* Finally, It will redirect to General Settings (/admin/config/dst_entity_generate/settings) where we can enable the entity types.

Note: In every sheets of spreadsheet there are two important columns,

X: For providing status of the row by default keep it to x to implement the row.
Settings/notes: To add settings related to that row.

