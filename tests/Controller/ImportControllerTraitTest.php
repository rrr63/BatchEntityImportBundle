<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Controller;

use Doctrine\ORM\EntityRepository;
use JG\BatchEntityImportBundle\Exception\MatrixRecordInvalidDataTypeException;
use JG\BatchEntityImportBundle\Tests\DatabaseLoader;
use JG\BatchEntityImportBundle\Tests\Fixtures\Entity\TranslatableEntity;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ImportControllerTraitTest extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = self::createClient();

        $databaseLoader = self::$kernel->getContainer()->get(DatabaseLoader::class);
        $databaseLoader->reload();
    }

    public function testControllerWorksOk(): void
    {
        self::assertEmpty($this->getRepository()->findAll());
        $uploadedFile = __DIR__ . '/../Fixtures/Resources/test.csv';

        // insert new data
        $this->submitSelectFileForm($uploadedFile);
        $this->client->submitForm('btn-submit');
        $this->checkData('test2');

        // update existing data
        $this->submitSelectFileForm($uploadedFile);
        $this->client->submitForm('btn-submit', [
            'matrix' => [
                'records' => [
                    [
                        'entity' => 1,
                        'test_private_property' => 'abcd',
                    ],
                    [
                        'entity' => 2,
                        'test_private_property' => 'new_value',
                    ],
                ],
            ],
        ]);
        $this->checkData('new_value');
    }

    private function submitSelectFileForm(string $uploadedFile): void
    {
        $this->client->request('GET', '/jg_batch_entity_import_bundle/import');
        self::assertTrue($this->client->getResponse()->isSuccessful());

        $this->client->submitForm('btn-submit', ['file_import[file]' => $uploadedFile]);

        self::assertTrue($this->client->getResponse()->isSuccessful());
        self::assertEquals('/jg_batch_entity_import_bundle/import', $this->client->getRequest()->getRequestUri());
    }

    private function checkData(string $expectedValue): void
    {
        $repository = $this->getRepository();
        self::assertTrue($this->client->getResponse()->isRedirect('/jg_batch_entity_import_bundle/import'));
        $this->client->followRedirect();
        self::assertTrue($this->client->getResponse()->isSuccessful());
        self::assertStringContainsString('Data has been imported', $this->client->getResponse()->getContent());
        self::assertCount(2, $repository->findAll());

        /** @var TranslatableEntity|null $item */
        $item = $repository->find(2);
        self::assertNotEmpty($item);
        self::assertSame($expectedValue, $item->getTestPrivateProperty());
    }

    public function testImportFileWrongExtension(): void
    {
        $uploadedFile = __DIR__ . '/../Fixtures/Resources/test.txt';
        $this->submitSelectFileForm($uploadedFile);

        self::assertStringContainsString('Wrong file extension.', $this->client->getResponse()->getContent());
        self::assertStringContainsString('id="file_import_file"', $this->client->getResponse()->getContent());
    }

    public function testFlashMessage(): void
    {
        $uploadedFile = __DIR__ . '/../Fixtures/Resources/test_exception_invalid_type.csv';
        $this->submitSelectFileForm($uploadedFile);
        $this->client->submitForm('btn-submit');
        $this->client->followRedirect();
        self::assertStringContainsString('Invalid type of data. Probably missing validation.', $this->client->getResponse()->getContent());
    }

    private function getRepository(): EntityRepository
    {
        return self::$kernel->getContainer()->get('doctrine.orm.entity_manager')->getRepository(TranslatableEntity::class);
    }
}
