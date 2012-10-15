<?php
OC::$CLASSPATH['OC_Contacts_App'] = 'apps/contacts/lib/app.php';
OC::$CLASSPATH['OC_Contacts_Addressbook'] = 'apps/contacts/lib/addressbook.php';
OC::$CLASSPATH['OC_Contacts_VCard'] = 'apps/contacts/lib/vcard.php';
OC::$CLASSPATH['OC_Contacts_Hooks'] = 'apps/contacts/lib/hooks.php';
OC::$CLASSPATH['OC_Share_Backend_Contact'] = 'apps/contacts/lib/share/contact.php';
OC::$CLASSPATH['OC_Share_Backend_Addressbook'] = 'apps/contacts/lib/share/addressbook.php';
OC::$CLASSPATH['OC_Connector_Sabre_CardDAV'] = 'apps/contacts/lib/sabre/backend.php';
OC::$CLASSPATH['OC_Connector_Sabre_CardDAV_AddressBookRoot'] = 'apps/contacts/lib/sabre/addressbookroot.php';
OC::$CLASSPATH['OC_Connector_Sabre_CardDAV_UserAddressBooks'] = 'apps/contacts/lib/sabre/useraddressbooks.php';
OC::$CLASSPATH['OC_Connector_Sabre_CardDAV_AddressBook'] = 'apps/contacts/lib/sabre/addressbook.php';
OC::$CLASSPATH['OC_Connector_Sabre_CardDAV_Card'] = 'apps/contacts/lib/sabre/card.php';
OC::$CLASSPATH['OC_Connector_Sabre_CardDAV_VCFExportPlugin'] = 'apps/contacts/lib/sabre/vcfexportplugin.php';
OC::$CLASSPATH['OC_Search_Provider_Contacts'] = 'apps/contacts/lib/search.php';
OCP\Util::connectHook('OC_User', 'post_createUser', 'OC_Contacts_Hooks', 'createUser');
OCP\Util::connectHook('OC_User', 'post_deleteUser', 'OC_Contacts_Hooks', 'deleteUser');
OCP\Util::connectHook('OC_Calendar', 'getEvents', 'OC_Contacts_Hooks', 'getBirthdayEvents');
OCP\Util::connectHook('OC_Calendar', 'getSources', 'OC_Contacts_Hooks', 'getCalenderSources');

OCP\App::addNavigationEntry( array(
  'id' => 'contacts_index',
  'order' => 10,
  'href' => OCP\Util::linkTo( 'contacts', 'index.php' ),
  'icon' => OCP\Util::imagePath( 'settings', 'users.svg' ),
  'name' => OC_L10N::get('contacts')->t('Contacts') ));

OCP\Util::addscript('contacts', 'loader');
OC_Search::registerProvider('OC_Search_Provider_Contacts');
OCP\Share::registerBackend('contact', 'OC_Share_Backend_Contact');
OCP\Share::registerBackend('addressbook', 'OC_Share_Backend_Addressbook', 'contact');
