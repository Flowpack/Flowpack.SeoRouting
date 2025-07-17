<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Migration\Transformation;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\NodeMigration\Transformation\NodeBasedTransformationInterface;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\NodeMigration\Transformation\GlobalTransformationInterface;
use Neos\ContentRepository\NodeMigration\Transformation\TransformationFactoryInterface;
use Neos\ContentRepository\NodeMigration\Transformation\TransformationStep;

/**
 * Transforms a specified property value of a node to lowercase.
 */
class PropertyValueToLowercase implements TransformationFactoryInterface
{
    /**
     * @param array<string,string> $settings
     */
    public function build(array $settings, ContentRepository $contentRepository): GlobalTransformationInterface|NodeBasedTransformationInterface
    {
        return new class(
            $settings['property']
        ) implements NodeBasedTransformationInterface {

            private string $propertyName;

            public function __construct(string $propertyName)
            {
                $this->propertyName = $propertyName;
            }

            public function execute(Node $node, DimensionSpacePointSet $coveredDimensionSpacePoints, WorkspaceName $workspaceNameForWriting): TransformationStep {
                $currentProperty = $node->getProperty($this->propertyName);

                if ($currentProperty !== null && is_string($currentProperty)) {
                    $value = strtolower($currentProperty);

                    return TransformationStep::fromCommand(
                        SetNodeProperties::create(
                            $workspaceNameForWriting,
                            $node->aggregateId,
                            $node->originDimensionSpacePoint,
                            PropertyValuesToWrite::fromArray([
                                $this->propertyName => $value,
                            ])
                        )
                    );
                }

                return TransformationStep::createEmpty();
            }
        };
    }
}
