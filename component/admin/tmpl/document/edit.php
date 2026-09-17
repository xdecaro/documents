<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$isNew = empty($this->item->id);
$formatBytes = static function (int $bytes): string {
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return number_format($bytes / 1024, 1) . ' KiB';
    }
    return number_format($bytes / 1048576, 1) . ' MiB';
};
?>
<div class="xdecaro-scope">
    <form action="<?= Route::_('index.php?option=com_decarodocuments&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>"
          method="post"
          name="adminForm"
          id="document-form"
          class="form-validate"
          enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <section class="xdecaro-card h-100">
                    <div class="xdecaro-card__header">
                        <h2 class="xdecaro-card__title"><?= Text::_($isNew ? 'COM_DECARODOCUMENTS_NEW_DOCUMENT' : 'COM_DECARODOCUMENTS_EDIT_DOCUMENT'); ?></h2>
                        <p class="xdecaro-card__description"><?= Text::_('COM_DECARODOCUMENTS_DOCUMENT_SECURITY_NOTE'); ?></p>
                    </div>
                    <div class="xdecaro-card__body">
                        <?= $this->form->renderField('title'); ?>
                        <?= $this->form->renderField('description'); ?>

                        <div class="row g-3">
                            <div class="col-12 col-md-6"><?= $this->form->renderField('document_type'); ?></div>
                            <div class="col-12 col-md-6"><?= $this->form->renderField('reference_code'); ?></div>
                            <div class="col-12 col-md-4"><?= $this->form->renderField('document_date'); ?></div>
                            <div class="col-12 col-md-4"><?= $this->form->renderField('valid_from'); ?></div>
                            <div class="col-12 col-md-4"><?= $this->form->renderField('expires_at'); ?></div>
                            <div class="col-12 col-md-6"><?= $this->form->renderField('language'); ?></div>
                        </div>

                        <hr class="my-4">

                        <?= $this->form->renderField('upload'); ?>
                        <?php if (!$isNew) : ?>
                            <?= $this->form->renderField('version_note'); ?>
                        <?php endif; ?>

                        <?php if (!$isNew && !empty($this->item->original_name)) : ?>
                            <div class="alert alert-info mt-3" role="status">
                                <strong><?= Text::_('COM_DECARODOCUMENTS_CURRENT_FILE'); ?>:</strong>
                                <?= $this->escape((string) $this->item->original_name); ?>
                                · <?= $this->escape((string) $this->item->mime_type); ?>
                                · <?= $this->escape($formatBytes((int) $this->item->file_size)); ?>
                                · <?= Text::sprintf('COM_DECARODOCUMENTS_VERSION_NUMBER', (int) ($this->item->current_version ?? 1)); ?>
                                · <a href="<?= Route::_('index.php?option=com_decarodocuments&task=document.download&id=' . (int) $this->item->id); ?>"><?= Text::_('COM_DECARODOCUMENTS_DOWNLOAD'); ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-4">
                <section class="xdecaro-card h-100">
                    <div class="xdecaro-card__header">
                        <h2 class="xdecaro-card__title"><?= Text::_('COM_DECARODOCUMENTS_LIFECYCLE_AND_ACCESS'); ?></h2>
                    </div>
                    <div class="xdecaro-card__body">
                        <?= $this->form->renderField('lifecycle_status'); ?>
                        <?= $this->form->renderField('confidentiality'); ?>
                        <?= $this->form->renderField('state'); ?>
                        <?= $this->form->renderField('access'); ?>

                        <?php if (!$isNew && !empty($this->item->uuid)) : ?>
                            <div class="mt-3">
                                <span class="text-muted small"><?= Text::_('COM_DECARODOCUMENTS_UUID'); ?></span><br>
                                <code><?= $this->escape((string) $this->item->uuid); ?></code>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>

        <?php if (!$isNew) : ?>
            <section class="xdecaro-card mt-4">
                <div class="xdecaro-card__header">
                    <h2 class="xdecaro-card__title"><?= Text::_('COM_DECARODOCUMENTS_VERSION_HISTORY'); ?></h2>
                    <p class="xdecaro-card__description"><?= Text::_('COM_DECARODOCUMENTS_VERSION_HISTORY_DESC'); ?></p>
                </div>
                <div class="xdecaro-card__body">
                    <?php if ($this->versions === []) : ?>
                        <div class="xdecaro-empty">
                            <strong><?= Text::_('COM_DECARODOCUMENTS_NO_VERSIONS'); ?></strong>
                        </div>
                    <?php else : ?>
                        <div class="xdecaro-table-wrap">
                            <table class="xdecaro-table">
                                <thead>
                                    <tr>
                                        <th><?= Text::_('COM_DECARODOCUMENTS_VERSION'); ?></th>
                                        <th><?= Text::_('COM_DECARODOCUMENTS_FIELD_FILE'); ?></th>
                                        <th><?= Text::_('COM_DECARODOCUMENTS_FIELD_SIZE'); ?></th>
                                        <th><?= Text::_('COM_DECARODOCUMENTS_VERSION_NOTE'); ?></th>
                                        <th><?= Text::_('COM_DECARODOCUMENTS_VERSION_CREATED'); ?></th>
                                        <th><?= Text::_('COM_DECARODOCUMENTS_VERSION_AUTHOR'); ?></th>
                                        <th><?= Text::_('COM_DECARODOCUMENTS_ACTIONS'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($this->versions as $version) : ?>
                                        <tr>
                                            <td>
                                                <?= (int) $version->version_number; ?>
                                                <?php if ((int) $version->is_current === 1) : ?>
                                                    <span class="xdecaro-badge xdecaro-badge--success"><?= Text::_('COM_DECARODOCUMENTS_CURRENT'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= $this->escape((string) $version->original_name); ?>
                                                <div class="text-muted small"><?= $this->escape((string) $version->mime_type); ?></div>
                                            </td>
                                            <td><?= $this->escape($formatBytes((int) $version->file_size)); ?></td>
                                            <td><?= $this->escape((string) ($version->note ?: '—')); ?></td>
                                            <td><?= $this->escape((string) $version->created); ?></td>
                                            <td><?= $this->escape((string) ($version->created_by_name ?: '—')); ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-secondary"
                                                   href="<?= Route::_('index.php?option=com_decarodocuments&task=document.downloadVersion&id=' . (int) $this->item->id . '&version_id=' . (int) $version->id); ?>">
                                                    <?= Text::_('COM_DECARODOCUMENTS_DOWNLOAD'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?= $this->form->renderField('id'); ?>
        <?= $this->form->renderField('uuid'); ?>
        <input type="hidden" name="task" value="">
        <?= HTMLHelper::_('form.token'); ?>
    </form>
</div>
