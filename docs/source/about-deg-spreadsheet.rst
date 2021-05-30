.. _about_deg_spreadsheet:

About DEG Spreadsheet
===============================

DEG Spreadsheet is a Google Spreadsheet template used for capturing structure of entities, Use `DEG sample template <https://docs.google.com/spreadsheets/d/1ssK5EDNvbMxyI_h_UorJ3ERP1GKJSF8EppMWig9HpH4/edit?pli=1#gid=0>`_
for creation of new Google spreadsheet with same structure to generate different Drupal entities as per requirements.

Overview
-----------------------
This sheet is used to keep track of numbers of entities used in different sheets.

.. list-table:: Overview
   :widths: 25 75
   :header-rows: 1

   * - Column
     - Specifications
   * - Specification
     - List of different entity sheets
   * - Done
     - Number of entities(rows) 'Implemented and done(x)' as per status of column X of that sheet.
   * - Total
     - Number of entities(rows) defined.
   * - %
     - Percentage of entities(rows) 'Implemented and done(x)'.


Bundles
-----------------------
A list of bundles of different entity types in Drupal.

.. list-table:: Bundles
   :widths: 25, 25, 25, 75
   :header-rows: 1

   * - Column
     - Creation support
     - Update support
     - Specifications
   * - ID
     - No
     - No
     - An optional identifier for references in external documents.
   * - Name
     - Yes
     - Yes
     - To keep name of entity types like Article, Page etc.
   * - Machine name
     - Yes
     - No
     - Unique machine name for Drupal bundles.
   * - X
     - Yes
     - Yes
     - Implementation status for bundles, a = Approved and ready to implement , w = Wait to implement, x = Implemented and done, - c = Changed since implemented, d = To be deleted
   * - Dev
     - No
     - No
     - Developer initials, signifying that the row has been implemented as specified.
   * - QA
     - No
     - No
     - Tester initials, signifying that the row has been validated.
   * - Description
     - Yes
     - Yes
     - Description about the bundles.
   * - Example(s)
     - No
     - No
     - One or more relevant examples, optionally hyperlinked.
   * - Mod.
     - No
     - No
     - Moderated (Content moderation is enabled), for example: y = Yes, n = No
   * - Layout
     - No
     - No
     - Layout-enabled, via Core Layout Builder or via Panels, for example: y = Yes, n = No
   * - Trns.
     - No
     - No
     - Translatable, for example: y = Yes, n = No
   * - Migr.
     - No
     - No
     - Migrated (Content will be populated via migration)
   * - Cmnt.
     - No
     - No
     - Commenting is enabled, for example: y = Yes, n = No
   * - Meta.
     - No
     - No
     - Meta tags are enabled, for example: y = Yes, n = No
   * - Sched.
     - No
     - No
     - Schedulable (Scheduled updates are enabled), for example: y = Yes, n = No
   * - Type
     - Yes
     - No
     - Entity type, for example: Content type, Vocabulary, Paragraph type, Media type, Custom block type
   * - URL alias pattern
     - Yes
     - No
     - To configure URL alias pattern.
   * - Settings/notes
     - Yes
     - No
     - To configure settings/notes related to bundle, for example: 'Image' for media type image

Fields
-----------------------
A list for fields which is required for bundles.

