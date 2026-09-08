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
?>
<div class="xdecaro-scope">
    <form action="<?= Route::_('index.php?option=com_decarodocuments&view=documents'); ?>" method="post" name="adminForm" id="adminForm">
        <section class="xdecaro-card">
            <div class="xdecaro-card__header">
                <h2 class="xdecaro-card__title"><?= Text::_('COM_DECARODOCUMENTS_DOCUMENTS'); ?></h2>
                <p class="xdecaro-card__description"><?= Text::_('COM_DECARODOCUMENTS_DOCUMENTS_DESC'); ?></p>
            </div>
            <div class="xdecaro-card__body">
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
                                    <th><?= Text::_('COM_DECARODOCUMENTS_FIELD_FILE'); ?></th>
                                    <th><?= Text::_('COM_DECARODOCUMENTS_FIELD_SIZE'); ?></th>
                                    <th><?= Text::_('JFIELD_ACCESS_LABEL'); ?></th>
                                    <th><?= Text::_('JSTATUS'); ?></th>
                                    <th><?= Text::_('JDATE'); ?></th>
                                    <th style="width:1%">ID</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <tr>
                                    <td class="text-center"><?= HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td>
                                    <td>
                                        <a href="<?= Route::_('index.php?option=com_decarodocuments&task=document.edit&id=' . (int) $item->id); ?>">
                                            <?= $this->escape((string) $item->title); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?= Route::_('index.php?option=com_decarodocuments&task=document.download&id=' . (int) $item->id); ?>">
                                            <?= $this->escape((string) $item->original_name); ?>
                                        </a>
                                        <div class="text-muted small"><?= $this->escape((string) $item->mime_type); ?></div>
                                    </td>
                                    <td><?= $this->escape($formatBytes((int) $item->file_size)); ?></td>
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
        <?= HTMLHelper::_('form.token'); ?>
    </form>
</div>
