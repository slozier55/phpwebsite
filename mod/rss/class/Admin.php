<?php

  /**
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */

class RSS_Admin {

    function main()
    {
        $message = RSS_Admin::getMessage();
        PHPWS_Core::initModClass('rss', 'Feed.php');
        PHPWS_Core::initModClass('rss', 'Channel.php');

        if (!Current_User::allow('rss')) {
            Current_User::disallow();
        }

        $panel = & RSS_Admin::adminPanel();

        if (isset($_REQUEST['command'])) {
            $command = $_REQUEST['command'];
        } elseif (isset($_REQUEST['tab'])) {
            $command = $_REQUEST['tab'];
        } else {
            $command = $panel->getCurrentTab();
        }

        if (isset($_REQUEST['channel_id'])) {
            $channel = & new RSS_Channel($_REQUEST['channel_id']);
        } else {
            $channel = & new RSS_Channel;
        }

        if (isset($_REQUEST['feed_id'])) {
            $feed = & new RSS_Feed($_REQUEST['feed_id']);
        } else {
            $feed = & new RSS_Feed;
        }


        switch ($command) {
        case 'channels':
            $tpl = RSS_Admin::channels();
            break;

        case 'save_feed':
            $result = $feed->post();
            if (is_array($result)) {
                $tpl = RSS_Admin::editFeed($feed);
                $tpl['MESSAGE'] = implode('<br />', $result);
                Layout::nakedDisplay(PHPWS_Template::process($tpl, 'rss', 'main.tpl'));
                exit();
            } else {
                $feed->save();
                javascript('close_refresh');
            }
            break;

        case 'edit_channel':
            $tpl = RSS_Admin::editChannel($channel);
            break;

        case 'post_channel':
            $result = $channel->post();
            if (is_array($result)) {
                $message = implode('<br />', $result);
                $tpl = RSS_Admin::editChannel($channel);
            } else {
                $result = $channel->save();
                if (PEAR::isError($result)) {
                    RSS_Admin::sendMessage(_('An error occurred when saving your channel.'), 'channels');
                } else {
                    RSS_Admin::sendMessage(_('Channel saved.'), 'channels');
                }
            }
            break;

        case 'reset_feed':
            $feed->reset();
        case 'import':
            $tpl = RSS_Admin::import();
            break;

        case 'turn_on_display':
            $feed->display = 1;
            $feed->save();
            $tpl = RSS_Admin::import();
            break;

        case 'turn_off_display':
            $feed->display = 0;
            $feed->save();
            $tpl = RSS_Admin::import();
            break;

        case 'add_feed':
            $tpl = RSS_Admin::editFeed($feed);
            Layout::nakedDisplay(PHPWS_Template::process($tpl, 'rss', 'main.tpl'));
            exit();
            break;

        case 'edit_feed':
            $tpl = RSS_Admin::editFeed($feed);
            Layout::nakedDisplay(PHPWS_Template::process($tpl, 'rss', 'main.tpl'));
            exit();
            break;

        case 'delete_feed':
            $feed->delete();
            $tpl = RSS_Admin::import();
            break;

        default:
            PHPWS_Core::errorPage('404');
            break;
        }

        $tpl['MESSAGE'] = $message;

        $content = PHPWS_Template::process($tpl, 'rss', 'main.tpl');

        $panel->setContent($content);
        $content = $panel->display();
        Layout::add(PHPWS_ControlPanel::display($content));
    }


    function sendMessage($message, $command)
    {
        $_SESSION['RSS_Message'] = $message;

        PHPWS_Core::reroute(sprintf('index.php?module=rss&command=%s&authkey=%s',
                                    $command, Current_User::getAuthKey()));

    }

    function getMessage()
    {
        if (!isset($_SESSION['RSS_Message'])) {
            return NULL;
        }

        $message = $_SESSION['RSS_Message'];
        unset($_SESSION['RSS_Message']);
        return $message;
    }

    function &adminPanel()
    {
        $opt['link'] = 'index.php?module=rss';

        $opt['title'] = _('Channels'); 
        $tab['channels'] = $opt;

        $opt['title'] = _('Import');
        $tab['import'] = $opt;

        $panel = & new PHPWS_Panel('rss_admin');
        $panel->quickSetTabs($tab);
        return $panel;
    }

