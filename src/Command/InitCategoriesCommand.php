<?php

namespace App\Command;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:categories:init', description: 'Create default product categories if they do not exist')]
class InitCategoriesCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private CategoryRepository $categoryRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $names = [
            'Romantic',
            'Celebrations',
            'Sympathy & Support',
            'Festive & Holidays',
            'Special Moments',
            'Corporate',
        ];

        $created = 0;

        foreach ($names as $name) {
            $existing = $this->categoryRepository->findOneBy(['name' => $name]);
            if ($existing) {
                $output->writeln(sprintf('<comment>Skipping existing category: %s</comment>', $name));
                continue;
            }

            $category = new Category();
            $category->setName($name);
            $this->em->persist($category);
            $created++;
            $output->writeln(sprintf('<info>Created category: %s</info>', $name));
        }

        if ($created > 0) {
            $this->em->flush();
            $output->writeln(sprintf('<info>Created %d categories.</info>', $created));
        } else {
            $output->writeln('<info>No categories created. All already exist.</info>');
        }

        return Command::SUCCESS;
    }
}
