<?php

/**
 * @version $Id$
 * @author Matthew McNaney <mcnaney at gmail dot com>
 */
class Folder
{
    public $id = 0;
    public $key_id = 0;
    public $title = null;
    public $description = null;
    public $ftype = IMAGE_FOLDER;
    public $public_folder = 1;
    public $icon = null;
    public $module_created = null;
    public $max_image_dimension = 0;
    // An array of file objects
    public $_files = 0;
    public $_error = 0;
    public $_base_directory = null;

    public function __construct($id = 0)
    {
        if (!$id) {
            return;
        }

        $this->id = (int) $id;
        $this->init();
        if ($this->_error) {
            $this->logError();
            $this->id = 0;
        }
    }

    public function init()
    {
        $db = new PHPWS_DB('folders');
        $result = $db->loadObject($this);
        if (PHPWS_Error::isError($result)) {
            $this->_error = $result;
        }
    }

    public function setFtype($ftype)
    {
        if (!in_array($ftype, array(IMAGE_FOLDER, DOCUMENT_FOLDER, MULTIMEDIA_FOLDER))) {
            return false;
        }
        $this->ftype = $ftype;
    }

    public function getPublic()
    {
        if ($this->public_folder) {
            return dgettext('filecabinet', 'Public');
        } else {
            return dgettext('filecabinet', 'Private');
        }
    }

    public function deleteLink($mode = 'link')
    {
        $vars['QUESTION'] = dgettext('filecabinet', 'Are you certain you want to delete this folder and all its contents?');
        $vars['ADDRESS'] = PHPWS_Text::linkAddress('filecabinet', array('aop' => 'delete_folder', 'folder_id' => $this->id), true);
        $label = dgettext('filecabinet', 'Delete');
        if ($mode == 'image') {
            $vars['LINK'] = Icon::show('delete', dgettext('filecabinet', 'Delete'));
        } else {
            $vars['LINK'] = $label;
        }
        return javascript('confirm', $vars);
    }

    /**
     * @deprecated
     * Creates javascript pop up for creating a new folder
     */
    public function editLink($mode = null, $module_created = null)
    {
        if ($this->id) {
            $vars['aop'] = 'edit_folder';
            $vars['folder_id'] = $this->id;
            if ($mode == 'title') {
                $label = $this->title;
            } else {
                $label = dgettext('filecabinet', 'Edit');
            }
        } else {
            $label = dgettext('filecabinet', 'Add folder');
            $vars['aop'] = 'add_folder';
        }

        if ($mode == 'image') {
            $js['label'] = '<i class="fa fa-edit" title="' . dgettext('filecabinet', 'Edit') . '"></i>';
        } else {
            return "<button class='btn btn-success '><i class='fa fa-plus'></i> Add folder</button>";
            //$js['label'] = & $label;
        }

        $vars['ftype'] = $this->ftype;
        if ($module_created) {
            $vars['module_created'] = $module_created;
        }

        $js['address'] = PHPWS_Text::linkAddress('filecabinet', $vars, true);

        $js['width'] = 370;
        $js['height'] = 500;
        if ($mode == 'button') {
            $js['type'] = 'button';
        }
        return javascript('open_window', $js);
    }

    public function deleteImageLink()
    {
        $vars['action'] = 'delete_image';
        $vars['image_id'] = $this->id;
        $js['QUESTION'] = dgettext('filecabinet', 'Are you sure you want to delete this image?');
        $js['ADDRESS'] = PHPWS_Text::linkAddress('filecabinet', $vars, true);
        $js['LINK'] = dgettext('filecabinet', 'Delete');
        $links[] = javascript('confirm', $js);
    }

    public function getFullDirectory()
    {
        if (!$this->id) {
            return null;
        }
        if (empty($this->_base_directory)) {
            $this->loadDirectory();
        }
        return sprintf('%sfolder%s/', $this->_base_directory, $this->id);
    }

