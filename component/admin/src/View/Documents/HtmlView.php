<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\View\Documents;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Xdecaro\Component\Decarodocuments\Administrator\Helper\CoreUiHelper;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public $pagination;
    public $state;

    public function display($tpl = null): void
    {
        $user = Factory::getApplication()->getIdentity();
        if (!$user->authorise('core.manage', 'com_decarodocuments')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        CoreUiHelper::useComponents();
        $this->items = (array) $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }
        ToolbarHelper::title(Text::_('COM_DECARODOCUMENTS_DOCUMENTS'), 'folder-open');
        if ($user->authorise('core.create', 'com_decarodocuments')) {
            ToolbarHelper::addNew('document.add');
        }
        if ($user->authorise('core.delete', 'com_decarodocuments')) {
            ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'documents.delete');
        }
        if ($user->authorise('core.admin', 'com_decarodocuments')) {
            ToolbarHelper::preferences('com_decarodocuments');
        }
        parent::display($tpl);
    }
}
