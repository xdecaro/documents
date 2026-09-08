<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

final class DocumentsController extends AdminController
{
    public function getModel($name = 'Document', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
