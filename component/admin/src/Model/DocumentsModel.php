<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

final class DocumentsModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        return $db->getQuery(true)
            ->select([
                $db->quoteName('d.id'),
                $db->quoteName('d.uuid'),
                $db->quoteName('d.title'),
                $db->quoteName('d.original_name'),
                $db->quoteName('d.mime_type'),
                $db->quoteName('d.file_size'),
                $db->quoteName('d.state'),
                $db->quoteName('d.access'),
                $db->quoteName('d.created'),
                $db->quoteName('d.created_by'),
                $db->quoteName('ag.title', 'access_level'),
                $db->quoteName('u.name', 'created_by_name'),
            ])
            ->from($db->quoteName('#__decarodocuments_documents', 'd'))
            ->leftJoin($db->quoteName('#__viewlevels', 'ag') . ' ON ' . $db->quoteName('ag.id') . ' = ' . $db->quoteName('d.access'))
            ->leftJoin($db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('d.created_by'))
            ->order($db->quoteName('d.created') . ' DESC, ' . $db->quoteName('d.id') . ' DESC');
    }
}
