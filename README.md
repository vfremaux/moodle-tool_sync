moodle-tool_sync
================

Moodle 2 version of the enrol/sync shifted to admin toolset.

Roadmap
================

Next to do : a REST feeding of incoming files to the command
file.

2017021500
===================================

Add the core extension web services

2017072100
==================================

Add cohort add_members and delete_members wrappers to core functions
to add more flexible and complete identifier types.

2017072500
===================================

Add get_cohorts WS extension to get cohorts by id or idnumber

2017073100
===================================

Add mass enrol/unenrol web services.

2017091900
===================================

Add file area cleanup task to keep number of files acceptable in the sync file area.

X.X.0016
===================================
Fixes get_full_users WS when course invoked by shortname

X.X.0017 :
===================================
- add wildcard input file processing for splitting load

X.X.0019
===================================
Adds handling of special enrol/sync plugin when enrolling by WS

X.X.0021
===================================
Clean the manual tool cinematics. Fix the refresh loading file issue.

X.X.0022
===================================
Add the group and grouping toolkit.

X.X.0023
===================================
Enrol tool : allow pursuing enrolling on disabled plugin instances (global tool config).

X.X.0024 (2018050300)
===================================
Adding provision to choose course identifier field on user synchronisation when
adding course enrolment.

X.X.0025 (2018100100)
===================================
Add group sync task.

Increment X.X.0026 (2018100100)
===================================
control verbosity. Less verbose when on DEVELOPER output.

Increment X.X.0027 (2018100100)
===================================
Add possibility to accept extra columns in CSV without error (user import).
