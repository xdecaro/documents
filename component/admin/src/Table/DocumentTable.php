<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

final class DocumentTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__decarodocuments_documents', 'id', $db);
    }

    public function check(): bool
    {
        $this->title = trim((string) $this->title);
        $this->uuid = strtolower(trim((string) $this->uuid));

        if ($this->title === '') {
            $this->setError('A document title is required.');
            return false;
        }

        if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $this->uuid)) {
            $this->setError('The document UUID is invalid.');
            return false;
        }

        if (!in_array((int) $this->state, [-2, 0, 1], true)) {
            $this->setError('The document state is invalid.');
            return false;
        }

        if ((int) $this->access <= 0) {
            $this->setError('The document access level is invalid.');
            return false;
        }

        return parent::check();
    }
}