    public function loadDirectory()
    {
        if ($this->ftype == DOCUMENT_FOLDER) {
            $this->_base_directory = PHPWS_Settings::get('filecabinet', 'base_doc_directory');
        } elseif ($this->ftype == IMAGE_FOLDER) {
            $this->_base_directory = 'images/filecabinet/';
        } else {
            $this->_base_directory = 'files/multimedia/';
        }
    }

    /**
     * @deprecated
     * @param type $button
     * @return type
     */
    public function embedLink($button = false)
    {
        $vars['address'] = PHPWS_Text::linkAddress('filecabinet', array('mop' => 'edit_embed',
                    'folder_id' => $this->id), true);
        $vars['width'] = 400;
        $vars['height'] = 200;
        $vars['title'] = $vars['label'] = dgettext('filecabinet', 'Add embedded');
        if ($button) {
            $vars['type'] = 'button';
        }
        return javascript('open_window', $vars);
    }

    public function logError()
    {
        PHPWS_Error::log($this->_error);
    }

    public function setTitle($title)
    {
        $this->title = trim(strip_tags($title));
    }

    public function viewLink($formatted = true)
    {
        $link = sprintf('index.php?module=filecabinet&amp;uop=view_folder&amp;folder_id=%s', $this->id);

        if (!$formatted) {
            return $link;
        } else {
            return sprintf('<a href="%s" title="%s">%s</a>', $link, dgettext('filecabinet', 'View folder'), $this->title);
        }
    }

    public function setDescription($description)
    {
        $this->description = PHPWS_Text::parseInput($description);
    }

    public function post()
    {
        if (empty($_POST['title'])) {
            $this->_error = dgettext('filecabinet', 'You must entitle your folder.');
            return false;
        } else {
            $this->setTitle($_POST['title']);
        }

        $this->ftype = $_POST['ftype'];
        if (isset($_POST['max_image_dimension'])) {
            $this->max_image_dimension = (int) $_POST['max_image_dimension'];
        }
        if (isset($_POST['public_folder'])) {
            $this->public_folder = $_POST['public_folder'];
        }
        return true;
    }

    public function save()
    {
        if (empty($this->icon)) {
            $this->icon = PHPWS_SOURCE_HTTP . 'mod/filecabinet/img/folder.png';
        }

        if (!$this->id) {
            $new_folder = true;
        } else {
            $new_folder = false;
        }

        $db = new PHPWS_DB('folders');
        $result = $db->saveObject($this);

        if (PHPWS_Error::logIfError($result)) {
            return false;
        }

        $full_dir = $this->getFullDirectory();
        if ($new_folder) {
            if (!is_dir($full_dir)) {
                $result = @mkdir($full_dir);
            } else {
                $result = true;
            }

            if ($result) {
                if ($this->ftype == IMAGE_FOLDER || $this->ftype == MULTIMEDIA_FOLDER) {
                    $thumb_dir = $full_dir . '/tn/';
                    if (!is_dir($thumb_dir)) {
                        $result = @mkdir($thumb_dir);
                        if (!$result) {
                            @rmdir($full_dir);
                            return false;
                        } else {
                            file_put_contents($thumb_dir . '.htaccess', 'Allow from all');
                        }
                    }
                }
            } else {
                PHPWS_Error::log(FC_BAD_DIRECTORY, 'filecabinet', 'Folder:save', $full_dir);
                $this->delete();
                return false;
            }
        }

        if ($this->ftype == DOCUMENT_FOLDER) {
            if ($this->public_folder) {
                $path = $full_dir . '.htaccess';
                if (is_file($path)) {
                    unlink($path);
                }
            } else {
                file_put_contents($full_dir . '.htaccess', 'Deny from all');
            }
        }
        return $this->saveKey($new_folder);
    }

