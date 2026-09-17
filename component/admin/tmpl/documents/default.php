<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.multiselect');

$formatBytes = static function (int $bytes): string {
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return number_format($bytes / 1024, 1) . ' KiB';
    }
    return number_format($bytes / 1048576, 1) . ' MiB';
};

$typeLabels = [
    'generic' => 'COM_DECARODOCUMENTS_TYPE_GENERIC',
    'identity' => 'COM_DECARODOCUMENTS_TYPE_IDENTITY',
    'certificate' => 'COM_DECARODOCUMENTS_TYPE_CERTIFICATE',
    'application' => 'COM_DECARODOCUMENTS_TYPE_APPLICATION',
    'form' => 'COM_DECARODOCUMENTS_TYPE_FORM',
    'resolution' => 'COM_DECARODOCUMENTS_TYPE_RESOLUTION',
    'minutes' => 'COM_DECARODOCUMENTS_TYPE_MINUTES',
    'financial' => 'COM_DECARODOCUMENTS_TYPE_FINANCIAL',
    'contract' => 'COM_DECARODOCUMENTS_TYPE_CONTRACT',
    'insurance' => 'COM_DECARODOCUMENTS_TYPE_INSURANCE',
    'receipt' => 'COM_DECARODOCUMENTS_TYPE_RECEIPT',
    'disciplinary' => 'COM_DECARODOCUMENTS_TYPE_DISCIPLINARY',
    'other' => 'COM_DECARODOCUMENTS_TYPE_OTHER',
];

$lifecycleLabels = [
    'draft' => 'COM_DECARODOCUMENTS_STATUS_DRAFT',
    'review' => 'COM_DECARODOCUMENTS_STATUS_REVIEW',
    'approval' => 'COM_DECARODOCUMENTS_STATUS_APPROVAL',
    'approved' => 'COM_DECARODOCUMENTS_STATUS_APPROVED',
    'published' => 'COM_DECARODOCUMENTS_STATUS_PUBLISHED',
    'archived' => 'COM_DECARODOCUMENTS_STATUS_ARCHIVED',
    'expired' => 'COM_DECARODOCUMENTS_STATUS_EXPIRED',
];

$confidentialityLabels = [
    'public' => 'COM_DECARODOCUMENTS_CONFIDENTIALITY_PUBLIC',
    'internal' => 'COM_DECARODOCUMENTS_CONFIDENTIALITY_INTERNAL',
    'confidential' => 'COM_DECARODOCUMENTS_CONFIDENTIALITY_CONFIDENTIAL',
    'sensitive' => 'COM_DECARODOCUMENTS_CONFIDENTIALITY_SENSITIVE',
];

