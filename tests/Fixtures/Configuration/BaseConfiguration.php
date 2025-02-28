<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Fixtures\Configuration;

use JG\BatchEntityImportBundle\Model\Configuration\AbstractImportConfiguration;
use JG\BatchEntityImportBundle\Tests\Fixtures\Entity\TestEntity;
use JG\BatchEntityImportBundle\Validator\Constraints\DatabaseEntityUnique;

class BaseConfiguration extends AbstractImportConfiguration
{
    public function getEntityClassName(): string
    {
        return TestEntity::class;
    }

    public function getMatrixConstraints(): array
    {
        return [
            new DatabaseEntityUnique([
                'entityClassName' => $this->getEntityClassName(),
                'fields' => [
                    'test_private_property',
                    'test_public_property',
                ],
            ]),
            new DatabaseEntityUnique([
                'entityClassName' => $this->getEntityClassName(),
                'fields' => [
                    'test-private-property2',
                ],
            ]),
        ];
    }
}
