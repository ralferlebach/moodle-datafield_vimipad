moodle-datafield_vimipad
========================

[![Moodle Plugin CI](https://github.com/ralferlebach/moodle-datafield_vimipad/actions/workflows/moodle-ci.yml/badge.svg?branch=main)](https://github.com/ralferlebach/moodle-datafield_vimipad/actions?query=workflow%3A%22Moodle+Plugin+CI%22+branch%3Amain) [![ViMi Pad: Database field](https://img.shields.io/badge/ViMi%20Pad-Database%20field-0f6cbf)](https://ralferlebach.github.io/Moodle-ViMiPad-Plugins/)

The ViMi Pad database field adds a knowledge map as a field type in the Database activity, so a map can be one column of a collection that participants build together.

ViMi Pad is not a single plugin but a family of four that work as one system. They are released
together, carry the same version number and the three satellites declare the activity as a
dependency, so a satellite is only ever as current as the activity it was qualified against.

* **mod_vimipad** is the editor and the data model: it owns the map itself - nodes, relations, containers, revisions, snapshots, annotations and grades - and exposes the public map API that the other three build on.
* **mod_vimigallery** is the reading view: it shows a set of maps as an album, one at a time, embedded on the course page or behind a link.
* **qtype_vimipad** turns a map into a quiz question: learners answer by drawing, and the answer is marked automatically against a reference map.
* **datafield_vimipad** adds a map as a field type in the Database activity, so a map can be one column of a collection.

This README documents **datafield_vimipad** - the fourth bullet point above. The other three plugins
are documented in their own repositories.

Because the responsibilities are split this way, this plugin owns no storage. The map lives in the
Database activity's own content table, and this plugin only decides how that value is edited,
displayed and searched.


Requirements
------------

This plugin requires Moodle 4.5+

It also requires the ViMi Pad activity, declared as a dependency in version.php and installed in the
same version (currently 1.0.0 / 2026100500):

* **mod_vimipad (ViMi Pad)** - required dependency: the field embeds its editor and validates values through its map API\
  https://github.com/ralferlebach/moodle-mod_vimipad


Motivation for this plugin
--------------------------

A Database activity is the natural place for a collection a whole course builds together: one entry
per case, per species, per machine. Some of those entries are better described by a small diagram
than by a paragraph.

Until now that meant attaching an image, which turns the diagram into something a reader can look at
but nobody can continue. This plugin makes the map a first-class field: editable in the entry form,
readable in the list, and searchable by the words inside it.


Installation
------------

Install the plugin like any other plugin to folder
/mod/data/field/vimipad

See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins


Usage & Settings
----------------

After installing the plugin, it does not do anything to Moodle yet. Add a field of type "ViMi Pad" to
a Database activity; participants then draw their map directly in the entry form.

The field has no site-wide settings. Each field chooses one diagram form, which applies to every
entry in that column so the collection stays comparable. Once entries hold maps, that choice is
locked - changing it would leave existing maps that no longer match their own field.

If you want to learn more about using the Database activity in Moodle, please see https://docs.moodle.org/en/Database_activity.


Capabilities
------------

This plugin does not add any additional capabilities. Who may add, edit and view entries is decided
by the Database activity.


Scheduled Tasks
---------------

This plugin does not add any additional scheduled tasks.


How this plugin works / Pitfalls
--------------------------------

The map is stored in the Database activity's content table like any other field value, so backup,
restore, course duplication and the privacy API handle it along with the rest of the entry. This
plugin stores nothing of its own, which is also why it declares no personal data.

In the entry form the embedded editor is live; in list and single view the same map is rendered
read-only. Searching the field matches the text inside the map, so an entry can be found by a
concept it contains rather than only by its title.

**Pitfall:** the diagram form is fixed once maps exist. Plan which form fits the collection before
participants start, or add a second field rather than converting the first.

**Pitfall:** a Database activity needs its templates generated before fields appear in the list view.
Moodle does this when you add the first field through the interface; a database built by script may
need the templates generated explicitly.

Theme support
-------------

This plugin is developed and tested on Moodle Core's Boost theme.
It should also work with Boost child themes, including Moodle Core's Classic theme. However, we can't support any other theme than Boost.


Plugin repositories
-------------------

This plugin is not published in the Moodle plugins repository.

The latest development version can be found on Github:
https://github.com/ralferlebach/moodle-datafield_vimipad

An overview of the whole plugin family is published at:
https://ralferlebach.github.io/Moodle-ViMiPad-Plugins/


Bug and problem reports / Support requests
------------------------------------------

This plugin is carefully developed and thoroughly tested, but bugs and problems can always appear.

Please report bugs and problems on Github:
https://github.com/ralferlebach/moodle-datafield_vimipad/issues

We will do our best to solve your problems, but please note that due to limited resources we can't always provide per-case support.


Feature proposals
-----------------

Due to limited resources, the functionality of this plugin is primarily implemented for our own local needs and published as-is to the community. We are aware that members of the community will have other needs and would love to see them solved by this plugin.

Please issue feature proposals on Github:
https://github.com/ralferlebach/moodle-datafield_vimipad/issues

Please create pull requests on Github:
https://github.com/ralferlebach/moodle-datafield_vimipad/pulls

We are always interested to read about your feature proposals or even get a pull request from you, but please accept that we can handle your issues only as feature _proposals_ and not as feature _requests_.


Moodle release support
----------------------

Due to limited resources, this plugin is only maintained for the most recent major release of Moodle as well as the most recent LTS release of Moodle. Bugfixes are backported to the LTS release. However, new features and improvements are not necessarily backported to the LTS release.

Apart from these maintained releases, previous versions of this plugin which work in legacy major releases of Moodle are still available as-is without any further updates in the Moodle Plugins repository.

There may be several weeks after a new major release of Moodle has been published until we can do a compatibility check and fix problems if necessary. If you encounter problems with a new major release of Moodle - or can confirm that this plugin still works with a new major release - please let us know on Github.

This plugin is designed to be compatible with all currently supported versions of Moodle, leveraging its latest APIs. However, if you are using a legacy version of Moodle, we kindly advise against installing or using this plugin. Instead, we strongly recommend updating your Moodle instance to a supported version to ensure security and compliance with current technological standards. Thank you for your understanding.


Translating this plugin
-----------------------

This Moodle plugin is provided with English and German language packs only. Translations into other languages must be managed through AMOS (https://lang.moodle.org), where they will become part of Moodle's official language pack.

As the plugin creator, we continue to maintain the German translation. For all other languages, we kindly ask you to contribute your translations directly in AMOS. These contributions will be reviewed by Moodle's official language pack maintainers before being included in the official repository.

Thank you for supporting the global Moodle community!


Right-to-left support
---------------------

This plugin has not been tested with Moodle's support for right-to-left (RTL) languages.
If you want to use this plugin with a RTL language and it doesn't work as-is, you are free to send us a pull request on Github with modifications.


Maintainers
-----------

The plugin is maintained by\
Ralf Erlebach


Copyright
---------

The copyright of this plugin is held by\
Ralf Erlebach

Individual copyrights of individual developers are tracked in PHPDoc comments and Git commits.
