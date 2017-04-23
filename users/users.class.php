<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   tool_sync
 * @category  tool
 * @author Funck Thibaut
 * @copyright 2010 Valery Fremaux <valery.fremaux@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_sync;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/admin/tool/sync/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/admin/tool/sync/classes/sync_manager.class.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/group/lib.php');

class users_sync_manager extends sync_manager {

    protected $manualfilerec;

    public function __construct($manualfilerec = null) {
        $this->manualfilerec = $manualfilerec;
    }

    /**
     * Configure elements for the tool configuration form
     */
    public function form_elements(&$frm) {

        $key = 'tool_sync/users_filelocation';
        $label = get_string('usersfile', 'tool_sync');
        $frm->addElement('text', $key, $label);
        $frm->setType('tool_sync/users_filelocation', PARAM_TEXT);

        $frm->addElement('static', 'usersst1', '<hr>');

        $frm->addElement('checkbox', 'tool_sync/users_createpasswords', get_string('createpasswords', 'tool_sync'));
        $frm->addHelpButton('tool_sync/users_createpasswords', 'createpasswords', 'tool_sync');

        $frm->addElement('checkbox', 'tool_sync/users_sendpasswordtousers', get_string('sendpasswordtousers', 'tool_sync'));
        $frm->addHelpButton('tool_sync/users_sendpasswordtousers', 'sendpasswordtousers', 'tool_sync');

        $frm->addElement('checkbox', 'tool_sync/users_allowrename', get_string('allowrename', 'tool_sync'));
        $frm->addHelpButton('tool_sync/users_allowrename', 'allowrename', 'tool_sync');

        $frm->addElement('checkbox', 'tool_sync/users_protectemails', get_string('protectemails', 'tool_sync'));
        $frm->addHelpButton('tool_sync/users_protectemails', 'protectemails', 'tool_sync');

        $identifieroptions = $this->get_userfields();
        $key = 'tool_sync/users_primaryidentity';
        $label = get_string('primaryidentity', 'tool_sync');
        $frm->addElement('select', $key, $label, $identifieroptions);
        $frm->setDefault('tool_sync/users_primaryidentity', 'idnumber');
        $frm->setType('tool_sync/users_primaryidentity', PARAM_TEXT);

        $cronurl = new \moodle_url('/admin/tool/sync/users/execcron.php');
        $params = array('onclick' => 'document.location.href= \''.$cronurl.'\'');
        $frm->addElement('button', 'manualusers', get_string('manualuserrun', 'tool_sync'), $params);

    }

    public function get_userfields() {
        return array('id' => 'id',
                     'idnumber' => 'idnumber',
                     'username' => 'username',
                     'email' => 'email');
    }

    // Override the get_access_icons() function.

