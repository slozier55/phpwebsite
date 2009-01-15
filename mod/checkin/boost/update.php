<?php
/**
 * @version $Id$
 * @author Matthew McNaney <mcnaney at gmail dot com>
 */

function checkin_update(&$content, $current_version) {
    switch (1) {
    case version_compare($current_version, '1.0.1', '<'):
        $content[] = '<pre>';
        $db = new PHPWS_DB('checkin_staff');
        
        if (PHPWS_Error::logIfError($db->addTableColumn('view_order', 'smallint not null default 0'))) {
            $content[] = 'Unable to create checkin_staff.view_order column.</pre>';
            return false;
        } else {
            $content[] = 'Created checkin_staff.view_order column.';
        }

        $db->addColumn('id');
        $staff_list = $db->select('col');

        if (!empty($staff_list)) {
            $count = 1;
            foreach ($staff_list as $staff_id) {
                $db->reset();
                $db->addWhere('id', $staff_id);
                $db->addValue('view_order', $count);
                PHPWS_Error::logIfError($db->update());
                $count++;
            }
        }

        checkinUpdateFiles(array('templates/visitors.tpl',
                                 'templates/waiting.tpl',
                                 'templates/queue.tpl'), $content);

        $content[] = '1.0.1 changes
------------------
+ Fixed bug with pulling current staff member
+ Added refresh link to waiting and assignment page
+ Fixed report
</pre>';
    }
    return true;
}

function checkinUpdateFiles($files, &$content)
{
    if (PHPWS_Boost::updateFiles($files, 'checkin')) {
        $content[] = '--- Updated the following files:';
    } else {
        $content[] = '--- Unable to update the following files:';
    }
    $content[] = "    " . implode("\n    ", $files);
}

?>