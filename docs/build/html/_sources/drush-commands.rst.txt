.. _drush_commands_list:

Drush Commands
#######################
A list of Drush commands used to generate the drupal entities in Drupal using Drupal Entity Generator (DEG) Spreadsheet ,


Generate content types
**********************

**Description**
------------------
   This command is used to generate all types of content with fields in Drupal.

**Command**
--------------
   ``deg:generate:content_types``

**Aliases**
--------------
   ``deg:content_types``, ``deg:ct``

**Options**
--------------
    ``--update`` Update existing entity types with fields and creates new if not present.

.. Note::

   The following messages can come after execution of the command.

   * [OK]: means bundle has been executed successfully.
       Example -  [OK] Node Type article is successfully created...
   * [notice]: means field is created successfully.
        Example -  [notice] Field storage created for field_teaser_title.
   * [WARNING]: means there is some minor error comes on execution of command.
       Example -  [WARNING] Alias for article is already present, skipping.
   * **[ERROR]: means there is some major error which is halting the execution of command.**


.. raw:: html

   <hr style="border: 1px solid grey;">

Generate vocabularies
**********************

**Description**
------------------
   This command is used to generate vocabularies in Drupal.

**Command**
--------------
   ``deg:generate:vocabs``

**Alias**
--------------
    ``deg:v``

**Options**
--------------
    ``--update`` Update existing Vocabulary types with fields and creates new if not present.

.. Note::

   The following messages can come after execution of the command.

   * [OK]: means vocabulary has been created successfully.
       Example - [OK] Vocabulary article_categories is successfully created...
   * [notice]: means field is created successfully.
        Example - [notice] field_title field is created in bundle vocabulary.
   * [WARNING]: means there is some minor error comes on execution of command.
        Example - [WARNING] Vocabulary article_categories Already exists. Skipping creation...

   * **[ERROR]: means there is some major error which is halting the execution of command.**

.. raw:: html

   <hr style="border: 1px solid grey;">


Generate blocks
**********************

**Description**
------------------
   This command is used to generate custom block types in Drupal.

**Command**
--------------
   ``deg:generate:custom_block_type``

**Alias**
--------------
    ``deg:cbt``

**Options**
--------------
    ``--update`` Update existing entity types with fields and creates new if not present.

.. Note::

   The following messages can come after execution of the command.

   * [OK]: means custom block type has been executed successfully.
       Example -  [OK] Custom Block Type custom_block_type is successfully created...
   * [notice]: means field is created successfully.
        Example -  field_block_title field is created in bundle "Custom Block type".

   * [WARNING]: means there is some minor error comes on execution of command.
        Example -  [WARNING] Custom Block Type custom_block_type Already exists. Skipping creation...

   * **[ERROR]: means there is some major error which is halting the execution of command.**


.. raw:: html

   <hr style="border: 1px solid grey;">


Generate image style
**********************

**Description**
------------------
   This command is used to generate image style and effects in Drupal.

**Command**
--------------
   ``deg:generate:imagestyle``

**Alias**
--------------
    ``deg:is``

**Options**
--------------
    ``--update`` Update existing image styles and creates new if not present.

.. Note::

   The following messages can come after execution of the command.

   * [OK]: means image style has been created successfully.
       Example -  [OK] Generating Drupal Image Style...

   * [WARNING]: means there is some minor error comes on execution of command.
        Example -  [WARNING] Image style demo_image_style already exists. Skipping creation...

   * **[ERROR]: means there is some major error which is halting the execution of command.**


.. raw:: html

   <hr style="border: 1px solid grey;">

Generate media
**********************

**Description**
------------------
   This command is used to generate media types in Drupal.

**Command**
--------------
   ``deg:generate:media``

**Alias**
--------------
    ``deg:media``

**Options**
--------------
    ``--update`` Update existing entity types with fields and creates new if not present.

.. Note::

   The following messages can come after execution of the command.

   * [OK]: means media type has been created successfully.
       Example - [OK] Media Type Image_media is successfully created...
   * [notice]: means field is created successfully.
        Example - [notice] field_summary field is created in bundle "Image Media".
   * [WARNING]: means there is some minor error comes on execution of command.
        Example - [WARNING] Media Type Image_media Already exists. Skipping creation...

   * **[ERROR]: means there is some major error which is halting the execution of command.**

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

.. Note::

   The following messages can come after execution of the command.

   * [OK]: means menu has been created successfully.
       Example - [OK] Menu Event menu is successfully created...
   * [notice]: means field is created successfully.
        Example - [notice] field_summary field is created in bundle "Image Media".
   * [WARNING]: means there is some minor error comes on execution of command.
        Example - [WARNING] Menu Sidebar menu Already exists. Skipping creation...

   * **[ERROR]: means there is some major error which is halting the execution of command.**

.. raw:: html

   <hr style="border: 1px solid grey;">


Generate paragraphs
**********************

**Description**
------------------
   This command is used to generate paragraph types in Drupal.

**Command**
--------------
   ``deg:generate:paragraphs``

**Aliases**
--------------
    ``deg:para``, ``deg:p``

**Options**
--------------
    ``--update`` Update existing entity types with fields and creates new if not present.

.. Note::

   The following messages can come after execution of the command.

   * [OK]: means paragraph type has been created successfully.
       Example - [OK] Paragraph Type slider is successfully created...
   * [notice]: means field is created successfully.
        Example - [notice] field_slider_title field is created in bundle "Slider"
   * [WARNING]: means there is some minor error comes on execution of command.
        Example - [Paragraph Type slider Already exists. Skipping creation...

   * **[ERROR]: means there is some major error which is halting the execution of command.**

.. raw:: html

   <hr style="border: 1px solid grey;">


Generate user roles
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

.. Note::

   The following messages can come after execution of the command.

   * [OK]: means user role has been created successfully.
       Example - [OK] user_role Content Editor is successfully created...
   * [WARNING]: means there is some minor error comes on execution of command.
        Example - [WARNING] user_role Site Configurator Already exists. Skipping creation...

   * **[ERROR]: means there is some major error which is halting the execution of command.**

.. raw:: html

   <hr style="border: 1px solid grey;">


Generate workflows
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

.. Note::

   The following messages can come after execution of the command.

   * [OK]: means workflow has been created successfully.
       Example - [OK] Editorial workflow was created successfully...
   * [WARNING]: means there is some minor error comes on execution of command.
        Example - [WARNING] To state Draft is not present for workflow Administrator

   * **[ERROR]: means there is some major error which is halting the execution of command.**


.. raw:: html

   <hr style="border: 1px solid grey;">

Generate all entities
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