    /**
     * Executes this manager main task
     */
    public function cron($syncconfig) {
        global $CFG, $DB, $OUTPUT;

        $systemcontext = \context_system::instance();

        // Internal process controls.
        $createpassword = @$syncconfig->users_createpasswords;
        $updateaccounts = true;
        $allowrenames   = @$syncconfig->users_allowrename;
        $keepexistingemailsafe = (isset($syncconfig->users_protectemails)) ? $syncconfig->users_protectemails : true;
        $notifypasswordstousers = @$syncconfig->users_sendpasswordtousers;

        if (!$adminuser = get_admin()) {
            return;
        }

        if (empty($this->manualfilerec)) {
            $filerec = $this->get_input_file(@$syncconfig->users_filelocation, 'userimport.csv');
        } else {
            $filerec = $this->manualfilerec;
        }

        // We have no file to process. Probably because never setup.
        if (!($filereader = $this->open_input_file($filerec))) {
            return;
        }

        $csvencode = '/\&\#44/';
        if (isset($syncconfig->csvseparator)) {
            $csvdelimiter = '\\' . $syncconfig->csvseparator;
            $csvdelimiter2 = $syncconfig->csvseparator;

            if (isset($CFG->CSV_ENCODE)) {
                $csvencode = '/\&\#' . $CFG->CSV_ENCODE . '/';
            }
        } else {
            $csvdelimiter = "\;";
            $csvdelimiter2 = ";";
        }

        /*
         * File that is used is currently hardcoded here!
         * Large files are likely to take their time and memory. Let PHP know
         * that we'll take longer, and that the process should be recycled soon
         * to free up memory.
         */
        @set_time_limit(0);
        @raise_memory_limit("256M");
        if (function_exists('apache_child_terminate')) {
            @apache_child_terminate();
        }

        $defaultcountry = (empty($CFG->country)) ? 'NZ' : $CFG->country;
        $timezone = (empty($CFG->timezone)) ? '99' : $CFG->timezone;
        $lang = (empty($CFG->lang)) ? 'en' : $CFG->lang;

        // Make arrays of valid fields for error checking.
        $required = array('username' => 1,
                'firstname' => 1,
                'lastname' => 1);

        $optionaldefaults = array(
                'mnethostid' => 1,
                'institution' => '',
                'department' => '',
                'city' => $CFG->defaultcity,
                'country' => $defaultcountry,
                'lang' => $lang,
                'maildisplay' => 1,
                'maildigest' => 0,
                'timezone' => $timezone);

        $optional = array('idnumber' => 1,
                'email' => 1, // Email is optional on upload to clear open ones  and reset at the beginning of the year!
                'auth' => 1,
                'icq' => 1,
                'phone1' => 1,
                'phone2' => 1,
                'address' => 1,
                'url' => 1,
                'description' => 1,
                'mailformat' => 1,
                'maildisplay' => 1,
                'maildigest' => 1,
                'htmleditor' => 1,
                'autosubscribe' => 1,
                'trackforums' => 1,
                'cohort' => 1,
                'cohortid' => 1,
                'course1' => 1,
                'group1' => 1,
                'type1' => 1,
                'role1' => 1,
                'enrol1' => 1,
                'start1' => 1,
                'end1' => 1,
                'wwwroot1' => 1, // Allows MNET propagation to remote node.
                'password' => !$createpassword,
                'suspended' => 1,
                'deleted' => 1,
                'oldusername' => $allowrenames);

        $patterns = array('course', // Patternized items are iterative items with indexing integer appended.
                'group',
                'type',
                'role',
                'enrol',
                'start',
                'end',
                'wwwroot');
        $metas = array(
                'profile_field_.*');

        // Jump any empty or comment line.
        $text = fgets($filereader, 1024);
        $i = 0;
        while (tool_sync_is_empty_line_or_format($text, $i == 0)) {
            $text = tool_sync_read($filereader, 1024, $syncconfig);
            $i++;
        }

        $headers = explode($csvdelimiter2, $text);

        if (!$this->check_headers($headers, $required, $patterns, $metas, $optional, $optionaldefaults)) {
            return;
        }

        $linenum = 2; // Since header is line 1.

        // Header is validated.
        $this->init_tryback(array(implode($syncconfig->csvseparator, $headers)));

        $usersnew     = 0;
        $usersupdated = 0;
        $userserrors  = 0;
        $renames      = 0;
        $renameerrors = 0;

        // Take some from admin profile, other fixed by hardcoded defaults.
        while (!feof($filereader)) {

            // Make a new base record.
            $user = new \StdClass;
            foreach ($optionaldefaults as $key => $value) {
                if ($value == 'adminvalue') {
                    $user->$key = $adminuser->$key;
                } else {
                    $user->$key = $value;
                }
            }

            /*
             * Note: commas within a field should be encoded as &#44 (for comma separated csv files)
             * Note: semicolon within a field should be encoded as &#59 (for semicolon separated csv files)
             */
            $text = tool_sync_read($filereader, 1024, $syncconfig);
            if (tool_sync_is_empty_line_or_format($text, false)) {
                $i++;
                continue;
            }
            $valueset = explode($csvdelimiter2, $text);
            $record = array();

            $tobegenerated = false;

            foreach ($valueset as $key => $value) {
                // Decode encoded commas.
                $record[$headers[$key]] = preg_replace($csvencode, $csvdelimiter2, trim($value));
            }

            if ($record[$headers[0]]) {
                // Add a new user to the database.
                // Add fields to object $user.
                foreach ($record as $name => $value) {
                    if ($name == 'wwwroot') {
                        // Process later.
                        continue;
                    }

                    // Check for required values.
                    if (isset($required[$name]) and !$value) {
                        $message = get_string('missingfield', 'error', $name).' ';
                        $message .= get_string('erroronline', 'error', $linenum).". ".get_string('missingfield', 'error', $name);
                        $this->report($message);
                        return;
                    } else if ($name == 'password') {

                        if (empty($value)) {
                            $user->password = 'to be generated';
                            $tobegenerated = true;
                        } else if ($value != '*NOPASS*') {
                            // Password needs to be encrypted.
                            $user->password = hash_internal_user_password($value);
                            if ($notifypasswordstousers) {
                                if (!empty($user->email) && (!preg_match('/NO MAIL|NOMAIL/', $user->email))) {
                                    // If we can send mail to user, let's notfy with the moodle password notification mail.
                                    sync_notify_new_user_password($user, $value);
                                }
                            }
                        } else {
                            // Mark user having no password.
                            $user->password = '*NOPASS*';
                        }
                    } else if ($name == 'username') {
                        $user->username = \core_text::strtolower($value);
                    } else {
                        // Normal entry.
                        $user->{$name} = $value;
                    }
                }

                if (isset($user->deleted)) {
                    $userid = @$syncconfig->users_primaryidentity;
                    if (empty($userid)) {
                        $userid = 'username';
                    }
                    if ($olduser = $DB->get_record('user', array($syncconfig->users_primaryidentity => $user->$userid))) {
                        delete_user($olduser);
                        continue;
                    }
                }

                if (isset($user->country)) {
                    $user->country = strtoupper($user->country);
                }
                if (isset($user->lang)) {
                    $user->lang = str_replace('_utf8', '', strtolower($user->lang));
                }
                $user->confirmed = 1;
                $user->timemodified = time();
                $linenum++;
                $username = $user->username;
                $firstname = $user->firstname;
                $lastname = $user->lastname;
                $idnumber = @$user->idnumber;

                $ci = 1;
                $courseix = 'course'.$ci;
                $groupix = 'group'.$ci;
                $typeix = 'type'.$ci;
                $roleix = 'role'.$ci;
                $enrolix = 'enrol'.$ci;
                $startix = 'start'.$ci;
                $endix = 'end'.$ci;
                $wwwrootix = 'wwwroot'.$ci;
                $addcourses = array();
                while (isset($user->$courseix)) {
                    $coursetoadd = new \StdClass;
                    $coursetoadd->idnumber = $user->$courseix;
                    $coursetoadd->group = isset($user->$groupix) ? $user->$groupix : null;
                    $coursetoadd->type = isset($user->$typeix) ? $user->$typeix : null;  // Deprecated. Not more used.
                    $coursetoadd->role = isset($user->$roleix) ? $user->$roleix : null;
                    $coursetoadd->enrol = isset($user->$enrolix) ? $user->$enrolix : null;
                    $coursetoadd->start = isset($user->$startix) ? $user->$startix : 0;
                    $coursetoadd->end = isset($user->$endix) ? $user->$endix : 0;
                    $coursetoadd->wwwroot = isset($user->$wwwrootix) ? $user->$wwwrootix : 0;
                    $addcourses[] = $coursetoadd;
                    $ci++;
                    $courseix = 'course'.$ci;
                    $groupix = 'group'.$ci;
                    $typeix = 'type'.$ci;
                    $roleix = 'role'.$ci;
                    $startix = 'start'.$ci;
                    $endix = 'end'.$ci;
                    $wwwrootix = 'wwwroot'.$ci;
                }

                /*
                 * Before insert/update, check whether we should be updating
                 * an old record instead
                 */
                if ($allowrenames && !empty($user->oldusername) ) {
                    $user->oldusername = moodle_strtolower($user->oldusername);
                    $params = array('username' => $user->oldusername, 'mnethostid' => $user->mnethostid);
                    if ($olduser = $DB->get_record('user', $params)) {
                        if ($DB->set_field('user', 'username', $user->username, array('username' => $user->oldusername))) {
                            $this->report(get_string('userrenamed', 'admin')." : $user->oldusername $user->username");
                            $renames++;
                        } else {
                            $this->report(get_string('usernotrenamedexists', 'tool_sync')." : $user->oldusername $user->username");
                            $renameerrors++;
                            continue;
                        }
                    } else {
                        $this->report(get_string('usernotrenamedmissing', 'tool_sync')." : $user->oldusername $user->username");
                        $renameerrors++;
                        continue;
                    }
                }

                // Set some default.
                if (empty($syncconfig->users_primaryidentity)) {
                    if (!isset($CFG->users_primaryidentity)) {
                        set_config('users_primaryidentity', 'idnumber', 'tool_sync');
                        $syncconfig->users_primaryidentity = 'idnumber';
                    } else {
                        set_config('users_primaryidentity', $CFG->primaryidentity, 'tool_sync');
                        $syncconfig->users_primaryidentity = $CFG->primaryidentity;
                    }
                }

                if (empty($user->mnethostid)) {
                    $user->mnethostid = $CFG->mnet_localhost_id;
                }

                if (($syncconfig->users_primaryidentity == 'idnumber') && !empty($idnumber)) {
                    $olduser = $DB->get_record('user', array('idnumber' => $idnumber, 'mnethostid' => $user->mnethostid));
                } else if (($syncconfig->users_primaryidentity == 'email') && !empty($user->email)) {
                    $olduser = $DB->get_record('user', array('email' => $user->email, 'mnethostid' => $user->mnethostid));
                } else {
                    $olduser = $DB->get_record('user', array('username' => $username, 'mnethostid' => $user->mnethostid));
                }
                if ($olduser) {
                    if ($updateaccounts) {
                        // Record is being updated.
                        $user->id = $olduser->id;
                        if ($olduser->deleted) {
                            // Revive old deleted users if they already exist.
                            $this->report(get_string('userrevived', 'tool_sync', "$user->username ($idnumber)"));
                            $user->deleted = 0;
                        }
                        if ($keepexistingemailsafe) {
                            unset($user->email);
                        }
                        try {
                            // This triggers event as required.
                            if (empty($syncconfig->simulate)) {
                                user_update_user($user, false);
                                $message = "$user->firstname $user->lastname as [$user->username] ($idnumber)";
                                $reportline = get_string('useraccountupdated', 'tool_sync', $message);
                                $this->report($reportline);
                            } else {
                                $message = "$user->firstname $user->lastname as [$user->username] ($idnumber)";
                                $this->report('SIMULATION : '.get_string('useraccountupdated', 'tool_sync', $message));
                            }

                            $usersupdated++;
                        } catch (Exception $e) {
                            if (!empty($syncconfig->filefailed)) {
                                $this->feed_tryback($text);
                            }
                            $message = "[$username] $lastname $firstname ($idnumber)";
                            $this->report(get_string('usernotupdatederror', 'tool_sync', $message));
                            $userserrors++;
                            continue;
                        }

                        // Save custom profile fields data from csv file.
                        if (empty($syncconfig->simulate)) {
                            profile_save_data($user);
                        }
                    } else {
                        /*
                         * Record not added - user is already registered
                         * In this case, output userid from previous registration
                         * This can be used to obtain a list of userids for existing users
                         */
                        $message = "[$username] $lastname $firstname ($user->idnumber)";
                        $this->report("$olduser->id ".get_string('usernotaddedregistered', 'error', $message));
                        $userserrors++;
                    }
                } else {
                    // New user.
                    // Pre check we have no username collision.
                    $params = array('mnethostid' => $user->mnethostid, 'username' => $user->username);
                    if ($olduser = $DB->get_record('user', $params)) {
                        $message = "$olduser->id , $user->username , $user->idnumber, $user->firstname, $user->lastname ";
                        $this->report(get_string('usercollision', 'tool_sync', $message));
                        continue;
                    }

                    try {
                        if (empty($syncconfig->simulate)) {
                            // This will also trigger the event.
                            $user->id = user_create_user($user, false);
                            $this->report(get_string('useraccountadded', 'tool_sync', "$user->id , $user->username "));
                            $usersnew++;
                            if (empty($user->password) && $createpassword) {
                                // Passwords will be created and sent out on cron.
                                $pref = new \StdClass();
                                $pref->userid = $newuser->id;
                                $pref->name = 'create_password';
                                $pref->value = 1;
                                $DB->insert_record('user_preferences', $pref);

                                $pref = new \StdClass();
                                $pref->userid = $newuser->id;
                                $pref->name = 'auth_forcepasswordchange';
                                $pref->value = $forcepasswordchange;
                                $DB->insert_record('user_preferences', $pref);
                            }

                            // Save custom profile fields data from csv file.
                            profile_save_data($user);
                        } else {
                            $message = "$user->id , $user->username ";
                            $this->report('SIMULATION : '.get_string('useraccountadded', 'tool_sync', $message));
                            $usersnew++;
                        }

                    } catch (Exception $e) {
                        // Record not added -- possibly some other error.
                        if (!empty($syncconfig->filefailed)) {
                            $this->feed_tryback($text);
                        }
                        $message = "[$username] $lastname $firstname ($idnumber)";
                        $this->report(get_string('usernotaddederror', 'tool_sync', $message));
                        $userserrors++;
                        continue;
                    }
                }

                // Post create check password handling. We need ID of the user !
                if ($tobegenerated && empty($syncconfig->simulate)) {
                    set_user_preference('create_password', 1, $user);
                }

                // Cohort (only system level) binding management.
                if (@$user->cohort) {
                    $t = time();
                    if (!$cohort = $DB->get_record('cohort', array('name' => $user->cohort))) {
                        $cohort = new \StdClass();
                        $cohort->name = $user->cohort;
                        $cohort->idnumber = @$user->cohortid;
                        $cohort->descriptionformat = FORMAT_MOODLE;
                        $cohort->contextid = $systemcontext->id;
                        $cohort->timecreated = $t;
                        $cohort->timemodified = $t;
                        if (empty($syncconfig->simulate)) {
                            $cohort->id = $DB->insert_record('cohort', $cohort);
                        } else {
                            $this->report('SIMULATION : '.get_string('creatingcohort', 'tool_sync', $cohort->name));
                        }
                    }

                    // Bind user to cohort.
                    $params = array('userid' => $user->id, 'cohortid' => $cohort->id);
                    if (!$cohortmembership = $DB->get_record('cohort_members', $params)) {
                        $cohortmembership = new \StdClass();
                        $cohortmembership->userid = $user->id;
                        $cohortmembership->cohortid = ''.@$cohort->id;
                        $cohortmembership->timeadded = $t;
                        if (empty($syncconfig->simulate)) {
                            $cohortmembership->id = $DB->insert_record('cohort_members', $cohortmembership);
                        } else {
                            $this->report('SIMULATION : '.get_string('registeringincohort', 'tool_sync', $cohort->name));
                        }
                    }
                }

                // Course binding management.
                if (!empty($addcourses)) {
                    foreach ($addcourses as $c) {

                        if (empty($c->idnumber)) {
                            // Empty course sets should be ignored.
                            continue;
                        }

                        if (empty($c->wwwroot)) {
                            // Course binding is local.

                            if (!$crec = $DB->get_record('course', array('idnumber' => $c->idnumber))) {
                                $this->report(get_string('unknowncourse', 'error', $c->idnumber));
                                continue;
                            }

                            if (!empty($c->enrol)) {
                                $enrol = enrol_get_plugin('manual');
                                $params = array('enrol' => $c->enrol, 'courseid' => $crec->id, 'status' => ENROL_INSTANCE_ENABLED);
                                if (!$enrols = $DB->get_records('enrol', $params, 'sortorder ASC')) {
                                    $this->report(get_string('errornomanualenrol', 'tool_sync'));
                                    $c->enrol = '';
                                } else {
                                    $enrol = reset($enrols);
                                    $enrolplugin = enrol_get_plugin($c->enrol);
                                }
                            }

                            $coursecontext = \context_course::instance($crec->id);
                            if (!empty($c->role)) {
                                $role = $DB->get_record('role', array('shortname' => $c->role));
                                if (!empty($c->enrol)) {

                                    $e = new \StdClass();
                                    $e->myuser = $user->username; // User identifier.
                                    $e->mycourse = $crec->idnumber; // Course identifier.

                                    try {
                                        if (empty($syncconfig->simulate)) {
                                            $enrolplugin->enrol_user($enrol, $user->id, $role->id, time(), 0, ENROL_USER_ACTIVE);
                                            $this->report(get_string('enrolled', 'tool_sync', $e));
                                        } else {
                                            $this->report('SIMULATION : '.get_string('enrolled', 'tool_sync', $e));
                                        }
                                        $ret = true;
                                    } catch (Exception $exc) {
                                        $this->report(get_string('errorenrol', 'tool_sync', $e));
                                    }
                                } else {
                                    if (!user_can_assign($coursecontext, $c->role)) {
                                        assert(true);
                                        // Notify Can not assign role in course'); // TODO: localize.
                                    }
                                    if (empty($syncconfig->simulate)) {
                                        $ret = role_assign($role->id, $user->id, $coursecontext->id);
                                        $e = new \StdClass();
                                        $e->contextid = $coursecontext->id;
                                        $e->rolename = $c->role;
                                        $this->report(get_string('roleadded', 'tool_sync', $e));
                                    } else {
                                        $e = new \StdClass();
                                        $e->contextid = $coursecontext->id;
                                        $e->rolename = $c->role;
                                        $this->report('SIMULATION : '.get_string('roleadded', 'tool_sync', $e));
                                    }
                                }
                            } else {
                                if (!empty($c->enrol)) {
                                    $role = $DB->get_record('role', array('shortname' => 'student'));
                                    $e = new \StdClass();
                                    $e->mycourse = $c->idnumber;
                                    $e->myuser = $user->username;
                                    if (empty($syncconfig->simulate)) {
                                        $enrolplugin->enrol_user($enrol, $user->id, $role->id, time(), 0, ENROL_USER_ACTIVE);
                                        $this->report(get_string('enrolled', 'tool_sync', $e));
                                    } else {
                                        $this->report('SIMULATION : '.get_string('enrolled', 'tool_sync', $e));
                                    }
                                }
                            }
                            if (!@$ret) {
                                // OK.
                                $e = new \StdClass();
                                $e->mycourse = $c->idnumber;
                                $e->myuser = $user->username;
                                $this->report(get_string('enrollednot', 'tool_sync', $e));
                            }

                            // We only can manage groups for successful enrollments.

                            if (@$ret) {
                                // Check group existance and try to create.
                                if (!empty($c->group)) {
                                    if (!$gid = groups_get_group_by_name($crec->id, $c->group)) {
                                        $groupsettings = new \StdClass();
                                        $groupsettings->name = $c->group;
                                        $groupsettings->courseid = $crec->id;
                                        if (empty($syncconfig->simulate)) {
                                            if (!$gid = groups_create_group($groupsettings)) {
                                                $this->report(get_string('groupnotaddederror', 'tool_sync', $c->group));
                                            }
                                        } else {
                                            $this->report('SIMULATION : '.get_string('groupadded', 'tool_sync', $c->group));
                                        }
                                    }

                                    if ($gid) {
                                        if (count(get_user_roles($coursecontext, $user->id))) {
                                            if (empty($syncconfig->simulate)) {
                                                if (groups_add_member($gid, $user->id)) {
                                                    $this->report(get_string('addedtogroup', '', $c->group));
                                                } else {
                                                    $this->report(get_string('addedtogroupnot', '', $c->group));
                                                }
                                            } else {
                                                $this->report('SIMULATION : '.get_string('addedtogroup', '', $c->group));
                                            }
                                        } else {
                                            $this->report(get_string('addedtogroupnotenrolled', '', $c->group));
                                        }
                                    }
                                }
                            }
                        }

                        /*
                         * if we can propagate user to designates wwwroot let's do it
                         * only if the VMoodle block is installed.
                         */
                        if (empty($syncconfig->simulate)) {
                            if (!empty($c->wwwroot) && $DB->get_record('block', array('name' => 'vmoodle'))) {
                                if (!file_exists($CFG->dirroot.'/blocks/vmoodle/rpclib.php')) {
                                    echo $OUTPUT->notification('This feature works with VMoodle Virtual Moodle Implementation');
                                    continue;
                                }
                                include_once($CFG->dirroot.'/blocks/vmoodle/rpclib.php');
                                include_once($CFG->dirroot.'/mnet/xmlrpc/client.php');

                                // Imagine we never did it before.
                                $mnet = new \mnet_environment();
                                $mnet->init();

                                $this->report(get_string('propagating', 'vmoodle', fullname($user)));
                                $caller = new \StdClass();
                                $caller->username = 'admin';
                                $caller->remoteuserhostroot = $CFG->wwwroot;
                                $caller->remotehostroot = $CFG->wwwroot;

                                // Check if exists.
                                $exists = false;
                                if ($return = mnetadmin_rpc_user_exists($caller, $user->username, $c->wwwroot, true)) {
                                    $response = json_decode($return);
                                    if (empty($response)) {
                                        continue;
                                    }
                                    if ($response->status == RPC_FAILURE_DATA) {
                                        $this->report(get_string('errorrpcparams', 'tool_sync', implode("\n", $response->errors)));
                                        continue;
                                    } else if ($response->status == RPC_FAILURE) {
                                        $this->report(get_string('rpcmajorerror', 'tool_sync'));
                                        continue;
                                    } else if ($response->status == RPC_SUCCESS) {
                                        if (!$response->user) {
                                            $this->report(get_string('userunknownremotely', 'tool_sync', fullname($user)));
                                            $exists = false;
                                        } else {
                                            $this->report(get_string('userexistsremotely', 'tool_sync', fullname($user)));
                                            $exists = true;
                                        }
                                    }
                                }
                                $created = false;
                                if (!$exists) {
                                    if ($return = mnetadmin_rpc_create_user($caller, $user->username, $user, '',
                                                                            $c->wwwroot, false)) {
                                        $response = json_decode($return);
                                        if (empty($response)) {
                                            $this->report(get_string('remoteserviceerror', 'tool_sync'));
                                            continue;
                                        }
                                        if ($response->status != RPC_SUCCESS) {
                                            $this->report(get_string('communicationerror', 'tool_sync'));
                                        } else {
                                            $u = new \StdClass();
                                            $u->username = $user->username;
                                            $u->wwwroot = $c->wwwroot;
                                            $this->report(get_string('usercreatedremotely', 'tool_sync', $u));
                                            $created = true;
                                        }
                                    }
                                }

                                // Process remote course enrolment.
                                if (!empty($c->role)) {
                                    $response = mnetadmin_rpc_remote_enrol($caller, $user->username, $c->role,
                                            $c->wwwroot, 'shortname', $c->idnumber, $c->start, $c->end, false);
                                    if (empty($response)) {
                                        $this->report(get_string('remoteserviceerror', 'tool_sync'));
                                        continue;
                                    }
                                    if ($response->status != RPC_SUCCESS) {
                                        $message = implode("\n", $response->errors);
                                        $this->report(get_string('communicationerror', 'tool_sync', $message));
                                    } else {
                                        // In case this block is installed, mark access authorisations in the user's profile.
                                        if (file_exists($CFG->dirroot.'/blocks/user_mnet_hosts/xlib.php')) {
                                            include_once($CFG->dirroot.'/blocks/user_mnet_hosts/xlib.php');
                                            if ($result = user_mnet_hosts_add_access($user, $c->wwwroot)) {
                                                if (preg_match('/error/', $result)) {
                                                    $message = get_string('errorsettingremoteaccess', 'tool_sync', $result);
                                                    $this->report($message);
                                                } else {
                                                    $this->report($result);
                                                }
                                            }
                                        }
                                        $e = new \StdClass();
                                        $e->username = $user->username;
                                        $e->rolename = $c->role;
                                        $e->coursename = $c->idnumber;
                                        $e->wwwroot = $c->wwwroot;
                                        $this->report(get_string('remoteenrolled', 'tool_sync', $e));
                                    }
                                }
                            }
                        }
                    }
                }
                unset($user);
            }
        }
        fclose($filereader);

        if (!empty($syncconfig->storereport)) {
            $this->store_report_file($filerec);
        }
        if (!empty($syncconfig->filearchive)) {
            $this->archive_input_file($filerec);
        }
        if (!empty($syncconfig->filecleanup)) {
            $this->cleanup_input_file($filerec);
        }

        return true;
    }
}
