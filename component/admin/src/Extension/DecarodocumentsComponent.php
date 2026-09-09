<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\MVCComponent;
use RuntimeException;
use Xdecaro\Component\Decarodocuments\Administrator\Service\RelationService;

/**
 * Public Documents component extension surface.
 *
 * Cross-product consumers may obtain this component through Joomla
 * Application::bootComponent('com_decarodocuments') and then request the
 * documented provider-owned services. The service itself remains responsible
 * for ACL and persistence checks.
 */
final class DecarodocumentsComponent extends MVCComponent
{
    private ?RelationService $relationService = null;

    public function setRelationService(RelationService $relationService): void
    {
        $this->relationService = $relationService;
    }

    public function getRelationService(): RelationService
    {
        if ($this->relationService === null) {
            throw new RuntimeException('Documents relation service is not available.');
        }

        return $this->relationService;
    }
}