    function editChannel(&$channel)
    {
        $form = & new PHPWS_Form;
        $form->addHidden('module', 'rss');
        $form->addHidden('command', 'post_channel');
        $form->addSubmit(_('Save Channel'));

        if ($channel->id) {
            $form->addHidden('channel_id', $channel->id);
        }

        $form->addText('title', $channel->title);
        $form->setLabel('title', _('Title'));

        $form->addTextArea('description', $channel->description);
        $form->setLabel('description', _('Description'));

        $formtpl = $form->getTemplate();
        
        $tpl['CONTENT'] = PHPWS_Template::processTemplate($formtpl, 'rss', 'channel_form.tpl');

        $tpl['TITLE'] = _('Edit channel');

        return $tpl;

    }

    function channels()
    {
        PHPWS_Core::initModClass('rss', 'Channel.php');
        $final_tpl['TITLE'] = _('Administrate RSS Feeds');

        $db = & new PHPWS_DB('rss_channel');
        $db->addOrder('title');
        $channels = $db->getObjects('RSS_Channel');
        
        if (empty($channels)) {
            $final_tpl['CONTENT'] = _('No channels have been registered.');
            return $final_tpl;
        } elseif (PEAR::isError($channels)) {
            PHPWS_Error::log($channels);
            $final_tpl['CONTENT'] = _('An error occurred when trying to access your RSS channels.');
            return $final_tpl;
        }

        foreach ($channels as $oChannel) {
            $row['TITLE'] = $oChannel->title;
            $row['ACTION'] = implode(' | ', $oChannel->getActionLinks());
            if ($oChannel->active) {
                $row['ACTIVE'] = _('Yes');
            } else {
                $row['ACTIVE'] = _('No');
            }

            $tpl['channels'][] = $row;
        }

        $tpl['TITLE_LABEL']  = _('Title');
        $tpl['ACTIVE_LABEL'] = _('Active');
        $tpl['ACTION_LABEL'] = _('Action');
        $tpl['LIMIT_LABEL']  = _('Limit');


        $final_tpl['CONTENT'] = PHPWS_Template::process($tpl, 'rss', 'channel_list.tpl');

        return $final_tpl;
    }

    function editFeed(&$feed)
    {
        $form = & new PHPWS_Form;
        if ($feed->id) {
            $form->addHidden('feed_id', $feed->id);
        }
        $form->addHidden('module', 'rss');
        $form->addHidden('command', 'save_feed');

        $form->addText('address', $feed->address);
        $form->setLabel('address', _('Address'));
        $form->setSize('address', '40');

        $form->addText('title', $feed->title);
        $form->setLabel('title', _('Title'));
        $form->setSize('title', '40');

        $form->addSubmit('submit', _('Save'));
        
        $form->addButton('cancel', _('Cancel'));
        $form->setExtra('cancel', 'onclick="window.close()"');

        $form->addText('item_limit', $feed->item_limit);
        $form->setSize('item_limit', 2);
        $form->setLabel('item_limit', _('Item limit'));

        $form->addText('refresh_time', $feed->refresh_time);
        $form->setSize('refresh_time', 5);
        $form->setLabel('refresh_time', _('Refresh time'));

        $template = $form->getTemplate();
        
        $template['TITLE_WARNING'] = _('Feed title will be used if left empty');
        $template['REFRESH_WARNING'] = _('In seconds');

        $content = PHPWS_Template::process($template, 'rss', 'add_feed.tpl');


        $tpl['TITLE'] = _('Add Feed');
        $tpl['CONTENT'] = $content;
        return $tpl;
    }

    function import()
    {
        PHPWS_Core::initCoreClass('DBPager.php');
        PHPWS_Core::initModClass('rss', 'Feed.php');
        $content = NULL;
        
        $vars['address'] = 'index.php?module=rss&command=add_feed';
        $vars['label'] = _('Add feed');
        $vars['height'] = '280';
        $template['ADD_LINK'] = javascript('open_window', $vars);

        $template['TITLE_LABEL']   = _('Title');
        $template['ADDRESS_LABEL'] = _('Address');
        $template['DISPLAY_LABEL'] = _('Display?');
        $template['ACTION_LABEL']  = _('Action');
        $template['REFRESH_TIME_LABEL'] = _('Refresh feed');

        $pager = & new DBPager('rss_feeds', 'RSS_Feed');
        $pager->setModule('rss');
        $pager->setTemplate('admin_feeds.tpl');
        $pager->addPageTags($template);
        $pager->addRowTags('pagerTags');
        $content = $pager->get();

        $tpl['TITLE'] = _('Import RSS Feeds');
        $tpl['CONTENT'] = $content;
        return $tpl;
    }
}

?>