$search = (string) $this->state->get('filter.search', '');
$lifecycle = (string) $this->state->get('filter.lifecycle_status', '');
$confidentiality = (string) $this->state->get('filter.confidentiality', '');
?>
<div class="xdecaro-scope">
    <form action="<?= Route::_('index.php?option=com_decarodocuments&view=documents'); ?>" method="post" name="adminForm" id="adminForm">
        <section class="xdecaro-card">
            <div class="xdecaro-card__header">
                <h2 class="xdecaro-card__title"><?= Text::_('COM_DECARODOCUMENTS_DOCUMENTS'); ?></h2>
                <p class="xdecaro-card__description"><?= Text::_('COM_DECARODOCUMENTS_DOCUMENTS_DESC'); ?></p>
            </div>
            <div class="xdecaro-card__body">
                <div class="row g-2 align-items-end mb-4">
                    <div class="col-12 col-lg-5">
                        <label class="form-label" for="filter_search"><?= Text::_('JSEARCH_FILTER_LABEL'); ?></label>
                        <input type="search"
                               class="form-control"
                               name="filter_search"
                               id="filter_search"
                               value="<?= $this->escape($search); ?>"
                               placeholder="<?= $this->escape(Text::_('COM_DECARODOCUMENTS_SEARCH_PLACEHOLDER')); ?>">
                    </div>
                    <div class="col-12 col-md-5 col-lg-3">
                        <label class="form-label" for="filter_lifecycle_status"><?= Text::_('COM_DECARODOCUMENTS_FIELD_LIFECYCLE_STATUS'); ?></label>
                        <select class="form-select" name="filter_lifecycle_status" id="filter_lifecycle_status">
                            <option value=""><?= Text::_('JOPTION_SELECT_PUBLISHED'); ?></option>
                            <?php foreach ($lifecycleLabels as $value => $label) : ?>
                                <option value="<?= $this->escape($value); ?>"<?= $lifecycle === $value ? ' selected' : ''; ?>><?= Text::_($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-5 col-lg-2">
                        <label class="form-label" for="filter_confidentiality"><?= Text::_('COM_DECARODOCUMENTS_FIELD_CONFIDENTIALITY'); ?></label>
                        <select class="form-select" name="filter_confidentiality" id="filter_confidentiality">
                            <option value=""><?= Text::_('JOPTION_SELECT_ACCESS'); ?></option>
                            <?php foreach ($confidentialityLabels as $value => $label) : ?>
                                <option value="<?= $this->escape($value); ?>"<?= $confidentiality === $value ? ' selected' : ''; ?>><?= Text::_($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"><?= Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
                        <a class="btn btn-outline-secondary" href="<?= Route::_('index.php?option=com_decarodocuments&view=documents&filter_search=&filter_lifecycle_status=&filter_confidentiality='); ?>">
                            <?= Text::_('JSEARCH_FILTER_CLEAR'); ?>
                        </a>
                    </div>
                </div>

                <?php if ($this->items === []) : ?>
                    <div class="xdecaro-empty">
                        <strong><?= Text::_('COM_DECARODOCUMENTS_NO_DOCUMENTS'); ?></strong>
                        <span><?= Text::_('COM_DECARODOCUMENTS_NO_DOCUMENTS_DESC'); ?></span>
                    </div>
                <?php else : ?>
                    <div class="xdecaro-table-wrap">
                        <table class="xdecaro-table">
                            <thead>
                                <tr>
                                    <th style="width:1%" class="text-center"><?= HTMLHelper::_('grid.checkall'); ?></th>
                                    <th><?= Text::_('COM_DECARODOCUMENTS_FIELD_TITLE'); ?></th>
                                    <th><?= Text::_('COM_DECARODOCUMENTS_FIELD_DOCUMENT_TYPE'); ?></th>
                                    <th><?= Text::_('COM_DECARODOCUMENTS_FIELD_LIFECYCLE_STATUS'); ?></th>
                                    <th><?= Text::_('COM_DECARODOCUMENTS_FIELD_CONFIDENTIALITY'); ?></th>
                                    <th><?= Text::_('COM_DECARODOCUMENTS_VERSION'); ?></th>
                                    <th><?= Text::_('COM_DECARODOCUMENTS_FIELD_EXPIRES_AT'); ?></th>
                                    <th><?= Text::_('JFIELD_ACCESS_LABEL'); ?></th>
                                    <th><?= Text::_('JSTATUS'); ?></th>
                                    <th><?= Text::_('JDATE'); ?></th>
                                    <th style="width:1%">ID</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <?php
                                $isExpired = !empty($item->expires_at) && strtotime((string) $item->expires_at) < time();
                                $lifecycleKey = $isExpired ? 'expired' : (string) $item->lifecycle_status;
                                $confidentialityKey = (string) $item->confidentiality;
                                ?>
                                <tr>
                                    <td class="text-center"><?= HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td>
                                    <td>
                                        <a href="<?= Route::_('index.php?option=com_decarodocuments&task=document.edit&id=' . (int) $item->id); ?>">
                                            <?= $this->escape((string) $item->title); ?>
                                        </a>
                                        <div class="text-muted small">
                                            <a href="<?= Route::_('index.php?option=com_decarodocuments&task=document.download&id=' . (int) $item->id); ?>">
                                                <?= $this->escape((string) $item->original_name); ?>
                                            </a>
                                            · <?= $this->escape($formatBytes((int) $item->file_size)); ?>
                                        </div>
                                        <?php if ((string) $item->reference_code !== '') : ?>
                                            <div class="text-muted small"><?= Text::_('COM_DECARODOCUMENTS_FIELD_REFERENCE'); ?>: <?= $this->escape((string) $item->reference_code); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= Text::_($typeLabels[(string) $item->document_type] ?? 'COM_DECARODOCUMENTS_TYPE_OTHER'); ?></td>
                                    <td>
                                        <span class="xdecaro-badge<?= $lifecycleKey === 'published' || $lifecycleKey === 'approved' ? ' xdecaro-badge--success' : ($lifecycleKey === 'expired' ? ' xdecaro-badge--warning' : ''); ?>">
                                            <?= Text::_($lifecycleLabels[$lifecycleKey] ?? 'COM_DECARODOCUMENTS_STATUS_DRAFT'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="xdecaro-badge<?= in_array($confidentialityKey, ['confidential', 'sensitive'], true) ? ' xdecaro-badge--warning' : ''; ?>">
                                            <?= Text::_($confidentialityLabels[$confidentialityKey] ?? 'COM_DECARODOCUMENTS_CONFIDENTIALITY_INTERNAL'); ?>
                                        </span>
                                    </td>
                                    <td><?= (int) ($item->current_version ?? 1); ?></td>
                                    <td><?= $this->escape((string) ($item->expires_at ?: '—')); ?></td>
                                    <td><?= $this->escape((string) ($item->access_level ?? '')); ?></td>
                                    <td>
                                        <?php if ((int) $item->state === 1) : ?>
                                            <span class="xdecaro-badge xdecaro-badge--success"><?= Text::_('JPUBLISHED'); ?></span>
                                        <?php elseif ((int) $item->state === 0) : ?>
                                            <span class="xdecaro-badge"><?= Text::_('JUNPUBLISHED'); ?></span>
                                        <?php else : ?>
                                            <span class="xdecaro-badge xdecaro-badge--warning"><?= Text::_('JTRASHED'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $this->escape((string) $item->created); ?></td>
                                    <td><?= (int) $item->id; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3"><?= $this->pagination->getListFooter(); ?></div>
                <?php endif; ?>
            </div>
        </section>

        <input type="hidden" name="task" value="">
        <input type="hidden" name="boxchecked" value="0">
        <input type="hidden" name="filter_order" value="<?= $this->escape((string) $this->state->get('list.ordering')); ?>">
        <input type="hidden" name="filter_order_Dir" value="<?= $this->escape((string) $this->state->get('list.direction')); ?>">
        <?= HTMLHelper::_('form.token'); ?>
    </form>
</div>
