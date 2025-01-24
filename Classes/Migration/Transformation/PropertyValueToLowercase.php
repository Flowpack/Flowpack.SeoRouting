<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Migration\Transformation;

use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Migration\Transformations\AbstractTransformation;

class PropertyValueToLowercase extends AbstractTransformation
{
    private string $propertyName;

    public function setProperty(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    /**
     * @inheritDoc
     */
    public function isTransformable(NodeData $node)
    {
        return $node->hasProperty($this->propertyName);
    }

    /**
     * @inheritDoc
     */
    public function execute(NodeData $node)
    {
        $currentPropertyValue = $node->getProperty($this->propertyName);
        if (! is_string($currentPropertyValue)) {
            return $node;
        }
        $newPropertyValue = strtolower($currentPropertyValue);
        $node->setProperty($this->propertyName, $newPropertyValue);

        return $node;
    }
}
