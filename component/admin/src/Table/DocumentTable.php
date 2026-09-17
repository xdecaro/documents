<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

final class DocumentTable extends Table
{
    private const TYPES = ['generic', 'identity', 'certificate', 'application', 'form', 'resolution', 'minutes', 'financial', 'contract', 'insurance', 'receipt', 'disciplinary', 'other'];
    private const LIFECYCLE = ['draft', 'review', 'approval', 'approved', 'published', 'archived', 'expired'];
    private const CONFIDENTIALITY = ['public', 'internal', 'confidential', 'sensitive'];

    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__decarodocuments_documents', 'id', $db);
    }

    public function check(): bool
    {
        $this->title = trim((string) $this->title);
        $this->uuid = strtolower(trim((string) $this->uuid));
        $this->document_type = strtolower(trim((string) ($this->document_type ?? 'generic')));
        $this->lifecycle_status = strtolower(trim((string) ($this->lifecycle_status ?? 'draft')));
        $this->confidentiality = strtolower(trim((string) ($this->confidentiality ?? 'internal')));
        $this->reference_code = trim((string) ($this->reference_code ?? ''));
        $this->language = trim((string) ($this->language ?? '*'));

        if ($this->title === '') {
            $this->setError('A document title is required.');
            return false;
        }

        if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $this->uuid)) {
            $this->setError('The document UUID is invalid.');
            return false;
        }

        if (!in_array($this->document_type, self::TYPES, true)) {
            $this->setError('The document type is invalid.');
            return false;
        }

        if (!in_array($this->lifecycle_status, self::LIFECYCLE, true)) {
            $this->setError('The document lifecycle status is invalid.');
            return false;
        }

        if (!in_array($this->confidentiality, self::CONFIDENTIALITY, true)) {
            $this->setError('The document confidentiality classification is invalid.');
            return false;
        }

        if (strlen($this->reference_code) > 191) {
            $this->setError('The document reference is too long.');
            return false;
        }

        if ($this->language !== '*' && !preg_match('/^[a-z]{2,3}-[A-Z]{2}$/D', $this->language)) {
            $this->setError('The document language is invalid.');
            return false;
        }

        foreach (['document_date', 'valid_from'] as $field) {
            $value = $this->{$field} ?? null;
            if ($value !== null && $value !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/D', (string) $value)) {
                $this->setError('A document date value is invalid.');
                return false;
            }
        }

        if (($this->expires_at ?? null) !== null && $this->expires_at !== ''
            && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', (string) $this->expires_at)) {
            $this->setError('The document expiry value is invalid.');
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