    public function saveKey($new_folder = true)
    {
        if (empty($this->key_id)) {
            $key = new Key;
        } else {
            $key = new Key($this->key_id);
            if (PHPWS_Error::isError($key->getError())) {
                $key = new Key;
            }
        }

        $key->setModule('filecabinet');
        $key->setItemName('folder');
        $key->setItemId($this->id);
        $key->setEditPermission('edit_folders');
        $key->setUrl($this->viewLink(false));
        $key->setTitle($this->title);
        $key->setSummary($this->description);
        $result = $key->save();
        if (PHPWS_Error::isError($result)) {
            return $result;
        }
        $this->key_id = $key->id;

        if ($new_folder) {
            $db = new PHPWS_DB('folders');
            $db->addWhere('id', $this->id);
            $db->addValue('key_id', $this->key_id);
            $result = $db->update();
            if (PHPWS_Error::isError($result)) {
                return $result;
            }
        }
        return true;
    }

    public function allow()
    {
        if (!$this->public_folder && !Current_User::isLogged()) {
            return false;
        }

        if (!$this->key_id) {
            return true;
        }
        $key = new Key($this->key_id);
        return $key->allowView();
    }

    public function delete()
    {
        if ($this->ftype == IMAGE_FOLDER) {
            $table = 'images';
        } elseif ($this->ftype == DOCUMENT_FOLDER) {
            $table = 'documents';
        } elseif ($this->ftype == MULTIMEDIA_FOLDER) {
            $table = 'multimedia';
        } else {
            return false;
        }

        /**
         * Delete file associations inside folder
         */
        $db = new PHPWS_DB('fc_file_assoc');
        $db->addWhere($table . '.folder_id', $this->id);
        $db->addWhere($table . '.id', 'fc_file_assoc.file_id');
        PHPWS_Error::logIfError($db->delete());


        /**
         * Delete the special folder associations to this folder
         */
        $db->reset();
        $db->addWhere('file_type', FC_IMAGE_FOLDER, '=', 'or', 1);
        $db->addWhere('file_type', FC_IMAGE_LIGHTBOX, '=', 'or', 1);
        $db->addWhere('file_type', FC_IMAGE_RANDOM, '=', 'or', 1);
        $db->addWhere('file_type', FC_DOCUMENT_FOLDER, '=', 'or', 1);
        $db->addWhere('file_id', $this->id);
        PHPWS_Error::logIfError($db->delete());

        /**
         * Delete the files in the folder from the db
         */
        unset($db);
        $db = new PHPWS_DB($table);
        $db->addWhere('folder_id', $this->id);
        PHPWS_Error::logIfError($db->delete());

        /**
         * Delete the folder from the database
         */
        $db = new PHPWS_DB('folders');
        $db->addWhere('id', $this->id);
        PHPWS_Error::logIfError($db->delete());

        /**
         * Delete the key
         */
        $key = new Key($this->key_id);
        $key->delete();

        /**
         * Delete the physical directory the folder occupies
         */
        $directory = $this->getFullDirectory();

        if (is_dir($directory)) {
            PHPWS_File::rmdir($directory);
        }

        return true;
    }

    public function rowTags()
    {
        PHPWS_Core::requireConfig('filecabinet', 'config.php');
        if (FC_ICON_PAGER_LINKS) {
            $mode = 'icon';
            $spacer = '';
        } else {
            $mode = null;
            $spacer = ' | ';
        }
        $authkey = \Current_User::getAuthKey();
        //$icon = sprintf('<img src="%s" />', $this->icon);
        $vars['aop'] = 'view_folder';
        $vars['folder_id'] = $this->id;

        $tpl['TITLE'] = PHPWS_Text::moduleLink($this->title, 'filecabinet', $vars);
        $tpl['ITEMS'] = $this->tallyItems();


        if (Current_User::allow('filecabinet', 'edit_folders', $this->id, 'folder')) {
            $links[] = "<i title='Edit folder' style='cursor:pointer' class='fa fa-edit show-modal' data-operation='aop' data-command='edit_folder' data-folder-id='$this->id' data-ftype='$this->ftype'></i>";
            //$links[] = $this->editLink('image');
            $links[] = $this->uploadLink('icon');
        }

        if (Current_User::allow('filecabinet', 'edit_folders', $this->id, 'folder', true)) {
            if ($this->key_id) {
                $links[] = Current_User::popupPermission($this->key_id, null, $mode);
            }
        }

        if (Current_User::allow('filecabinet', 'delete_folders', null, null, true)) {
            $authkey = \Current_User::getAuthKey();
            $links[] = "<i title='Delete folder' style='cursor:pointer' class='fa fa-trash-o delete-folder' data-folder-id='$this->id' data-authkey='$authkey'></i>";
            //$links[] = $this->deleteLink('image');
        }

        $tpl['PUBLIC'] = $this->getPublic();

        if (!empty($links)) {
            $tpl['LINKS'] = implode($spacer, $links);
        }

        return $tpl;
    }

