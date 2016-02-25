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

Drop the folder into the <moodleroot>/admin/tool directory.

# Evolutions
######################

2015112600 : Adds handling for storing a hash of incoming user picture (before processing) so it can be
given back to sender and checked for changes.
 