<?php

PHPWS_Core::initModClass('rss', 'XMLParser.php');

PHPWS_Core::requireConfig('rss');

class RSS_Feed {
    var $id           = 0;
    var $title        = NULL;
    var $address      = NULL;
    var $display      = 1;
    var $item_limit   = RSS_FEED_LIMIT;
    var $refresh_time = RSS_FEED_REFRESH;
    var $_error       = NULL;
    var $_parser      = NULL;
    var $mapped       = NULL;


    function RSS_Feed($id=0)
    {
        $this->id = $id;

        if (empty($this->id)) {
            return;
        }

        $this->init();
    }

    function init()
    {
        if (empty($this->id)) {
            return FALSE;
        }
        $db = & new PHPWS_DB('rss_feeds');
        return $db->loadObject($this);
    }

    function setAddress($address)
    {
        $this->address = trim($address);
    }

    function setTitle($title)
    {
        $this->title = trim(strip_tags($title));
    }

    function loadTitle()
    {
        $this->title = $this->mapped['CHANNEL']['TITLE'];
    }

    function pagerTags()
    {
        $vars['command'] = 'reset_feed';
        $vars['feed_id'] = $this->id;

        $links[] = PHPWS_Text::secureLink(_('Reset'), 'rss', $vars);

        $jsvars['address'] = sprintf('index.php?module=rss&command=edit_feed&feed_id=%s&authkey=%s',
                                   $this->id, Current_User::getAuthKey());
        $jsvars['label'] = _('Edit');
        $jsvars['height'] = '280';
        $links[] = javascript('open_window', $jsvars);

        $js['QUESTION'] = _('Are you sure you want to delete this RSS feed?');
        $js['ADDRESS']  = sprintf('index.php?module=rss&command=delete_feed&feed_id=%s&authkey=%s',
                                  $this->id, Current_User::getAuthKey());
        $js['LINK']     = _('Delete');
        $links[] = javascript('confirm', $js);

        $tpl['ACTION'] = implode(' | ', $links);

        if ($this->display) {
            $vars['command'] = 'turn_off_display';
            $tpl['DISPLAY'] = PHPWS_Text::secureLink(_('Yes'), 'rss', $vars);
        } else {
            $vars['command'] = 'turn_on_display';
            $tpl['DISPLAY'] = PHPWS_Text::secureLink(_('No'), 'rss', $vars);
        }

        $hours   = floor($this->refresh_time / 3600);

        $remaining = $this->refresh_time - ($hours * 3600);

        $minutes = floor( $remaining / 60);

        $seconds = $remaining - $minutes * 60;

        $time = NULL;
        
        if ($seconds) {
            $time = sprintf(_('%d seconds'), $seconds);
        }

        if ($minutes) {
            if (isset($time)) {
                $time = sprintf(_('%d minutes, '), $minutes) . $time;
            } else {
                $time = sprintf(_('%d minutes'), $minutes) . $time;
            }
        }

        if ($hours) {
            if (isset($time)) {
                $time = sprintf(_('%d hours, '), $hours) . $time;
            } else {
                $time = sprintf(_('%d hours'), $hours) . $time;
            }
        }

        $refresh_time = sprintf(_('Every %s'), $time);


        $tpl['REFRESH_TIME'] = $refresh_time;

        return $tpl;
    }

    function loadParser()
    {
        if (empty($this->address)) {
            return FALSE;
        }

        $cache_key = $this->address;
        $data = PHPWS_Cache::get($cache_key);
        if (!empty($data)) {
            $this->mapped = unserialize($data);
            return TRUE;
        } else {
            if (isset($this->_parser) && empty($this->_parser->error)) {
                return TRUE;
            }
            
            $this->_parser = & new XMLParser($this->address);
            if ($this->_parser->error) {
                PHPWS_Error::log($this->_parser->error);
                return FALSE;
            }
            
            $this->mapData();
            PHPWS_Cache::save($cache_key, serialize($this->mapped), $this->refresh_time);
        }
        return TRUE;
    }

    /**
     * Resets the cache on the RSS feed
     */
    function reset()
    {
        $cache_key = $this->address;
        PHPWS_Cache::remove($cache_key);
    }

