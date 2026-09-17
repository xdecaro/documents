<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

final class DocumentsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'd.id',
                'title', 'd.title',
                'document_type', 'd.document_type',
                'lifecycle_status', 'd.lifecycle_status',
                'confidentiality', 'd.confidentiality',
                'expires_at', 'd.expires_at',
                'created', 'd.created',
                'state', 'd.state',
                'access', 'd.access',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'd.created', $direction = 'DESC'): void
    {
        $app = Factory::getApplication();

        $this->setState('filter.search', $app->getUserStateFromRequest(
            'com_decarodocuments.documents.filter.search',
            'filter_search',
            ''
        ));
        $this->setState('filter.lifecycle_status', $app->getUserStateFromRequest(
            'com_decarodocuments.documents.filter.lifecycle_status',
            'filter_lifecycle_status',
            '',
            'cmd'
        ));
        $this->setState('filter.confidentiality', $app->getUserStateFromRequest(
            'com_decarodocuments.documents.filter.confidentiality',
            'filter_confidentiality',
            '',
            'cmd'
        ));

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('d.id'),
                $db->quoteName('d.uuid'),
                $db->quoteName('d.title'),
                $db->quoteName('d.document_type'),
                $db->quoteName('d.lifecycle_status'),
                $db->quoteName('d.confidentiality'),
                $db->quoteName('d.reference_code'),
                $db->quoteName('d.document_date'),
                $db->quoteName('d.expires_at'),
                $db->quoteName('d.language'),
                $db->quoteName('d.current_version'),
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
            ->leftJoin($db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('d.created_by'));

        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            if (str_starts_with($search, 'id:') && ctype_digit(substr($search, 3))) {
                $id = (int) substr($search, 3);
                $query->where($db->quoteName('d.id') . ' = :documentId')
                    ->bind(':documentId', $id, ParameterType::INTEGER);
            } else {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
                $query->where('('
                    . $db->quoteName('d.title') . ' LIKE :search'
                    . ' OR ' . $db->quoteName('d.original_name') . ' LIKE :search'
                    . ' OR ' . $db->quoteName('d.reference_code') . ' LIKE :search'
                    . ' OR ' . $db->quoteName('d.uuid') . ' LIKE :search'
                    . ')')
                    ->bind(':search', $like);
            }
        }

        $lifecycle = (string) $this->getState('filter.lifecycle_status');
        if ($lifecycle !== '') {
            $query->where($db->quoteName('d.lifecycle_status') . ' = :lifecycle')
                ->bind(':lifecycle', $lifecycle);
        }

        $confidentiality = (string) $this->getState('filter.confidentiality');
        if ($confidentiality !== '') {
            $query->where($db->quoteName('d.confidentiality') . ' = :confidentiality')
                ->bind(':confidentiality', $confidentiality);
        }

        $orderCol = $this->state->get('list.ordering', 'd.created');
        $orderDirn = strtoupper((string) $this->state->get('list.direction', 'DESC'));
        if (!in_array($orderDirn, ['ASC', 'DESC'], true)) {
            $orderDirn = 'DESC';
        }

        $query->order($db->escape($orderCol) . ' ' . $orderDirn)
            ->order($db->quoteName('d.id') . ' DESC');

        return $query;
    }
}
