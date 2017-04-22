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
 * @package     tool_sync
 * @category    tool
 * @author      Funck Thibaut
 * @copyright   2010 Valery Fremaux <valery.fremaux@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addedtogroup'] = 'L\'utilisateur {$a->myuser} a été ajouté au groupe {$a->group}';
$string['addedtogroupnot'] = 'L\'utilisateur {$a->myuser} n\'a pas été ajouté au groupe {$a->group}';
$string['allowrename'] = 'Permettre le changement d\'identifiant';
$string['alreadyassigned'] = 'L\'utilisateur {$a->myuser} est déjà assigné au role {$a->myrole} dans le cours {$a->mycourse}';
$string['archivecontrolfiles'] = 'Si activé, archive les fichiers de controle après exécution';
$string['assign'] = 'Role "{$a->myrole}" assigné à {$a->myuser} dans le cours {$a->mycourse}';
$string['automation'] = 'Alimentations et automatisation';
$string['backtoprevious'] = 'Retourner à la page précédente';
$string['builddeletefile'] = 'Générer un fichier de suppression';
$string['buildresetfile'] = 'Générer un fichier de réinitialisation';
$string['button'] = 'Enregistrer la configuration des outils';
$string['categoryremoved'] = 'Catégorie {$a} supprimée';
$string['checkingcourse'] = 'Vérification d\'existence des cours';
$string['choosecoursetodelete'] = 'Selection des cours à supprimer&nbsp;:';
$string['choosecoursetoreset'] = 'Selection des cours à réinitialiser&nbsp;:';
$string['cleancategories'] = 'Nettoyage des catégories de cours vides';
$string['cleancats'] = 'Nettoyer les catégories';
$string['cohortalreadymember'] = 'L\'utilisateur {$a->username} ({$a->idnumber}) est déjà membre de {$a->cname}';
$string['cohortautocreate'] = 'Créer les cohortes manquantes';
$string['cohortbindingadded'] = 'Cohorte {$a->cohort} ajoutée au cours {$a->course} avec le role {$a->role}';
$string['cohortbindingdisabled'] = 'Cohorte {$a->cohort} désactivée du cours {$a->course} pour le role {$a->role}';
$string['cohortbindingmanual'] = 'Accrocher les cohortes aux cours';
$string['cohortcohortidentifier'] = 'Identifiant de cohorte';
$string['cohortcoursebindingfilelocation'] = 'Fichier des liaisons des cohortes aux cours';
$string['cohortcourseidentifier'] = 'Identifiant de cours';
$string['cohortfreed'] = 'Cohorte [{$a->idnumber}] {$a->name} réinitialisée';
$string['cohortnotexists'] = 'Cohorte {$a} inexistante. Abandon.';
$string['cohortcreated'] = 'Cohorte {$a->name} ajoutée';
$string['cohortfilelocation'] = 'Emplacement du fichier de cohortes';
$string['cohortmanualsync'] = 'Exécution de la synchronisation des cohortes';
$string['cohortmemberadded'] = 'Utilisateur {$a->username} ({$a->idnumber})  ajouté à la cohorte {$a->cname}';
$string['cohortmemberremoved'] = 'Utilisateur {$a->username} ({$a->idnumber}) supprimé de la cohorte {$a->cname}';
$string['cohortmgtmanual'] = 'Gestion manuelle des cohortes';
$string['cohortnotfound'] = 'La cohorte {$a->identifier} identifié par {$a->cid} n\'a pas été trouvée. La création n\'est pas possible.';
$string['cohortroleidentifier'] = 'Identifiant de role';
$string['cohortsconfig'] = 'Configuration de la synchronisation des cohortes';
$string['cohortsstarting'] = 'Cohort sync starting...';
$string['cohortsync'] = 'Synchonisation des cohortes';
$string['cohortsyncdelete'] = 'Détruire les cohortes vides';
$string['cohortuseridentifier'] = 'Identifiant d\'utilisateur';
$string['cohortusernotfound'] = 'L\'utilisateur {$a->identifier} identifié par {$a->uid} n\'a pas été trouvé.';
$string['cohortupdated'] = 'La cohorte [{$a->id}] {$a->name} a été mise à jour.';
$string['cohortcreationskipped'] = 'La cohorte {$a->name} n\'a pas été créée.';
$string['commandfile'] = 'Fichier de commande';
$string['communicationerror'] = 'Erreur de communication avec le distant. Erreurs : {$a}';
$string['configdefaultcmd'] = 'Configuration par défaut pour la colonne cmd';
$string['configuration'] = 'Configuration du format des fichiers d\'entrée';
$string['confirm'] = 'Confirmer';
$string['confirmcleancats'] = 'Supprimer les catégories vides';
$string['confirmdelete'] = 'Supprimer les cours avec ce fichier';
$string['coursecatdeleted'] = 'La catégorie de cours {$a} a été supprimée.';
$string['coursecheck'] = 'Vérification des cours';
$string['coursecreated'] = 'Le cours [{$a->shortname}] {$a->fullname} a été créé.';
$string['coursecronprocessing'] = 'Exécution de la synchronisation des cours';
$string['coursedefaultsummary'] = 'Ecrire un résumé court et motivant expliquant le contenu et objectifs du cours';
$string['coursedeleted'] = 'Cours {$a} supprimé.';
$string['coursedeletefile'] = 'Fichier de suppression';
$string['coursedeletion'] = 'Destruction de cours';
$string['courseexists'] = 'Le cours [{$a->shortname}] {$a->fullname} existe déjà.';
$string['coursefoundas'] = 'Le cours d\'idnumber {{$a->idnumber}} existe : <ol><li>fullname = {$a->fullname} </li><li> shortname = {$a->shortname}</li></ol>';
$string['coursefullname'] = 'Nom long';
$string['coursemgtmanual'] = 'Gestion manuelle des cours';
$string['coursenodeleteadvice'] = 'La suppression de cours ne supprimera pas le cours {$a}. Cours inexistant.';
$string['coursenotfound'] = 'Le cours {$a} n\'existe pas dans moodle.';
$string['coursenotfound2'] = 'Le cours d\'idnumber [{$a->idnumber}] "{$a->description}" n\'existe pas dans moodle';
$string['coursereset'] = 'Réinitalisation massive des cours';
$string['coursescronconfig'] = 'Activer la synchronisation par cron des cours';
$string['coursesmgtfiles'] = 'Configuration des opérations sur les cours';
$string['coursesync'] = 'Synchronisation des cours';
$string['courseupdated'] = 'Cours {$a->shortname} mis à jour.';
$string['createpasswords'] = 'Créer les mots de passe';
$string['createtextreport'] = 'Souhaitez vous créer un rapport en format texte ?';
$string['creatingcohort'] = 'La cohorte {$a} sera créée.';
$string['creatingcoursefromarchive'] = 'Création du cours à partir de {$a}';
$string['criticaltime'] = 'Temps limite';
$string['csvseparator'] = 'Séparateur de champs CSV';
$string['day_fri'] = 'Vendredi';
$string['day_mon'] = 'Lundi';
$string['day_sat'] = 'Samedi';
$string['day_sun'] = 'Dimanche';
$string['day_thu'] = 'Jeudi';
$string['day_tue'] = 'Mardi';
$string['day_wed'] = 'Mercredi';
$string['deletecontrolfiles'] = 'Si activé, supprime les fichiers de controle après exécution';
$string['deletecoursesconfirmquestion'] = 'Etes-vous sur de vouloir détruire ces cours<br />pour l\'éternité à venir et à la face du monde ? pour toujours ?';
$string['deletefile'] = 'Utiliser un fichier de suppression mémorisé';
$string['deletefilebuilder'] = 'Création de fichiers de commande pour la suppression de cours';
$string['deletefileidentifier'] = 'Identifiant de cours pour suppression';
$string['deletefileinstructions'] = 'Choisissez un fichier contenant la liste des noms cours des cours à supprimer (un nom par ligne).';
$string['deletefromremote'] = 'Télécharger et exécuter un fichier de suppression';
$string['deletethisreport'] = 'Voulez-vous effacer ce rapport ?';
$string['description'] = '<center><a href="/enrol/sync/index.php">Gestionnaire complet de synchronisation</a></center>';
$string['disabled'] = 'Désactivé.';
$string['displayoldreport'] = 'Afficher un ancien rapport';
$string['emptycats'] = 'Catégories vides sous : {$a}';
$string['emptygroupsdeleted'] = 'Groupes vides supprimés';
$string['encoding'] = 'Encodage des fichiers source';
$string['endofprocess'] = ' - Fin d\'exécution';
$string['endofreport'] = 'Fin du rapport de traitement';
$string['enrol'] = 'Utilisateur {$a->myuser} inscrit dans le cours {$a->mycourse}';
$string['enrolcourseidentifier'] = 'Identifiant pour désigner les cours';
$string['enrolcronprocessing'] = 'Traitement des inscriptions';
$string['enroldefault'] = 'Traitement par défaut';
$string['enroldefaultcmd'] = 'Configuration par défaut pour la colonne "cmd"';
$string['enroldefaultcmd_desc'] = 'Définit la commande par défaut sur le rôle si la colonne "cmd" est absente';
$string['enroldefaultinfo'] = 'Configuration par defaut pour la colonne cmd';
$string['enrolemailcourseadmins'] = 'Notifier les admissions aux administrateurs du cours';
$string['enrolemailcourseadmins_desc'] = 'Si activé, envoie un résumé des admissions aux enseignants du cours';
$string['enrolfile'] = 'Fichier d\'inscriptions';
$string['enrolfilelocation'] = 'Fichier d\'inscriptions';
$string['enrolled'] = 'Utilisateur {$a->myuser} inscrit dans le cours {$a->mycourse}';
$string['enrollednot'] = 'Echec inscription {$a->myuser} dans le cours {$a->mycourse}';
$string['enrolmanualsync'] = 'Exécution manuelle de la synchronisation d\'inscriptions';
$string['enrolmgtmanual'] = 'Gestion manuelle des inscriptions';
$string['enrolsconfig'] = 'Configuration des opérations sur les inscriptions';
$string['enrolscronconfig'] = 'Activer la synchronisation par cron des inscriptions';
$string['enrolsync'] = 'Synchronisation des inscriptions';
$string['enroluseridentifier'] = 'Identifiant pour désigner les utilisateurs';
$string['enterfilename'] = 'Entrez le nom du fichier rapport à visualiser :';
$string['errorbackupfile'] = 'Une erreur s\'est produite en rapport à l\'archive de cours (Code erreur: {$a->error}).';
$string['errorbadcmd'] = 'Erreur ligne {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : erreur de valeur dans la colonne cmd.';
$string['errorbadcount'] = 'Erreur ligne {$a->i} : {$a->count} valeurs trouvées. {$a->expected} attendues.';
$string['errorcategorycontextdeletion'] = 'Erreur de suppression du contexte : catégorie {$a}';
$string['errorcategorycreate'] = 'Erreur ligne {$a->i} : Erreur pendant la création de la catégorie nommée {$a->catname}, un total de {$a->failed} categorie(s) ont échoué.';
$string['errorcategorydeletion'] = 'Erreur de suppression de la catégorie {$a}';
$string['errorcategoryparenterror'] = 'Erreur ligne {$a->i} : Le cours {$a->coursename} n\'a pu être créé car la (les) catégorie(s) sont manquantes.';
$string['errorcoursedeletion'] = 'Le cours d\'id : {$a} n\'a pu être supprimé complètement. Des éléments peuvent subsister.';
$string['errorcoursemisconfiguration'] = 'Erreur ligne {$a->i} : Le cours {$a->coursename} est mal configuré. Les enseignants ne peuvent y être enrolés.';
$string['errorcourseupdated'] = 'Erreur ligne {$a->i}: Erreur sur la mise à jour du cours {$a->shortname}.';
$string['errorcritical'] = 'Erreur ligne {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : erreur critique.';
$string['erroremptycommand'] = 'Erreur ligne {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : aucune valeur renseignée dans la colonne \'cmd\'';
$string['erroremptyrole'] = 'Erreur ligne {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : Tentative d\'ajout d\'un rôle vide';
$string['errorenrol'] = 'Erreur d\'inscription. {$a->myuser} dans le cours {$a->mycourse}';
$string['errorgcmdvalue'] = 'Erreur ligne {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : la valeur de gcmd n\'existe pas';
$string['errorgroupnotcreated'] = 'Erreur ligne {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : le groupe n\'a pas pu être créé.';
$string['errorinputconditions'] = 'Mauvaises conditions d\'entée dans la fonction de création de cours.';
$string['errorinvalidcolumnname'] = 'Erreur : nom de colonne "{$a}" invalide';
$string['errorinvalidfieldname'] = 'Erreur : nom de champ "{$a}" invalide';
$string['errorline'] = 'Erreur : Ligne';
$string['errornocourse'] = 'Erreur ligne {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : Le cours n\'existe pas';
$string['errornocourses'] = 'Erreur : Aucun cours traité dans ce CSV';
$string['errornomanualenrol'] = 'Aucun plugin d\'inscription manuel disponible. Désactivation de l\'inscription pour cet utilsiateur.';
$string['errornorole'] = 'Erreur ligne {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : précisez un identifiant de rôle pour un ajout ou un changement d\'assignation';
$string['errornoteacheraccountkey'] = 'Erreur ligne {$a->i} : Valeur invalide pour le champ teacher {$a->key} - d\'autres champs ont été spécifiés mais le champ {$a->key}_account est nul.';
$string['errornouser'] = 'Erreur ligne {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : L\'utilisateur n\'existe pas';
$string['errornullcourseidentifier'] = 'Erreur ligne {$a} : Identifiant de cours vide ou nul.';
$string['errornullcourseidentifier'] = 'Identifiant de cours nul ou invalide à la ligne {$a}.';
$string['errornullcsvheader'] = 'Erreur ; Les colonnes du fichier CSV doivent toutes être nommées';
$string['erroropeningfile'] = 'Erreur d\'ouverture de fichier';
$string['errorrequiredcolumn'] = 'Erreur : colonne requise : {$a}';
$string['errorrestoringtemplate'] = 'Erreur ligne {$a->i} : Erreur de restauration pour le cours {$a->coursename}';
$string['errorrestoringtemplatesql'] = 'Erreur ligne {$a->i} : Erreur SQL pour le gabarit {$a->template}. Le cours {$a->coursename} n\'a pas pu être créé.';
$string['errorrpcparams'] = 'Erreur de paramètres RPC : {$a}';
$string['errors'] = 'Erreurs';
$string['errorsectioncreate'] = 'Erreur ligne {$a->i} : Erreur pendant la création des sections du cours {$a->coursename}';
$string['errorsettingremoteaccess'] = 'Erreur de l\'ouverture de droits d\'accès réseau : {$a} ';
$string['errorteacherenrolincourse'] = 'Erreur ligne {$a->i} : Impossible d\'enroler les enseignants du cours {$a->coursename}';
$string['errorteacherrolemissing'] = 'Erreur ligne {$a->i} : Le rôle enseignant du cours  {$a->coursename} n\'a pas pu être déterminé';
$string['errortemplatenotfound'] = 'Erreur ligne {$a->i} : Le gabarit de cours {$a->template} n\'a pas pu être trouvé ou n\'a pas d\'archives. Le cours {$a->coursename} n\'a pas pu être créé.';
$string['errortoooldlock'] = 'Erreur : un ancien fichier locked.txt est présent';
$string['errorunassign'] = 'Erreur ligne {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : la désassignation du rôle {$a->myrole} à échoué.';
$string['errorunassignall'] = 'Erreur ligne {$a->i} : {$a->mycmd} {$a->myrole} {$a->myuser} {$a->mycourse} : la désassignation générale des rôles à échoué.';
$string['errorunenrol'] = 'Erreur de désincription. {$a->myuser} dans le cours {$a->mycourse}';
$string['erroruploadpicturescannotunzip'] = 'Erreur : Impossible de dezipper le fichier d\'avatars : {$a} (le fichier est peut être vide)';
$string['errorvalidationbadtype'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (ni un entier ni du texte).';
$string['errorvalidationbaduserid'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (pas d\'utilisateur avec l\'ID "{$a->value}").';
$string['errorvalidationcategorybadpath'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (chemin "{$a->path}" invalide - mauvais délimiteurs).';
$string['errorvalidationcategoryid'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (pas de catégorie d\'ID {$a->category}).';
$string['errorvalidationcategorylength'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (longueur de nom de catégorie "{$a->item}" &gt; 30).';
$string['errorvalidationcategorytype'] = 'Erreur line {$a->i} : Valeur du champ {$a->fieldname} invalide (chemin "{$a->value}" invalide - le nom de la catégorie à la posiiton {$a->pos} as shown est invalide).';
$string['errorvalidationcategoryunpathed'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (chemin vide).';
$string['errorvalidationempty'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (vide ou espaces).';
$string['errorvalidationintegerabove'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (&gt; {$a->max}).';
$string['errorvalidationintegerbeneath'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (&lt; {$a->min}).';
$string['errorvalidationintegercheck'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (n\'est pas entier).';
$string['errorvalidationmultipleresults'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (recherche ambigüe; résultat multiple à [{$a->ucount}] réponses).';
$string['errorvalidationsearchfails'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (la recherche n\'a pas de résultats).';
$string['errorvalidationsearchmisses'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (la recherche aboutit à un utilisateur inexistant ?!).';
$string['errorvalidationstringlength'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (longueur &gt; {$a->length}).';
$string['errorvalidationtimecheck'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (n\'est pas un temps valide).';
$string['errorvalidationvalueset'] = 'Erreur ligne {$a->i} : Valeur du champ {$a->fieldname} invalide (doit être dans l\'ensemble {$a->set}).';
$string['errornoenrolmethod'] = 'La méthode d\'inscription invoquée n\'est pas active dans ce cours';
$string['eventscleanup'] = 'Nettoyage des événéments générés (conseillé)';
$string['execstartsat'] = 'Exécution démarrée à {$a} ';
$string['cohortbindingbadcourseid'] = 'Le cours {$a} n\'a pas pu être trouvé. Abandon.';
$string['cohortbindingbadcohortid'] = 'La cohorte {$a} n\'a pas pu être trouvée. Abandon.';
$string['cohortbindingbadroleid'] = 'Le role explicite {$a} n\'a pas pu être trouvé. Abandon.';
$string['executecoursecronmanually'] = 'Exécuter toutes les opérations de cours';
$string['existcoursesfile'] = 'Fichier de test d\'existance';
$string['existfileidentifier'] = 'Identifiant d\'existance';
$string['failedfile'] = 'Fichier de reprise';
$string['filearchive'] = 'Archivage des fichiers de controle';
$string['filecabinet'] = 'Répertoire des rapports';
$string['filecleanup'] = 'Nettoyage des fichiers de controle';
$string['filegenerator'] = 'Générateur de fichiers de commande';
$string['filemanager'] = 'Gestion des fichiers de contrôle';
$string['filemanager2'] = 'Gestionnaire de fichiers';
$string['filenameformatcc'] = '<strong>Format du nom de rapport :</strong> CC_YYYY-MM-DD_hh-mm.txt';
$string['filenameformatuc'] = '<strong>Format du nom de rapport :</strong> UC_YYYY-MM-DD_hh-mm.txt';
$string['filenotfound'] = 'Le fichier {$a} n\'a pas été trouvé';
$string['filetoprocess'] = 'Fichier à exécuter';
$string['final_action'] = 'Post-traitements';
$string['flatfilefoundforenrols'] = 'Fichier d\'enrôlements trouvé :';
$string['forcecourseupdateconfig'] = 'Si activé, les cours existants auront leurs attributs mise à jour. Le contenu et les données de cours restent inchangées.';
$string['foundfile'] = 'Trouvé fichier : {$a}';
$string['foundfilestoprocess'] = 'Trouvé {$a} fichiers à traiter';
$string['generate'] = 'Générer';
$string['getfile'] = 'Obtenir le fichier';
$string['group_clean'] = 'Nettoyage des groupes';
$string['group_cleanex'] = 'Effacer les groupes vides après exécution';
$string['groupassigndeleted'] = 'Les assignations de groupe sont supprimées pour l\'utilisateur {$a->myuser} dans le cours {$a->mycourse}';
$string['groupcreated'] = 'Le groupe {$a->group} a été créé dans le cours {$a->mycourse}';
$string['groupnotaddederror'] = 'Erreur de création de groupe : {$a}';
$string['groupunknown'] = 'Le groupe {$a->group} n\'existe pas dans {$a->mycourse} et la commande ne permet pas la création.';
$string['hiddenroleadded'] = 'Rôle masqué ajouté dans le contexte :';
$string['hour'] = 'heure';
$string['ignoresubcats'] = 'Ignorer les sous-categories vides';
$string['importfile'] = 'Importer un nouveau fichier de test';
$string['invalidseparatordetected'] = 'Séparateur de champ innatendu dans les noms de colonne. Le format du fichier ne semble pas correspondre au régalge de l\'outil.';
$string['load'] = 'Charger';
$string['location'] = 'Emplacement';
$string['mail'] = 'Rapport de traitement';
$string['mailenrolreport'] = 'Rapport de l\'auto-enrollement : ';
$string['makedeletefile'] = 'Créer un fichier de suppression de cours';
$string['makefailedfile'] = 'Générer un fichier de reprise de défauts';
$string['makeresetfile'] = 'Créer un fichier de réinitialisation de cours';
$string['manualcleancategories'] = 'Nettoyer manuellement les catégories vides';
$string['manualcohortbindingrun'] = 'Accrocher les cohortes aux cours manuellement';
$string['manualcohortrun'] = 'Exécuter manuellement la synchronisation des cohortes';
$string['manualdeleterun'] = 'Exécuter manuellement une destruction de cours';
$string['manualenrolrun'] = 'Exécuter manuellement ce script à partir du fichier de commande';
$string['manualhandling'] = 'Gestion manuelle des opérations';
$string['manualmetasrun'] = 'Mettre en place manuellement les relations metacours';
$string['manualuploadrun'] = 'Exécuter manuellement une creation de cours';
$string['manualuserpicturesrun'] = 'Exécuter manuellement le rechargement d\'avatars';
$string['manualuserrun'] = 'Exécuter manuellement ce script à partir du fichier de commande';
$string['manualuserrun2'] = 'Exécuter manuellement ce script à partir d\'un fichier distant';
$string['metabindingfile'] = 'Fichier de liaison metacours';
$string['metabindingfileidentifier'] = 'Identifiant de cours pour les liaisons Metacours';
$string['metalinkcreated'] = 'Liaison Metacours pour {$e->for} à partir de {$e->from} créée';
$string['metalinkdisabled'] = 'Liaison Metacours pour {$e->for} à partir de {$e->from} désactivée';
$string['metalinkrevived'] = 'Liaison Metacours pour {$e->for} à partir de {$e->from} restaurée';
$string['minute'] = 'minute';
$string['missingidentifier'] = 'L\'identifiant {$a} requis par la configuration est manquant dans le fichier';
$string['ncategoriesdeleted'] = '{$a} catégories supprimées';
$string['noeventstoprocess'] = 'Pas d\'événements à la ligne {$a}';
$string['nofile'] = 'Aucun fichier disponible';
$string['nofileconfigured'] = 'Pas de fichier de données configuré pour cette opération';
$string['nofiletoprocess'] = 'Pas de fichier à traiter';
$string['nogradestoprocess'] = 'Pas de notes à la ligne {$a}';
$string['nogrouptoprocess'] = 'Pas de groupes';
$string['nologstoprocess'] = 'Pas de logs à la ligne {$a}';
$string['nonotestoprocess'] = 'Pas d\'annotations à la ligne {$a}';
$string['nonuniqueidentifierexception'] = 'Cette valeur d\'identifiant à la ligne {$a} désigne plusieurs cours. La réinitialisation est annulée pour ces cours.';
$string['nothingtodelete'] = 'Aucun élément à supprimer';
$string['optionheader'] = 'Options de synchronisation';
$string['parsingfile'] = 'Examen du fichier...';
$string['passwordnotification'] = 'Vos accès sur {$a}';
$string['pluginname'] = 'Synchronisation des cours et utilisateurs par fichiers CSV';
$string['predeletewarning'] = '<b><font color="red">ATTENTION :</font></b> La suppression des cours suivant va être effectuée :';
$string['primaryidentity'] = 'champ d\'identité primaire';
$string['process'] = 'Effectuer l\'opération';
$string['processerror'] = 'Erreur d\'exécution. La raison est : {$a}';
$string['processingfile'] = 'Examen du fichier : {$a}';
$string['processresult'] = 'Résultat d\'exécution';
$string['protectemails'] = 'Protéger les adresse de courriel';
$string['purge'] = 'Purger tous les rapports';
$string['registeringincohort'] = 'Inscription dans la cohorte {$a}';
$string['reinitialisation'] = 'Réinitialiser des cours';
$string['remoteenrolled'] = 'Utilisateur {$a->username} inscrit en tant que {$a->rolename} sur {$a->wwwroot} dans le cours {$a->coursename}';
$string['remoteserviceerror'] = 'Erreur du service distant';
$string['report'] = 'Rapport';
$string['resetfile'] = 'Fichier de reinitialisation';
$string['resetfilebuilder'] = 'Générateur de fichier CSV de réinitialisation';
$string['resetfileidentifier'] = 'Identifiant de cours pour la réinitialisation';
$string['resettingcourse'] = 'Réinitialisation du cours :';
$string['resettingcourses'] = 'Réinitialisation des cours';
$string['returntotools'] = 'Retour aux outils';
$string['roleadded'] = 'Role "{$a->rolename}" ajouté dans le contexte {$a->contextid}';
$string['rootcategory'] = '--- Catégorie racine ---';
$string['run'] = 'Déclenchement';
$string['runlocalfiles'] = 'Lancer tous les traitements';
$string['runtime'] = 'Heure d\'exécution';
$string['selecteditems'] = 'Cours sélectionnés pour la génération';
$string['selectencoding'] = 'Sélectionner l\'encodage des fichiers source';
$string['selectseparator'] = 'Vous pouvez choisir le séparateur de champs CSV. Ce séparateur est valide pour tous les fichiers de commande du synchroniseur.';
$string['sendpasswordtousers'] = 'Envoyer les mots de passe aux utilisateurs';
$string['shortnametodelete'] = 'Cours à supprimer';
$string['simulate'] = 'Simuler l\'opération';
$string['skippedline'] = 'Ligne ({$a}) ignorée (erreur de format colonne)';
$string['startcategory'] = 'Catégorie parente';
$string['startingcheck'] = 'Démarrage de la vérification d\'existence...';
$string['startingreset'] = 'Démarrage de la réinitialisation...';
$string['storedfile'] = 'Fichier de commande mémorisé : {$a}';
$string['storereport'] = 'Sauvegarder le rapport';
$string['sync:configure'] = 'Configurer les synchronisations';
$string['synccohorts'] = 'Gestionnaire des cohortes';
$string['syncconfig'] = 'Configuration de la synchronisation';
$string['synccourses'] = 'Gestionnaire de cours';
$string['syncenrol'] = 'Mise à jour des rôles et inscriptions';
$string['syncenrols'] = 'Gestionnaire d\'inscription';
$string['syncfiles'] = 'fichiers de synchronisation';
$string['syncforcecourseupdate'] = 'Forcer la mise à jour des cours';
$string['synchronization'] = 'Synchronisation de données';
$string['syncuserpictures'] = 'Gestionnaire d\'avatars';
$string['syncusers'] = 'Gestionnaire d\'utilisateurs';
$string['task_synccohorts'] = 'Synchronisaton des cohortes par CSV';
$string['task_synccourses'] = 'Synchronisation de cours par CSV';
$string['task_syncenrols'] = 'Synchronisation des inscriptions par CSV';
$string['task_syncuserpictures'] = 'Synchronisation des avatars';
$string['task_syncusers'] = 'Synchronisation des utilisateurs par CSV';
$string['taskrunmsg'] = 'Exécution du script sur {$a}<br/>.';
$string['taskrunmsgnofile'] = 'Pas de fichier défini<br/>.';
$string['testcourseexist'] = 'Tester l\'existence de cours';
$string['title'] = '<center><h1>Synchronisation des cours et utilisateurs : configuration</h1></center>';
$string['toolindex'] = 'Index des outils';
$string['toolname'] = 'Synchronisation des cours et utilisateurs par fichiers CSV';
$string['totaltime'] = 'Temps d\'exécution : ';
$string['unassign'] = 'Suppression du rôle {$a->myrole} de {$a->myuser} dans le cours {$a->mycourse}';
$string['unassignall'] = 'Suppression de tous les rôles de {$a->myuser} du cours {$a->mycourse}';
$string['unenrol'] = 'Utilisateur {$a->myuser} désincrit du cours {$a->mycourse}';
$string['unknownrole'] = 'Role inconnu à la ligne {$a->i}';
$string['unknownshortname'] = 'Nom court inconnu à la ligne {$a->i}';
$string['upload'] = 'Télécharger';
$string['uploadcourse'] = 'Mise à jour des cours';
$string['uploadcoursecreationfile'] = 'fichier de creation de cours';
$string['uploadpictures'] = 'Mise à jour des avatars';
$string['uploadusers2'] = 'Mise à jour des utilisateurs';
$string['uselocal'] = 'Utiliser le fichier local : {$a}';
$string['useraccountadded'] = 'Utilisateur ajouté : {$a} ';
$string['useraccountupdated'] = 'Utilisateur modifié : {$a} ';
$string['usercollision'] = 'Erreur : Collision d\'identifiant à la création pour {$a}';
$string['usercreatedremotely'] = 'Utilisateur {$a->username} créé sur {$a->wwwroot} ';
$string['usercronprocessing'] = 'Synchronisation automatique de utilisateurs';
$string['userexistsremotely'] = 'L\'utilisateur {$a} existe déjà sur le distant';
$string['usermgtmanual'] = 'Gestion manuelle des utilisateurs';
$string['usernotaddederror'] = 'Erreur de création de compte : {$a}';
$string['usernotrenamedexists'] = 'Erreur sur renommage de compte (cible existe) : {$a}';
$string['usernotrenamedmissing'] = 'Erreur sur renommage de compte (compte source manquant) : {$a}';
$string['usernotupdatederror'] = 'Erreur de modification de compte : {$a}';
$string['userpicturehash'] = 'Somme (MD5) de contrôle des avatars';
$string['userpicturehashfieldname'] = 'Clef avatar';
$string['userpicturemgt'] = 'Gestion des avatars d\'utilisateurs';
$string['userpicturesconfig'] = 'Configuration des opérations sur les avatars d\'utilisateurs';
$string['userpicturescronconfig'] = 'Activer le traitement de l\'import d\'avatars';
$string['userpicturescronprocessing'] = 'Traitement automatique des avatars d\'utilisateurs';
$string['userpicturesfilesprefix'] = 'Préfixe des fichiers d\'avatars';
$string['userpicturesfilesprefix_desc'] = 'Tous les fichiers présents correspondant au préfixe seront traités dans l\'ordre lexicographique.';
$string['userpicturesforcedeletion'] = 'Forcer la suppression des fichiers sources';
$string['userpicturesforcedeletion_desc'] = 'Supprime les archives sources même si l\'option globale de suppression des fichiers de commande est inactive';
$string['userpicturesmanualsync'] = 'Mise à jour manuelle des avatars';
$string['userpicturesmgtmanual'] = 'Gestion manuelle des avatars';
$string['userpicturesoverwrite'] = 'Remplacer les images existantes';
$string['userpicturesoverwrite_desc'] = 'Si activé, remplace les avatars avec les nouvelles versions';
$string['userpicturesuserfield'] = 'Champ de reconnaissance des utilisateur';
$string['userpicturesuserfield_desc'] = 'La valeur de ce champ doit correspondre au nom des fichiers image (avant extension).';
$string['userpicturesync'] = 'Synchronisation des avatars d\'utilisateurs';
$string['userrevived'] = 'Utilisateur supprimé réanimé : {$a}';
$string['usersconfig'] = 'Configuration des opérations sur les utilisateurs';
$string['userscronconfig'] = 'Activer la synchronisation par cron des utilisateurs';
$string['usersfile'] = 'Fichier des utilisateurs';
$string['usersupdated'] = 'Utilisateurs mis à jour ';
$string['usersync'] = 'Synchronisation des utilisateurs';
$string['userunknownremotely'] = 'L\'utilisateur {$a} n\'existe pas sur le distant';
$string['utilities'] = 'Utilitaires';

$string['coursesync_help'] = '
';

$string['userpicturesync_help'] = '
Si votre système de gestion stocke des avatars d\'utilisateurs (trombinoscope), ce service permet d\'organiser une synchronisation des images associées aux utilisateurs de Moodle. Il automatise la fonction Administration > Utilisateurs > Comptes > Déposer des utilisateurs de la version standard de Moodle.
Pour synchroniser des avatars d\'utilisateurs, vous devez constituer un fichier archive compressé (ZIP) avec les fichiers images. Vous pouvez choisir l\'un des champs d\'identification des utilisateurs qui sont proposés dans la configuration du service. Les noms d\'image doivent à ce moment être construits sur cette base :

<pre>&lt;%valeurid%&gt;.gif ou &lt;%valeurid%&gt;.jpg</pre>

Le fichier zip doit comporter un préfixe reconnaissable que vous pouvez configurer par le paramètre userpictures_fileprefix. L\'ordre d\'évaluation est l\'ordre alphabetique du nom de fichier (physique). Vous pouvez cadencer un examen successif de plusieurs mises à jour si par exemple vous nommez vos fichiers selon une séquence temporelle :

Exemple :

<pre>userpictures_20111201.zip
userpictures_20111202.zip
userpictures_20111203.zip
...
</pre>
';
$string['enrolsync_help'] = 'Cette fonction est un complément du systeme d\'enrollement par fichier plat. Il gère les groupes et permet de créer des rôles en attribution cachée.
Le fichier des enrolements présente un certain nombre d\'ordres ou opérations qui conduisent à la modification des assignations de rôle des utilisateurs dans les cours.
';

$string['syncconfig_help'] = '
<p>Ces paramètres déterminent les options d\'automatisation et de planification des opérations de synchronisation gérées par ce composant.</p>

<p><b>Choix des services</b></p>
<p>Chaque service de synchronnisaton peut être inclus ou non dans l\'automatisation.</p>

<p><b>Programmation de l\'automatisation</b></p>
<p>Elle détermine la fréquence et le moment de l\'exécution.</p>

<p><b>Post taitements</b></p>
<p>Les post-traitements s\'exécutent après l\'examen des fichiers de commande.</p>
';

$string['cleancategories_help'] = '
Vous allez supprimer toutes les catégories vides de Moodle. Cette commande est exécutée en
mode récursif et détruira toutes les "banches vides".
';

$string['boxdescription'] = 'Outil de gestion des synchronisations de cours, d\'utilisateurs et de groupe à l\'aide de fichiers txt et csv appelés par le cron.<br/><br/>
    Il suffit de préciser les chemins des quatre fichiers (à partir de la racine de "moodledata" :<br/>
    <ol>
        <li>Le fichier .txt pour la suppression de cours.
        </li>
        <li>Le fichier .csv pour l\'ajout de cours.
        </li>
        <li>Le fichier .csv pour l\'ajout ou la suppression d\'utilisateurs.
        </li>
        <li>Le fichier .csv pour l\'enrollement des apprenants et la gestion des groupes.
        </li></il>
        Il est egalement possible de déclencher ces scripts manuellement.
';

$string['coursecreateformat'] = 'Format de fichier de création de cours';
$string['coursecreateformat_help'] = '
Le fichier de réinitialisation de cours doit être un fichier texte CSV encodé ISO ou UTF-8 format selon la configuration locale de l\'outil de synchronisation.
La première ligne doit comporter les titres de champs dans un ordre quelconque.

<p>Champs obligatoires : <b>shortname, fullname</b>.

<li><i>shortname</i> : Doit être unique dans Moodle et ne doit donc pas correspondre à un cours existant.

<p>Champs optionnels : <b>category, sortorder, summary, format, idnumber, showgrades, newsitems, startdate, marker, maxbytes, legacyfiles, showreports, visible, visibleold, groupmode, groupmodeforce, defaultgroupingid, lang, theme, timecreated, timemodified, self, guest, template</b></p>

';

$string['coursedeleteformat'] = 'Format de fichier de suppression de cours';
$string['coursedeleteformat_help'] = '
Ce fichier est une simple liste textuelle (un item par ligne) des identifiants primaires de cours à détruire, tel que défini dans la configuration locale de l\'outil de synchronisation.
';

$string['coursecheckformat'] = 'Format de fichier de test d\'existence de cours';
$string['coursecheckformat_help'] = '
Ce fichier est une simple liste textuelle (un item par ligne) des identifiants primaires de cours à vérifier, tel que défini dans la configuration locale de l\'outil de synchronisation.
';

$string['coursereinitializeformat'] = 'Format de fichier de réinitialisation de cours';
$string['coursereinitializeformat_help'] = '
Course reinitialisation file must be in ISO or UTF-8 format depending on Sync Tool settings.
The first line must hold column titles in any order.
The first field must identify a course, depending on the selected course primary identifier in configuration :

<li><i>id</i>: Using the numeric internal DN identifier of the course.</li>
<li><i>shortname</i>: Using the course shortname</li>
<li><i>idnumber</i>: Using the IDNumber of the course</li>

<p>Mandatory fields : <b>events, logs, notes, completion, grades, roles, local_roles, groups, groupings, blog_associations, comments, modules</b>

<p>Usual value is \'yes\' or \'no\' unless :</p>
<li><i>roles</i>: a list of role shortnames, separed by spaces.</li>
<li><i>local_roles</i>: \'all\' (roles and overrides), \'roles\' or \'overrides\'.</li>
<li><i>grades</i>: \'all\' (items and grades), \'items\' or \'grades\'.</li>
<li><i>groups</i>: \'all\' (groups and members), \'groups\' or \'members\'.</li>
<li><i>groupings</i>: \'all\' (groups and members), \'groups\' or \'members\'.</li>
<li><i>modules</i>: \'all\' (reset all modules), or a list of module shortnames to reset.</li>

<p>Additional fields can be added for more specific control for modules:
<b>forum_all, forum_subscriptions, glossary_all, chat, data, slots (scheduler), apointments, assignment_submissions, assign_submissions, survey_answers, lesson, choice, scorm, quiz_attempts</b></p>

';

$string['userformat'] = 'Format du fichier de synchronisation des utilisateurs';
$string['userformat_help'] = '
Le fichier de synchronisation des utilisateurs doit être un fichier text encodé en ISO ou UTF-8
selon la configuration générale de l\'outil de synchronisation.
La première ligne doit comporter les noms de champs au format moodle dans un ordre quelconque.
Le premier champ doit identifier un utilisateur par son \'username\'.

<p>Champs obligatoires : <b>username, firstname, lastname, email</b></p>

<p>Champs facultatifs : <b>idnumber, email, auth, icq, phone1, phone2, address, url, description, mailformat, maildisplay, htmleditor, autosubscribe, cohort, cohortid, course1, group1, type1, role1, enrol1, start1, end1, wwwroot1, password, oldusername</b></p>

<p>Les "patterns" sont des groupes de champs qui doivent être utilisés ensemble, et suivis d\'un index numérique (nomchamp<n>).</p>

<p>Pattern d\'inscription : <b>course, group, type, role, enrol, start, end, wwwroot</b>. Ce pattern permet d\'inscrire immédiatement les utilisateurs créés ou modifiés à des cours. Vous pouvez utiliser plusieurs fois ce motif avec des indexes suivis (1, 2, 3...) etc. On peut ne pas utiliser sur une ligne l\'un des patterns mentionnés en laissant les champs vides.</p>

<p>Il est possible d\'utiliser quelques formes supplémentaires pour ajouter des valeurs aux champs customisés de profil. La forme générale de ces champs est <i>user_profile_xxxxx</i></p>
';

$string['enrolformat'] = 'Format de fichier d\'inscriptions';
$string['enrolformat_help'] = '
Le fichier d\'inscriptions est un fichier texte CSV encodé en UTF-8 ou ISO selon la configuration de l\'outil et automatise les entrées/sorties d\'inscriptions dans Moodle.

<p>Champs obligatoires : <b>rolename, uid, cid</b></p>

<li><i>rolename</i> : Le nom court du role (student, teacher, etc.)</li>
<li><i>uid</i> : la valeur d\'identifiant de l\'utilisateur, selon la configuration choisie (id, idnumber, username ou email).</li>
<li><i>cid</i> : la valeur d\'identifiant du cours, selon la configuration choisie (id, shortname ou idnumber).</li>

<p>Champs facultatifs : <b>hidden, starttime, endtime, enrol, cmd, g1 à g9</b></p>

<li><i>cmd</i> : implicitement \'add\', mais peut valoir \'del\' pour désincription. \'shift\' supprimera auparavent tous les anciens rôles de l\'utilisateur concerné dans le cours.</li>
<li><i>hidden</i> :</li>
<li><i>starttime, endtime</i> : Un temps linux en secondes.</li>
<li><i>enrol</i> : la méthode d\'inscription (manual, mnet, cohort, etc...). Si cette colonne n\'existe pas, alors l\'opération ne fera qu\'ajouter des "autres rôles", sans inscription.</li>
<li><i>gcmd</i> : \'gadd\' ou \'gaddcreate\', \'greplace\' ou \'greplacecreate\', mais peut être \'gdel\' pour une suppression de l\'appartenance au groupe.</li>
<li><i>g1 to g9</i> : up to 9 goupnames the enrolled user will be member of. The group is created if missing and using a \'gaddcreate\' or a \'greplacecreate\'.</li>
';

$string['cohortformat'] = 'Format de fichier d\'alimentation de cohortes';
$string['cohortformat_help'] = '
Le fichier de création de cohortes est un fichier texte ISO ou UTF-8 suivant la configuration locale.
The first line must hold column titles in any order.

<p>Mandatory fields: <b>cohortid, userid</b></p>

<li><i>cohortid</i> : Un identifiant de cohorte, selon la configuration de l\'outil de synchronisation. Peut être l\'id, le nom, ou l\'idnumber.</li>
<li><i>userid</i> : Un identifiant d\'utilisateur selon la configuration de l\'outil de synchonisation. Peut être l\'id, l\'email, le username, ou l\'idnumber.</li>

<p>Optional fields: <b>cdescription, cidnumber</b></p>

<li><i>cdescription</i> : Si la cohorte doit être créée, une description textuelle.</li>
<li><i>cidnumber</i> : Si la cohorte doit être créée, l\'idnumber. Dans ce cas, l\'identifiant devra être choisi comme \'name\'.</li>
';

$string['userpicturesformat'] = 'Format de transfert d\'images d\'avatars';
$string['userpicturesformat_help'] = '
Le fichier des avatars utilisateurs est une archive ZIP sans sous-répertoires avec la liste d\'images png, jpg ou gif des avatars utilisateurs, nommés par l\'identifiant primaire
de l\'utilisateur désigné par la configuraton de l\'outil de synchronisation.
';

$string['passwordnotification_tpl'] = '
Un mot de passe vous a été attribué : {$a}
';

$string['allowrename_help'] = 'Si actif, les identifiants de connexion peuvent être changés. Une colonne "oldusername" doit être rajoutée pour fournir la correspondance avec l\'ancien identifiant.';

$string['protectemails_help'] = 'Si actif, les adresses de courriel exprimées des utilisateurs resteront inchangées. Les adresses vides seront complétées par les données du fichier.';

$string['createpasswords_help'] = 'Si actif et que la colonne "password" n\'est pas fournie, les mots de passe seront générés et envoyés par Moodle.';

$string['sendpasswordtousers_help'] = 'Si actif et que les mots de passes sont fournis par le fichier, ils seront notifiés aux utilisateur sur l\'adresse de courriel fournie.';