<?php
namespace Xdecaro\Plugin\Xdecaroanalytics\Decarodocuments\Provider;

defined('_JEXEC') or die;

use xdecaro\Component\Analytics\Administrator\Contract\AnalyticsProviderInterface;
use Xdecaro\Component\Decarodocuments\Administrator\Service\AnalyticsSourceService;

final class DocumentsProvider implements AnalyticsProviderInterface
{
    private AnalyticsSourceService $source;

    public function __construct(AnalyticsSourceService $source)
    {
        $this->source = $source;
    }

    public function getKey(): string
    {
        return 'documents';
    }

    public function getLabel(): string
    {
        return 'Documents';
    }

    public function getMetrics(): array
    {
        return $this->source->getMetrics();
    }

    public function getDatasets(): array
    {
        return $this->source->getDatasets();
    }

    public function getMetric(string $metricKey, array $context = []): array
    {
        return $this->source->getMetric($metricKey, $context);
    }

    public function getDataset(string $datasetKey, array $context = []): array
    {
        return $this->source->getDataset($datasetKey, $context);
    }
}
