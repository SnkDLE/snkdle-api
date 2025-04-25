<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Song;
use Faker\Generator;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    private Generator $faker;

    public function __construct(){
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        // for ($index = 0; $index < 5; $index++) {
        //     $song = new Song();
        //     $song->setName($this->faker->sentence(3));
        //     $song->setArtiste($this->faker->name());

        //     $manager->persist($song);
        // }
        // $manager->flush();
    }
}
