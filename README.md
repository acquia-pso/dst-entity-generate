# Drupal Spec Tool: Entity Generate

[Drupal spec tool (acquia/drupal-spec-tool)](https://github.com/acquia/drupal-spec-tool) is used for specifying Drupal architecture details. Currently, this tool generates only test cases. This tool aims to extend the Drupal Spec tool to build Drupal entities using the architecture defined in the Spec tool sheet.


## Getting started
### Installation with composer
Using composer is the preferred way of managing your modules and themes as composer handles dependencies automatically and there is less margin for error. You can find out more about composer and how to install it here: https://getcomposer.org/. It is not recommended to edit your composer.json file manually.

Open up your terminal and navigate to your project root directory.

Run the following commands to require the module:

```
composer require acquia-pso/dst-entity-generate
```
DST Entity Generate module will install along with several module dependencies from drupal.org.

You can now enable the modules via drush with the following commands:

```
drush cr
drush pm:enable dst_entity_generate -y
```

### Usage
#### Drush integration (supports D8 & D9)
The DST Entity Generate module provides the following drush commands to generate the Drupal entities:

##### Generate all the entities
Read the content architecture updated in the Drupal spec tool sheet and generate all the Drupal entities.
```
dst:generate (dst:ga)
dst:generate:all (dst:ga)
```

##### Generate specific entity
Read the content architecture added in the Drupal spec tool sheet and generate a specific Drupal entity mentioned as an argument. The tool supports these entities:

* Content types with fields
```
dst:generate:bundles (dst:b)
```

* Taxonomy Vocabularies
```
dst:generate:vocabs (dst:v)
```

* User roles
```
dst:generate:user-roles (dst:ur)
```
* Menus
```
dst:generate:menus (dst:m)
```

* Image Styles
```
dst:generate:image-styles (dst:is)
```

* Image effects
```
dst:generate:image-effects (dst:ie)
```

* Workflow (Includes workflow, states and transitions)
```
dst:generate:workflow (dst:w)
```

* Block types
```
dst:generate:block-types (dst:bt)
```


## Future Roadmap
Future roadmap is to write drush command(s) for updating the existing entities created using this tool from the Spec tool sheet.
```
dst:update                 // Read & Update all the entities
dst:update:all (dst:up)    // Read & Update all the entities
dst:update:bundles         // Read & Update specific  entities, e.g. bundles for content type updates.
```

## To manage Document
There is docs directory having all documents related to Drupal Entity Generator (DEG).
We have used Sphinx for generating the documents. Install Sphinx(https://www.sphinx-doc.org/en/master) to update and manage the documents.
There are .rst  source files for documents in 'docs/source' directory.


## Contributors

[Mukesh Sah](#)

[Ashutosh Gupta](#)

[Praveen Singh](#)

[Sharique Farooqui](#)

[Omkar Pednekar](#)

[Gaurav Goyal](https://www.drupal.org/u/gauravgoyal-0)

[Amit Vyas](https://www.drupal.org/u/vyasamit2007)
