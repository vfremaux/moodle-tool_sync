This is the Moodle 2 version of the enrol/sync plugin

This plugin concentrates all CSV based approaches for feeding massively Moodle
with initialisations as courses, users, and enrollements, completing all 
existing mechanisms with missing parts in standard processes : 

- Charset and CSV format options
- Cron automation for regular feeding
- Feeding files management and archiving
- Reports and failover files generation
- Full flexibility regarding to entity identity field selection
- creates massively courses and categories (automated, exclusive feature)
- deletes massively courses
- reinitializes massively courses (exclusive feature)
- automates user pictures images loading (exclusive feature)
- automates user creation from CSV
- automates enrollment creation from CSV
- Manual play of all feeding files
- empty groups cleanup
- efficient tool management GUI

plus some local enhancements such as automated group creation and feeding.

Conceptually not innovating, but completing existing processes with the whole
set of features.

# Dependencies
##############

This plugin uses special features from the "publishflow block" for creating course from a 
stored template. Only templates stored in the backup/publishflow file area can be candidates
for rehydrating a new course from a previous backup template.

# Installation
###############

Drop the folder into the <moodleroot>/enrol directory.

This is NOT an interactive enrolment plugin so there is no use to "activate" the plugin into the course enrollement administration.

# Facilitating access to the tool central board
###############################################

This is an optional setup (non standard) that helps to get a usefull link to the
synchronisation toolset : 

The main access to the tool set is at : 

http://<wwwroot>/enrol/sync/sync.php

And cannot be used by non full admin people.

Method 1 : You may provide this link somewhere in some private content accessible to admins. 

Method 2 : Another way is to add a non standard link within the Administration menu that way : 

Somewhere (at the end) in <moodleroot>/admin/settings/top.php, add the following hook : 

// PATCH : Adding local root items to admin menu
if (file_exists($CFG->dirroot.'/local/admin/settings/localtop.php')){
	require $CFG->dirroot.'/local/admin/settings/localtop.php';
}
// /PATCH

This hooks gently an extra call to an additional settings setup sequence from the "local"
customization container, where you can add the required file : 

<moodlerot>/local/admin/settings/localtop.php

(note the name and path is just suggested for architectural clarity).

In this file, just add the additional settings setup :

<?php

$ADMIN->add('root', new admin_externalpage('sync', get_string('enrolname', 'enrol_sync'), "{$CFG->wwwroot}/enrol/sync/sync.php"));

This will add a root level link to the sync tool. (you may choose to add it to the "server" node if you do not want it to appear 
in root tree, but "server" is usually reserved to technical environment aspects.).  


 