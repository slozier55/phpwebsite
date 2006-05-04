<?php
  /**
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */

chdir('../');
// Uncomment this line if having problems installing in Windows
//ini_set('include_path', '.;.\\lib\\pear\\');
require_once 'core/class/Init.php';

if (!is_file('lib/pear/DB.php')) {
    echo _('Unable to locate your pear library files.');
    echo '<br />';
    echo _('Untar pear.tgz in your phpwebsite installation directory.');
    echo '<br />';
    echo '<pre>tar zxf pear.tgz</pre>';
    exit();
}

if (isset($_REQUEST['step']) && $_REQUEST['step'] > 1) {
    require_once './config/core/config.php';
 }
 else {
     require_once './setup/preconfig.php';
 }

require_once './inc/Functions.php';
require_once './core/class/Init.php';
include_once './setup/config.php';
require_once './setup/class/Setup.php';

PHPWS_Core::initCoreClass('Form.php');
PHPWS_Core::initCoreClass('Text.php');
PHPWS_Core::initCoreClass('Template.php');
PHPWS_Core::initModClass('boost', 'Boost.php');
PHPWS_Core::initModClass('users', 'Current_User.php');

session_start();

$content = array();
$setup = & new Setup;
$title = 'phpWebSite 1.0.0 - ';

if (!$setup->checkSession($content) || !isset($_REQUEST['step'])) {
    $step = 0;
 } else {
    $step = $_REQUEST['step'];
 }

if (!$setup->checkDirectories($content)){
    $title .= _('Directory Permissions');
    exit(Setup::show($content, $title));
 }

switch ($step){
 case '0':
     $title .=  'Beta Setup';
     $setup->welcome($content);
     break;

 case '1':
     $title .= _('Create Config File');
     $setup->createConfig($content);
     break;

 case '1a':
     $title .= _('Create Database');
     $setup->createDatabase($content);
     break;

 case '2':
     $title .= _('Create Core');
     $result = $setup->createCore($content);
     break;

 case '3':
     $title .= _('Install Modules');
     $result = $setup->installModules($content);
     if ($result) {
         $setup->finish($content);
     }
     break;
 }

echo Setup::show($content, $title);
?>