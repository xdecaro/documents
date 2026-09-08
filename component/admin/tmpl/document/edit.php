<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$isNew = empty($this->item->id);
?>
<div class="xdecaro-scope">
    <form action="<?= Route::_('index.php?option=com_decarodocuments&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>"
          method="post"
          name="adminForm"
          id="document-form"
          class="form-validate"
          enctype="multipart/form-data">
        <section class="xdecaro-card">
            <div class="xdecaro-card__header">
                <h2 class="xdecaro-card__title"><?= Text::_($isNew ? 'COM_DECARODOCUMENTS_NEW_DOCUMENT' : 'COM_DECARODOCUMENTS_EDIT_DOCUMENT'); ?></h2>
                <p class="xdecaro-card__description"><?= Text::_('COM_DECARODOCUMENTS_DOCUMENT_SECURITY_NOTE'); ?></p>
            </div>
            <div class="xdecaro-card__body">
                <div class="row g-4">
                    <div class="col-12 col-lg-8">
                        <?= $this->form->renderField('title'); ?>
                        <?= $this->form->renderField('description'); ?>
                        <?= $this->form->renderField('upload'); ?>

                        <?php if (!$isNew && !empty($this->item->original_name)) : ?>
                            <div class="alert alert-info mt-3" role="status">
                                <strong><?= Text::_('COM_DECARODOCUMENTS_CURRENT_FILE'); ?>:</strong>
                                <?= $this->escape((string) $this->item->original_name); ?>
                                · <?= $this->escape((string) $this->item->mime_type); ?>
                                · <a href="<?= Route::_('index.php?option=com_decarodocuments&task=document.download&id=' . (int) $this->item->id); ?>"><?= Text::_('COM_DECARODOCUMENTS_DOWNLOAD'); ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12 col-lg-4">
                        <?= $this->form->renderField('state'); ?>
                        <?= $this->form->renderField('access'); ?>
                        <?php if (!$isNew && !empty($this->item->uuid)) : ?>
                            <div class="mt-3">
                                <span class="text-muted small">UUID</span><br>
                                <code><?= $this->escape((string) $this->item->uuid); ?></code>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <?= $this->form->renderField('id'); ?>
        <?= $this->form->renderField('uuid'); ?>
        <input type="hidden" name="task" value="">
        <?= HTMLHelper::_('form.token'); ?>
    </form>
</div>
