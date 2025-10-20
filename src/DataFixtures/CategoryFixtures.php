<?php
namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // List of predefined categories
        $category = [
            'Romantic',
            'Celebrations',
            'Sympathy & Support',
            'Festive & Holidays',
            'Special Moments',
            'Corporate'
        ];

        foreach ($category as $name) {
            $category = new Category();
            $category->setName($name);
            $manager->persist($category);
        }

        $manager->flush();
    }
}