    public function uploadLink($type, $file_id = 0)
    {
        $file_id = (int)$file_id;
        switch ($this->ftype) {
            case MULTIMEDIA_FOLDER:
                $operation = 'mop';
                $command = 'upload_multimedia_form';
                $label = 'Upload media';
                break;
            case IMAGE_FOLDER:
                $operation = 'iop';
                $command = 'upload_image_form';
                $label = 'Upload image';
                break;
            case DOCUMENT_FOLDER:
                $operation = 'dop';
                $command = 'upload_document_form';
                $label = 'Upload document';
                break;
        }

        $salt = array($operation => $command, 'folder_id' => $this->id, 'file_id'=>$file_id);
        $authkey = \Current_User::getAuthKey(PHPWS_Text::saltArray($salt));

        if ($type == 'icon') {
            if ($file_id) {
                $icon = 'fa-edit';
            } else {
                $icon = 'fa-upload';
            }
            return "<i title='$label' class='fa $icon show-modal pointer' data-authkey='$authkey' data-command='$command' data-operation='$operation' data-folder-id='$this->id' data-id='$file_id'></i>";
        } elseif ($type == 'button') {
            return <<<EOF
<button class="btn btn-primary show-modal" data-authkey="$authkey" data-command="$command" data-operation="$operation" data-folder-id="$this->id" data-id="$file_id">
    <i class="fa fa-plus"></i> $label</button>
EOF;
        }
    }

    /**
     * Loads the files in the current folder into the _files variable
     * $original_only applies to images
     */
    public function loadFiles($original_only = false)
    {
        if ($this->ftype == IMAGE_FOLDER) {
            PHPWS_Core::initModClass('filecabinet', 'Image.php');
            $db = new PHPWS_DB('images');
            $obj_name = 'PHPWS_Image';
        } elseif ($this->ftype == DOCUMENT_FOLDER) {
            PHPWS_Core::initModClass('filecabinet', 'Document.php');
            $db = new PHPWS_DB('documents');
            $obj_name = 'PHPWS_Document';
        } elseif ($this->ftype == MULTIMEDIA_FOLDER) {
            PHPWS_Core::initModClass('filecabinet', 'Multimedia.php');
            $db = new PHPWS_DB('multimedia');
            $obj_name = 'PHPWS_Multimedia';
        }

        $db->addWhere('folder_id', $this->id);
        $db->addOrder('title');
        $result = $db->getObjects($obj_name);

        if (PHPWS_Error::isError($result)) {
            PHPWS_Error::log($result);
            return false;
        } elseif ($result) {
            $this->_files = &$result;
            return true;
        } else {
            return false;
        }
    }

    public function tallyItems()
    {
        if ($this->ftype == IMAGE_FOLDER) {
            $db = new PHPWS_DB('images');
        } elseif ($this->ftype == DOCUMENT_FOLDER) {
            $db = new PHPWS_DB('documents');
        } elseif ($this->ftype == MULTIMEDIA_FOLDER) {
            $db = new PHPWS_DB('multimedia');
        }

        $db->addWhere('folder_id', $this->id);
        return $db->count();
    }

}

?>