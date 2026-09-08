<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\View\Document;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Xdecaro\Component\Decarodocuments\Administrator\Helper\CoreUiHelper;

final class HtmlView extends BaseHtmlView
{
    public $form;
    public $item;

    public function display($tpl = null): void
    {
        $user = Factory::getApplication()->getIdentity();
        $this->item = $this->get('Item');
        $isNew = empty($this->item->id);
        $permission = $isNew ? 'core.create' : 'core.edit';
        if (!$user->authorise($permission, 'com_decarodocuments')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        CoreUiHelper::useComponents();
        HTMLHelper::_('behavior.formvalidator');
        HTMLHelper::_('behavior.keepalive');
        $this->form = $this->get('Form');
        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }
        ToolbarHelper::title($isNew ? Text::_('COM_DECARODOCUMENTS_NEW_DOCUMENT') : Text::_('COM_DECARODOCUMENTS_EDIT_DOCUMENT'), 'file');
        ToolbarHelper::apply('document.apply');
        ToolbarHelper::save('document.save');
        ToolbarHelper::cancel('document.cancel');
        parent::display($tpl);
    }
}
