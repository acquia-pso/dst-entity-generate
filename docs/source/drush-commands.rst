.. _drush_commands_list:

Drush Commands
#######################
A list of Drush commands used to generate the drupal entities in Drupal using Drupal Entity Generator (DEG) Spreadsheet ,


Generate content types
**********************

**Command**
--------------
   deg:generate:content_types

**Aliases**
--------------
   deg:content_types, deg:ct

**Description**
------------------
   This command is used to generate all types of content with fields in Drupal.

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

**Command**
--------------
   deg:generate:vocabs

**Alias**
--------------
    deg:v

**Description**
------------------
   This command is used to generate vocabularies in Drupal.

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

**Command**
--------------
   deg:generate:custom_block_type

**Alias**
--------------
    deg:cbt

**Description**
------------------
   This command is used to generate custom block types in Drupal.

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

**Command**
--------------
   deg:generate:imagestyle

**Alias**
--------------
    deg:is

**Description**
------------------
   This command is used to generate image style and effects in Drupal.

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

**Command**
--------------
   deg:generate:media

**Alias**
--------------
    deg:media

**Description**
------------------
   This command is used to generate media types in Drupal.

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

**Command**
--------------
   deg:generate:menus

**Alias**
--------------
    deg:m

**Description**
------------------
   This command is used to generate menu types in Drupal.

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

**Command**
--------------
   deg:generate:paragraphs

**Aliases**
--------------
    deg:para, deg:p

**Description**
------------------
   This command is used to generate paragraph types in Drupal.

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

**Command**
--------------
   deg:generate:user-roles

**Alias**
--------------
    deg:ur

**Description**
------------------
   This command is used to generate user roles in Drupal.

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

**Command**
--------------
   deg:generate:workflow

**Alias**
--------------
    deg:w

**Description**
------------------
   This command is used to generate workflows, states and workflow transitions in Drupal.

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

**Command**
------------
   deg:generate

**Aliases**
------------
   deg:generate:all, deg:ga

**Description**
----------------
   Runs all DEG drush commands together.

.. Note::

   Run DEG commands separately to get better visibility on entity generation.

.. raw:: html

   <hr style="border: 1px solid grey;">

