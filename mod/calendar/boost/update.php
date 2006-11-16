<?php

  /**
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */

function calendar_update(&$content, $version)
{

    switch ($version) {
    case version_compare($version, '1.1.0', '<'):
        $files[] = 'templates/style.css';
        $files[] = 'templates/admin/settings.tpl';
        $files[] = 'templates/admin/forms/edit_schedule.tpl';
        if (PHPWS_Boost::updateFiles($files, 'calendar')) {
            $content[] = 'Template files updated.';
        } else {
            $content[] = 'Failed to copy template files.';
        }
        $content[] = 'New - event displays as Busy to the public if set as such.';
        $content[] = 'New - Settings tab returns with a few basic settings.';

    case version_compare($version, '1.2.0', '<'):
        $files = array('templates/admin/forms/settings.tpl');
        if (PHPWS_Boost::updateFiles($files, 'calendar')) {
            $content[] = 'Template files updated.';
        } else {
            $content[] = 'Failed to copy template files.';
        }
        
        $content[] = '- Opened up private calendar key posting to allow permission settings.';
        $content[] = '- Added admin option to change the default calendar view.';
        $content[] = '- Month link on mini calendar now opens the default view.';
        $content[] = '- Public calendars that are restricted are now properly hidden.';

    case version_compare($version, '1.2.1', '<'):
        $files = array();
        $files[] = 'templates/admin/forms/setting.tpl';
        if (PHPWS_Boost::updateFiles($files, 'calendar')) {
            $content[] = 'Template files updated.';
        } else {
            $content[] = 'Failed to copy template files.';
        }

        $content[] = '<pre>
+ Updated file - templates/admin/forms/setting.tpl
+ Fixed bug #1589525 - Calendar days not linked to correct day view.
+ Fixed bug #1589528 - Added option to show mini calendar on all
  pages, front only, or none to settings tab.
+ Added language file.
+ Updated files templates/admin/forms/settings.tpl
+ Opened up private calendar key posting to allow permission settings.
+ Added admin option to change the default calendar view
+ Month link on mini calendar now opens the default view.
+ Public calendars that are restricted are now properly hidden.
</pre>';
    }

    return true;
}

?>