    function post()
    {
        if (!empty($_POST['title'])) {
            $this->setTitle($_POST['title']);
        } else {
            $this->title = NULL;
        }

        if (!isset($_POST['address'])) {
            $error[] = _('You must enter an address.');
        }

        $this->setAddress($_POST['address']);

        if (!$this->loadParser()) {
            $error[] = _('Invalid feed address.');
        }

        $item_limit = (int)$_POST['item_limit'];

        if (empty($item_limit)) {
            $this->item_limit = RSS_FEED_LIMIT;
        } elseif ($item_limit > RSS_MAX_FEED) {
            $error[] = sprintf(_('You may not pull more than %s feeds.'), RSS_MAX_FEED);
            $this->item_limit = RSS_FEED_LIMIT;
        } else {
            $this->item_limit = $item_limit;
        }
        

        $refresh_time = (int)$_POST['refresh_time'];
        
        if ($refresh_time < 60) {
            $error[] = _('Refresh time is too low. It must be over 60 seconds.');
            $this->refresh_time = RSS_FEED_REFRESH;
        } elseif ($refresh_time > 2592000) {
            $error[] = _('You should refresh more often than every month.');
            $this->refresh_time = RSS_FEED_REFRESH;
        } else {
            $this->refresh_time = &$refresh_time;
        }

        if (isset($error)) {
            return $error;
        } else {
            return TRUE;
        }
    }

    function save()
    {
        if (empty($this->title)) {
            $this->loadTitle();
        }

        $db = & new PHPWS_DB('rss_feeds');
        return $db->saveObject($this);
    }

    function delete()
    {
        $db = & new PHPWS_DB('rss_feeds');
        $db->addWhere('id', $this->id);
        return $db->delete();
    }

    function view()
    {
        if (!$this->loadParser()) {
            $tpl['MESSAGE'] = _('Sorry, unable to grab feed.');
        } else {
            if (isset($this->mapped['ITEMS'])) {
                $count = 0;
                foreach ($this->mapped['ITEMS'] as $item_data) {
                    if ($count >= $this->item_limit) {
                        break;
                    }
                    $tpl['item_list'][] = $item_data;
                    $count++;
                }
            } else {
                $tpl['MESSAGE'] = _('Unable to list feed.');
            }
        }
        $tpl['FEED_LINK']  = &$this->mapped['CHANNEL']['LINK'];

        if (isset($this->mapped['IMAGE'])) {
            $image = & $this->mapped['IMAGE'];

            if (isset($image['LINK'])) {
                $tpl['IMAGE'] = sprintf('<a href="%s"><img src="%s" title="%s" border="0" /></a>',
                                        $image['LINK'], $image['URL'], $image['TITLE']);
            } else {
                $tpl['IMAGE'] = sprintf('<img src="%s" title="%s" border="0" />',
                                        $image['URL'], $image['TITLE']);
            }

        } else {
            $tpl['FEED_TITLE'] = &$this->title;
        }
                                         
        $content = PHPWS_Template::process($tpl, 'rss', 'view_rss.tpl');

        return $content;
    }


    function pullChannel($data, $version)
    {
        foreach ($data as $info) {
            extract($info);

            switch ($name) {
            case 'ITEM':
                $this->addItem($info['child']);
                break;

            case 'ITEMS':
                if ($version == '1.0') {
                    $items = &$child[0]['child'];
                    foreach ($items as $item) {
                        list(,$resource) = each($item['attributes']);
                        $this->mapped['CHANNEL']['ITEM_RESOURCES'][] = $resource;
                    }
                } elseif ($version == '2.0' || $version == '0.92') {
                    $this->addItem($info['child']);
                }                
                break;

            case 'IMAGE':
                if ($version == '1.0') {
                    foreach ($item['attributes'] as $ignore=>$resource);
                    $this->mapped['CHANNEL']['IMAGE'] = $resource;
                } elseif ($version == '2.0' || $version == '0.92') {
                    $this->pullImage($info['child']);
                }                break;

            case 'TEXTINPUT':
                foreach ($item['attributes'] as $ignore=>$resource);
                $this->mapped['CHANNEL']['TEXTINPUT'] = $resource;
                break;

            default:
                $this->mapped['CHANNEL'][$name] = $content;
            }
        }

    }

    function pullImage($data)
    {
        foreach ($data as $info) {
            extract($info);
            $this->mapped['IMAGE'][$name] = $content;
        }
    }

    function addItem($data)
    {
        foreach ($data as $info) {
            extract($info);
            $item[$name] = $content;
        }
        $this->mapped['ITEMS'][] = $item;
    }

    function pullTextInput($data)
    {
        foreach ($data as $info) {
            extract($info);
            $this->mapped['TEXT_INPUT'][$name] = $content;
        }
    }

    function mapData()
    {
        if (isset($this->_parser->data[0]['attributes']['VERSION'])) {
            $version = &$this->_parser->data[0]['attributes']['VERSION'];
        } else {
            $version = '1.0';
        }
        
        $section = &$this->_parser->data[0]['child'];

        foreach ($section as $sec_key => $sec_value) {
            switch ($sec_value['name']) {

            case 'CHANNEL':
                $this->pullChannel($sec_value['child'], $version);
                break;

            case 'IMAGE':
                $this->pullImage($sec_value['child']);
                break;

            case 'ITEM':
                $this->addItem($sec_value['child']);
                break;

            case 'TEXTINPUT':
                $this->pullTextInput($sec_value['child']);
                break;
            }

        }
    }

}

?>