Portfolio module for Moodle, heavily based on the Book module for Moodle (http://moodle.org/) - Copyright (C) 2004-2011  Petr Skoda (http://skodak.org/)

Based on the book module, this activity allows students to create portfolio contributions, with a structure set by the teacher.
The course teacher(s) are able to create an overall structure for the students' work via a series of chapters and subchapters, the same as the layout found in the book module.
The course students are then able to add their own contributions based on this structure, which can then be commented on an graded by the course teacher(s).

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details: http://www.gnu.org/copyleft/gpl.html

Changes:

* 2016-11-21 - Minor M3.2 compatibility fix (only behat affected)
* 2016-10-17 - Fix M3.1 compatibility, add various small features (zip export, link to activity report now optional, optional contribution sharing, option to skip the intro page, automatic gradebook grade on submission, fix bug in handling of user chapters vs standard chapters).
* 2013-11-13 - Global setting to display 'contribution count' on the course page (off by default)
* 2013-10-21 - Fix install for Moodle version < 2.5.

Usage:

* As a teacher, click on 'View/edit portfolio template', then create each of the chapters that you want students to contribute to
(note you must create all chapters before any students start contributing)
* You can fill in each chapter with instructions / introductory information to help students.
* As a student, select a chapter to contribute to, then click on 'Add contribution' and enter the text of your contribution / upload relevant files.
* As a teacher, click on 'Submitted portfolios', then you can filter and search the list of students who have made contributions.
* Click on 'View' to browse through a particular student's contributions and add comments to each one.
* Click on 'Grade' to give overall feedback comments and a grade.

Created by:

* Petr Skoda (skodak) - most of the coding & design
* Mojmir Volf, Eloy Lafuente, Antonio Vicent and others
* Portfolio features by Davo Smith and Manolescu Dorel of Synergy Learning, on behalf of The Goethe Institut.

Project page:

* https://github.com/synergylearning/moodle-mod_giportfolio
* http://moodle.org/plugins/view.php?plugin=mod_giportfolio


Installation:

* http://docs.moodle.org/20/en/Installing_contributed_modules_or_plugins

Issue tracker:

* https://github.com/synergylearning/moodle-mod_giportfolio/issues?milestone=&labels=


Intentionally omitted features:

* more chapter levels - it would encourage teachers to write too much complex and long books, better use standard standalone HTML editor and import it as Resource. DocBook format is another suitable solution.
* TOC hiding in normal view - instead use printer friendly view
* PDF export - there is no elegant way AFAIK to convert HTML to PDF, use virtual PDF printer or better use DocBook format for authoring
* detailed student tracking (postponed till officially supported)
* export as zipped set of HTML pages - instead use browser command Save page as... in print view

Future:

* No more development planned