.. list-table:: Fields
   :widths: 25, 25, 25, 75
   :header-rows: 1

   * - Column
     - Creation support
     - Update support
     - Specifications
   * - Bundle
     - Yes
     - No
     - Name of bundles like Article (Content type), Page (Content type) etc.
   * - Field label
     - Yes
     - Yes
     - Field label of fields.
   * - Machine name
     - Yes
     - No
     - Unique machine name for Drupal bundles, (use prefix + underscore: 'field + underscore' for field, for 'group + underscore' for group type fields)
   * - X
     - Yes
     - Yes
     - Implementation status for bundles, a = Approved and ready to implement , w = Wait to implement, x = Implemented and done, - c = Changed since implemented, d = To be deleted
   * - Dev
     - No
     - No
     - Developer initials, signifying that the row has been implemented as specified.
   * - QA
     - No
     - No
     - Tester initials, signifying that the row has been validated.
   * - Field group
     - No
     - No
     - Field group to which field comes
   * - Field type
     - Yes
     - No
     - To define field type like Text (formatted, long), Text (plain) etc.
   * - Ref. bundle
     - Yes
     - No
     - Reference of bundle, for example: Article categories (Vocabulary)
   * - Req
     - Yes
     - Yes
     - Is field mandatory or not, for example: y = Yes, n = No
   * - Vals.
     - Yes
     - Yes
     - Allowed number of values, Asterisk (*) means unlimited, for example: 1, 2, 3
   * - Form widget
     - Yes
     - No
     - To define field Form widget, like Text area (multiple rows), Textfield
   * - Trns.
     - No
     - No
     - Translatable, for example: y = Yes, n = No
   * - Settings/notes
     - Yes
     - No
     - To configure settings/notes related to bundle, for example: 'Image' for media type image
   * - Help text
     - Yes
     - Yes
     - Notes about the field

User roles
-----------------------
Defines a list of user roles in Drupal.

.. list-table:: User roles
   :widths: 25, 25, 25, 75
   :header-rows: 1

   * - Column
     - Creation support
     - Update support
     - Specifications
   * - ID
     - No
     - No
     - An optional identifier for references in external documents.
   * - Name
     - Yes
     - Yes
     - To keep name of user roles like Administrators, Content Editor, Content Manager etc.
   * - Machine name
     - Yes
     - No
     - Unique machine name for Drupal bundles.
   * - X
     - Yes
     - Yes
     - Implementation status for bundles, a = Approved and ready to implement , w = Wait to implement, x = Implemented and done, - c = Changed since implemented, d = To be deleted
   * - Dev
     - No
     - No
     - Developer initials, signifying that the row has been implemented as specified.
   * - QA
     - No
     - No
     - Tester initials, signifying that the row has been validated.
   * - Notes
     - No
     - No
     - Notes about the field


Workflows
-----------------------
Defines a list of Drupal Workflows types.

.. list-table:: Workflows
   :widths: 25, 25, 25, 75
   :header-rows: 1

   * - Column
     - Creation support
     - Update support
     - Specifications
   * - ID
     - No
     - No
     - An optional identifier for references in external documents.
   * - Label
     - Yes
     - Yes
     - To keep name of Drupal workflow like Editorial, Administrator etc.
   * - Machine name
     - Yes
     - No
     - Unique machine name for Drupal bundles.
   * - X
     - Yes
     - Yes
     - Implementation status for bundles, a = Approved and ready to implement , w = Wait to implement, x = Implemented and done, - c = Changed since implemented, d = To be deleted
   * - Dev
     - No
     - No
     - Developer initials, signifying that the row has been implemented as specified.
   * - QA
     - No
     - No
     - Tester initials, signifying that the row has been validated.
   * - Type
     - Yes
     - No
     - Workflow type Content Moderation
   * - Notes
     - No
     - No
     - Notes on workflow


Workflow states
-----------------------
Defines a list of Workflow states for workflows.

.. list-table:: Workflow states
   :widths: 25, 25, 25, 75
   :header-rows: 1

   * - Column
     - Creation support
     - Update support
     - Specifications
   * - Workflow
     - Yes
     - No
     - Defines name of Workflow like Editorial, Administrator etc.
   * - Label
     - Yes
     - Yes
     - To keep name of Drupal workflow state like Draft, In review, Published etc.
   * - Machine name
     - Yes
     - No
     - Unique machine name for Drupal bundles.
   * - X
     - Yes
     - Yes
     - Implementation status for bundles, a = Approved and ready to implement , w = Wait to implement, x = Implemented and done, - c = Changed since implemented, d = To be deleted
   * - Dev
     - No
     - No
     - Developer initials, signifying that the row has been implemented as specified.
   * - QA
     - No
     - No
     - Tester initials, signifying that the row has been validated.
   * - Notes
     - No
     - No
     - Notes on workflow states

