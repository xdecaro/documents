<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\MVCComponent;
use RuntimeException;
use Xdecaro\Component\Decarodocuments\Administrator\Service\AnalyticsSourceService;
use Xdecaro\Component\Decarodocuments\Administrator\Service\CoreIntegrationService;
use Xdecaro\Component\Decarodocuments\Administrator\Service\CrossProductIntegrationService;
use Xdecaro\Component\Decarodocuments\Administrator\Service\RelationService;

/** Public Documents component extension surface. */
final class DecarodocumentsComponent extends MVCComponent
{
    private ?RelationService $relationService = null;
    private ?CoreIntegrationService $coreIntegrationService = null;
    private ?CrossProductIntegrationService $crossProductIntegrationService = null;
    private ?AnalyticsSourceService $analyticsSourceService = null;

    public function setRelationService(RelationService $service): void { $this->relationService = $service; }
    public function setCoreIntegrationService(CoreIntegrationService $service): void { $this->coreIntegrationService = $service; }
    public function setCrossProductIntegrationService(CrossProductIntegrationService $service): void { $this->crossProductIntegrationService = $service; }
    public function setAnalyticsSourceService(AnalyticsSourceService $service): void { $this->analyticsSourceService = $service; }

    public function getRelationService(): RelationService
    {
        if ($this->relationService === null) { throw new RuntimeException('Documents relation service is not available.'); }
        return $this->relationService;
    }

    public function getCoreIntegrationService(): CoreIntegrationService
    {
        if ($this->coreIntegrationService === null) { throw new RuntimeException('Documents Core integration service is not available.'); }
        return $this->coreIntegrationService;
    }

    public function getCrossProductIntegrationService(): CrossProductIntegrationService
    {
        if ($this->crossProductIntegrationService === null) { throw new RuntimeException('Documents cross-product integration service is not available.'); }
        return $this->crossProductIntegrationService;
    }

    public function getAnalyticsSourceService(): AnalyticsSourceService
    {
        if ($this->analyticsSourceService === null) { throw new RuntimeException('Documents analytics source service is not available.'); }
        return $this->analyticsSourceService;
    }
}
