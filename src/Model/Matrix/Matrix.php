<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Model\Matrix;

use JG\BatchEntityImportBundle\Service\PropertyExistenceChecker;
use Symfony\Component\Validator\Constraints as Assert;

use const ARRAY_FILTER_USE_KEY;

class Matrix
{
    private const RESERVED_ENTITY_COLUMN_NAME = 'entity';
    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Type('string'),
        new Assert\Regex(pattern: "/^([\w -]+)(:[\w]+)?$/", message: 'validation.matrix.header.name'),
    ])]
    #[Assert\NotBlank]
    private readonly array $header;
    /**
     * @var array|MatrixRecord[]
     */
    #[Assert\All([
        new Assert\Type(MatrixRecord::class),
    ])]
    #[Assert\NotBlank]
    private array $records = [];

    public function __construct(array $header = [], array $recordsData = [])
    {
        $this->header = $this->clearHeader($header);

        foreach ($recordsData as $data) {
            $data = $this->clearRecordData($data);
            if ($data) {
                $this->records[] = new MatrixRecord($data);
            }
        }
    }

    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * @return array|MatrixRecord[]
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    public function getHeaderInfo(string $className): array
    {
        $info = [];
        $checker = new PropertyExistenceChecker($className);

        foreach ($this->header as $name) {
            $info[$name] = $checker->propertyExists($name);
        }

        return $info;
    }

    private function clearHeader(array $header): array
    {
        $header = array_values(
            array_filter($header, fn (?string $columnName): bool => $this->isColumnNameValid($columnName)),
        );

        return \array_map(static fn (string $name) => \str_replace(' ', '_', $name), $header);
    }

    private function clearRecordData(array $data): array
    {
        return array_filter($data, fn (?string $columnName): bool => $this->isColumnNameValid($columnName), ARRAY_FILTER_USE_KEY);
    }

    private function isColumnNameValid(?string $name): bool
    {
        return !empty(trim((string) $name)) && self::RESERVED_ENTITY_COLUMN_NAME !== $name;
    }
}
