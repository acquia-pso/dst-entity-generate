.. _drush_commands_list:

Drush Commands
#######################
A list of Drush commands used to generate the drupal entities in Drupal using Drupal Entity Generator (DEG) Spreadsheet ,

Generate All Entities
**********************

**Description**
----------------
   Runs all DEG drush commands together.

**Command**
------------
   ``deg:generate``

**Aliases**
------------
   ``deg:generate:all``, ``deg:ga``

**Options**
--------------
    ``--update`` Update existing entity types with fields and creates new if not present.

.. raw:: html

   <hr style="border: 1px solid grey;">

Generate Content Types
**********************

**Description**
------------------
   This command is used to generate content types with fields.

**Command**
--------------
   ``deg:generate:content_types``

**Aliases**
--------------
   ``deg:content_types``, ``deg:ct``

**Options**
--------------
    ``--update`` Update existing entity types with fields and creates new if not present.

.. raw:: html

   <hr style="border: 1px solid grey;">

Generate vocabularies
**********************

**Description**
------------------
   This command is used to generate vocabularies with fields in Drupal.

**Command**
--------------
   ``deg:generate:vocabs``

**Alias**
--------------
    ``deg:v``

**Options**
--------------
    ``--update`` Update existing Vocabulary types with fields and creates new if not present.

.. raw:: html

   <hr style="border: 1px solid grey;">


Generate Blocks Types
**********************

**Description**
------------------
   This command is used to generate custom block types with fields in Drupal.

**Command**
--------------
   ``deg:generate:custom_block_type``

**Alias**
--------------
    ``deg:cbt``

**Options**
--------------
    ``--update`` Update existing entity types with fields and creates new if not present.

.. raw:: html

   <hr style="border: 1px solid grey;">


Generate Image Styles
**********************

**Description**
------------------
   This command is used to generate image styles and image effects in Drupal.

**Command**
--------------
   ``deg:generate:imagestyle``

**Alias**
--------------
    ``deg:is``

**Options**
--------------
    ``--update`` Update existing image styles and creates new if not present.

.. raw:: html

   <hr style="border: 1px solid grey;">

Generate Media Types
**********************

**Description**
------------------
   This command is used to generate media types with fields in Drupal.

**Command**
--------------
   ``deg:generate:media``

**Alias**
--------------
    ``deg:media``

**Options**
--------------
    ``--update`` Update existing entity types with fields and creates new if not present.

.. raw:: html

   <hr style="border: 1px solid grey;">


Generate menus
**********************

**Description**
------------------
   This command is used to generate menu types in Drupal.

**Command**
--------------
   ``deg:generate:menus``

**Alias**
--------------
    ``deg:m``

**Options**
--------------
    ``--update`` Update existing Menus and creates new if not present.

.. raw:: html

   <hr style="border: 1px solid grey;">


Generate Paragraphs Types
**************************

**Description**
------------------
   This command is used to generate paragraph types with fields in Drupal.

**Command**
--------------
   ``deg:generate:paragraphs``

**Aliases**
--------------
    ``deg:para``, ``deg:p``

**Options**
--------------
    ``--update`` Update existing entity types with fields and creates new if not present.

.. raw:: html

   <hr style="border: 1px solid grey;">


Generate User Roles
**********************

**Description**
------------------
   This command is used to generate user roles in Drupal.

**Command**
--------------
   ``deg:generate:user-roles``

**Alias**
--------------
    ``deg:ur``

**Options**
--------------
    ``--update`` Update existing User roles and creates new if not present.

.. raw:: html

   <hr style="border: 1px solid grey;">


Generate Workflows
**********************

**Description**
------------------
   This command is used to generate workflows, states and workflow transitions in Drupal.

**Command**
--------------
   ``deg:generate:workflow``

**Alias**
--------------
    ``deg:w``

**Options**
--------------
    ``--update`` Update existing Workflow types and creates new if not present.

.. raw:: html

   <hr style="border: 1px solid grey;">

.. Note::

   The following messages can come after execution of the command.

   * [OK]: means entity has been created successfully.
       Example - [OK] Node Type article is successfully created..
   * [notice]: means field is created successfully.
        Example -  [notice] Field storage created for field_teaser_title.
   * [WARNING]: means there is some minor error comes on execution of command.
        Example -  [WARNING] Alias for article is already present, skipping.

   * **[ERROR]: means there is some major error which is halting the execution of command.**

