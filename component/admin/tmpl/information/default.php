<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$info = $this->info;
$healthy = !empty($info['core_compatible'])
    && !empty($info['core_api'])
    && !empty($info['documents_table'])
    && !empty($info['relations_table'])
    && !empty($info['storage_ready']);
?>
<div class="xdecaro-scope">
    <div class="xdecaro-toolbar mb-3">
        <span class="xdecaro-badge <?= $healthy ? 'xdecaro-badge--success' : 'xdecaro-badge--warning'; ?>">
            <?= Text::_($healthy ? 'COM_DECARODOCUMENTS_HEALTHY' : 'COM_DECARODOCUMENTS_CHECK_REQUIRED'); ?>
        </span>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <section class="xdecaro-card h-100">
                <div class="xdecaro-card__header">
                    <h2 class="xdecaro-card__title">Documents by xdecaro</h2>
                    <p class="xdecaro-card__description"><?= Text::_('COM_DECARODOCUMENTS_INFO_PRODUCT_DESC'); ?></p>
                </div>
                <div class="xdecaro-card__body">
                    <dl class="row mb-0">
                        <dt class="col-5"><?= Text::_('COM_DECARODOCUMENTS_VERSION'); ?></dt><dd class="col-7"><?= $this->escape((string) $info['version']); ?></dd>
                        <dt class="col-5"><?= Text::_('COM_DECARODOCUMENTS_COMPONENT_ID'); ?></dt><dd class="col-7"><code><?= $this->escape((string) $info['component_id']); ?></code></dd>
                        <dt class="col-5"><?= Text::_('COM_DECARODOCUMENTS_PACKAGE_ID'); ?></dt><dd class="col-7"><code><?= $this->escape((string) $info['package_id']); ?></code></dd>
                        <dt class="col-5"><?= Text::_('COM_DECARODOCUMENTS_DOCUMENT_COUNT'); ?></dt><dd class="col-7"><?= (int) $info['document_count']; ?></dd>
                    </dl>
                </div>
            </section>
        </div>

        <div class="col-12 col-lg-6">
            <section class="xdecaro-card h-100">
                <div class="xdecaro-card__header">
                    <h2 class="xdecaro-card__title"><?= Text::_('COM_DECARODOCUMENTS_DIAGNOSTICS'); ?></h2>
                    <p class="xdecaro-card__description"><?= Text::_('COM_DECARODOCUMENTS_DIAGNOSTICS_PRIVACY'); ?></p>
                </div>
                <div class="xdecaro-card__body">
                    <dl class="row mb-0">
                        <dt class="col-6">Joomla</dt><dd class="col-6"><?= $this->escape((string) $info['joomla_version']); ?></dd>
                        <dt class="col-6">PHP</dt><dd class="col-6"><?= $this->escape((string) $info['php_version']); ?></dd>
                        <dt class="col-6">Core by xdecaro</dt><dd class="col-6"><?= $this->escape((string) ($info['core_version'] ?: '—')); ?></dd>
                        <dt class="col-6"><?= Text::_('COM_DECARODOCUMENTS_CORE_API'); ?></dt><dd class="col-6"><span class="xdecaro-badge <?= !empty($info['core_api']) ? 'xdecaro-badge--success' : 'xdecaro-badge--warning'; ?>"><?= Text::_(!empty($info['core_api']) ? 'JYES' : 'JNO'); ?></span></dd>
                        <dt class="col-6"><?= Text::_('COM_DECARODOCUMENTS_DATABASE'); ?></dt><dd class="col-6"><span class="xdecaro-badge <?= !empty($info['documents_table']) && !empty($info['relations_table']) ? 'xdecaro-badge--success' : 'xdecaro-badge--warning'; ?>"><?= Text::_(!empty($info['documents_table']) && !empty($info['relations_table']) ? 'COM_DECARODOCUMENTS_ALIGNED' : 'COM_DECARODOCUMENTS_CHECK_REQUIRED'); ?></span></dd>
                        <dt class="col-6"><?= Text::_('COM_DECARODOCUMENTS_PRIVATE_STORAGE'); ?></dt><dd class="col-6"><span class="xdecaro-badge <?= !empty($info['storage_ready']) ? 'xdecaro-badge--success' : 'xdecaro-badge--warning'; ?>"><?= Text::_(!empty($info['storage_ready']) ? 'COM_DECARODOCUMENTS_READY' : 'COM_DECARODOCUMENTS_CHECK_REQUIRED'); ?></span></dd>
                    </dl>
                </div>
            </section>
        </div>
    </div>
</div>