Workflow transitions
-----------------------
Defines a list of Workflow transitions.

.. list-table:: Workflow transitions
   :widths: 25, 25, 25, 75
   :header-rows: 1

   * - Column
     - Creation support
     - Update support
     - Specifications
   * - ID
     - No
     - No
     - An optional identifier for references in external documents.
   * - Workflow
     - Yes
     - No
     - Name of Workflow like Editorial, Administrator etc.
   * - Label
     - Yes
     - Yes
     - Name of Workflow transitions like Create New Draft, Send to review, Send to Publish etc.
   * - Machine name
     - Yes
     - No
     - Unique machine name for Drupal bundles.
   * - X
     - Yes
     - Yes
     - Implementation status for bundles, a = Approved and ready to implement , w = Wait to implement, x = Implemented and done, - c = Changed since implemented, d = To be deleted
   * - From state
     - Yes
     - No
     - From state, example Draft, In review, Published etc.
   * - To state
     - Yes
     - No
     - To state, example Draft, In review, Published etc.
   * - Dev
     - No
     - No
     - Developer initials, signifying that the row has been implemented as specified.
   * - QA
     - No
     - No
     - Tester initials, signifying that the row has been validated.
   * - Notes
     - No
     - No
     - Notes on workflow states

Menus
-----------------------
Defines a list of menu types in Drupal.

.. list-table:: Menus
   :widths: 25, 25, 25, 75
   :header-rows: 1

   * - Column
     - Creation support
     - Update support
     - Specifications
   * - Label
     - Yes
     - Yes
     - Name of Menus like Article menu, Sidebar menu etc.
   * - Machine name
     - Yes
     - No
     - Unique machine name for Drupal bundles.
   * - X
     - Yes
     - Yes
     - Implementation status for bundles, a = Approved and ready to implement , w = Wait to implement, x = Implemented and done, - c = Changed since implemented, d = To be deleted
   * - Description
     - Yes
     - Yes
     - To keep description of menus
   * - Dev
     - No
     - No
     - Developer initials, signifying that the row has been implemented as specified.
   * - QA
     - No
     - No
     - Tester initials, signifying that the row has been validated.
   * - Notes
     - No
     - No
     - Notes on workflow states

Image styles
-----------------------
Defines a list of image styles in Drupal.

.. list-table:: Image styles
   :widths: 25, 25, 25, 75
   :header-rows: 1

   * - Column
     - Creation support
     - Update support
     - Specifications
   * - Style name
     - Yes
     - Yes
     - Name of image style name like Crop thumbnail, Thumbnail (100×100), Medium (220×220), Large (480×480) etc.
   * - Machine name
     - Yes
     - No
     - Unique machine name for Drupal bundles.
   * - X
     - Yes
     - Yes
     - Implementation status for bundles, a = Approved and ready to implement , w = Wait to implement, x = Implemented and done, - c = Changed since implemented, d = To be deleted
   * - Notes
     - No
     - No
     - Notes on workflow states


Image effects
-----------------------
Defines a list of image style effects in Drupal.

.. list-table:: Image effects
   :widths: 25, 25, 25, 75
   :header-rows: 1

   * - Column
     - Creation support
     - Update support
     - Specifications
   * - Image style
     - Yes
     - No
     - Name of image style name like Crop thumbnail, Thumbnail (100×100), Medium (220×220), Large (480×480) etc.
   * - Effect
     - Yes
     - No
     - Image style effects, example- Scale, Manual crop etc.
   * - X
     - Yes
     - No
     - Implementation status for bundles, a = Approved and ready to implement , w = Wait to implement, x = Implemented and done, - c = Changed since implemented, d = To be deleted
   * - Summary
     - Yes
     - No
     - Summary about image style effects like width, height of image, example -  width 400, uses Freeform crop type, 480×480
   * - Notes
     - No
     - No
     - Notes on workflow states
