<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ShoppingList;
use App\Entity\ShoppingListProduct;
use App\Entity\TodoItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-fixtures',
    description: 'Load sample data into the dashboard database (shopping lists & todos).',
)]
class LoadFixturesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'user-id',
            null,
            InputOption::VALUE_REQUIRED,
            'User ID (from auth-service) to assign fixtures to',
            1,
        );

        $this->addOption(
            'clear',
            null,
            InputOption::VALUE_NONE,
            'Drop all existing rows before inserting fixtures',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $userId = (int) $input->getOption('user-id');

        if ($input->getOption('clear')) {
            $this->clearData();
            $io->note('Existing data cleared.');
        }

        $io->section("Loading fixtures for user ID {$userId}");

        // ── Shopping Lists ───────────────────────────────────────
        $listsData = [
            [
                'name'     => 'Weekly Groceries',
                'products' => [
                    ['name' => 'Milk',    'qty' => 2, 'weight' => '1 L'],
                    ['name' => 'Bread',   'qty' => 1, 'weight' => '500 g'],
                    ['name' => 'Eggs',    'qty' => 12, 'weight' => null],
                    ['name' => 'Butter',  'qty' => 1, 'weight' => '250 g'],
                    ['name' => 'Apples',  'qty' => 6, 'weight' => null],
                ],
            ],
            [
                'name'     => 'BBQ Party',
                'products' => [
                    ['name' => 'Burgers',     'qty' => 8,  'weight' => '1 kg'],
                    ['name' => 'Hot-dog buns','qty' => 12, 'weight' => null],
                    ['name' => 'Ketchup',     'qty' => 2,  'weight' => '400 g'],
                    ['name' => 'Charcoal',    'qty' => 1,  'weight' => '3 kg'],
                ],
            ],
            [
                'name'     => 'Office Supplies',
                'products' => [
                    ['name' => 'Pens',       'qty' => 10, 'weight' => null],
                    ['name' => 'Notebooks',  'qty' => 3,  'weight' => null],
                    ['name' => 'Sticky notes', 'qty' => 4, 'weight' => null],
                ],
            ],
        ];

        foreach ($listsData as $listDef) {
            $list = new ShoppingList();
            $list->setName($listDef['name']);
            $list->setOwnerId($userId);
            $list->setCreatedBy($userId);
            $list->setUpdatedBy($userId);

            foreach ($listDef['products'] as $i => $pDef) {
                $product = new ShoppingListProduct();
                $product->setName($pDef['name']);
                $product->setQty($pDef['qty']);
                $product->setWeight($pDef['weight']);
                $product->setPosition($i);
                $product->setCreatedBy($userId);
                $product->setUpdatedBy($userId);
                $list->addProduct($product);
            }

            $this->em->persist($list);
        }

        // ── Todo Items ────────────────────────────────────────────
        $todos = [
            ['text' => 'Set up CI/CD pipeline',              'done' => false],
            ['text' => 'Write unit tests for auth service',  'done' => false],
            ['text' => 'Review pull requests',               'done' => true],
            ['text' => 'Update project README',              'done' => false],
            ['text' => 'Schedule team retrospective',        'done' => true],
            ['text' => 'Fix navbar responsive layout',       'done' => false],
            ['text' => 'Add pagination to shopping lists',   'done' => false],
        ];

        foreach ($todos as $todoDef) {
            $item = new TodoItem();
            $item->setText($todoDef['text']);
            $item->setDone($todoDef['done']);
            $item->setOwnerId($userId);
            $item->setCreatedBy($userId);
            $item->setUpdatedBy($userId);
            $this->em->persist($item);
        }

        $this->em->flush();

        $io->success(sprintf(
            'Loaded %d shopping list(s) and %d todo item(s) for user ID %d.',
            count($listsData),
            count($todos),
            $userId,
        ));

        return Command::SUCCESS;
    }

    private function clearData(): void
    {
        $conn = $this->em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $conn->executeStatement('TRUNCATE TABLE shopping_list_product');
        $conn->executeStatement('TRUNCATE TABLE shopping_list');
        $conn->executeStatement('TRUNCATE TABLE todo_item');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
