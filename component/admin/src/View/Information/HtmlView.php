<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\View\Information;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Xdecaro\Component\Decarodocuments\Administrator\Helper\CoreUiHelper;

final class HtmlView extends BaseHtmlView
{
    public array $info = [];

    public function display($tpl = null): void
    {
        $user = Factory::getApplication()->getIdentity();
        if (!$user->authorise('core.manage', 'com_decarodocuments')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        CoreUiHelper::useComponents();
        $this->info = (array) $this->get('Info');
        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }
        ToolbarHelper::title(Text::_('COM_DECARODOCUMENTS_INFORMATION'), 'info-circle');
        parent::display($tpl);
    }
}
