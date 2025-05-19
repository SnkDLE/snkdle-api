<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Song;
use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\Character;
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
        // Create characters
        $characters = $this->loadCharacters($manager);
        
        // Create quizzes
        $quizzes = $this->loadQuizzes($manager);
        
        // Create questions
        $this->loadQuestions($manager, $characters);
        
        $manager->flush();
    }
    
    private function loadCharacters(ObjectManager $manager): array
    {
        $characters = [];
        $species = ['Human', 'Titan', 'Ackerman', 'Eldian', 'Marleyan'];
        $statuses = ['Alive', 'Deceased', 'Unknown'];
        $genders = ['Male', 'Female'];
        
        // Create 20 characters
        for ($i = 0; $i < 20; $i++) {
            $character = new Character();
            $character->setName($this->faker->name());
            $character->setImage('https://example.com/image' . $i . '.jpg');
            $character->setSpecies([$this->faker->randomElement($species)]);
            $character->setGender($this->faker->randomElement($genders));
            $character->setAge($this->faker->numberBetween(15, 60));
            $character->setStatus($this->faker->randomElement($statuses));
            
            $manager->persist($character);
            $characters[] = $character;
        }
        
        return $characters;
    }
    
    private function loadQuizzes(ObjectManager $manager): array
    {
        $quizzes = [];
        
        // Create 10 quizzes with dates over the past 10 days
        for ($i = 0; $i < 10; $i++) {
            $quiz = new Quiz();
            $date = new \DateTime();
            $date->modify('-' . $i . ' days');
            $quiz->setDate($date);
            
            $manager->persist($quiz);
            $quizzes[] = $quiz;
        }
        
        return $quizzes;
    }
    
    private function loadQuestions(ObjectManager $manager, array $characters): void
    {
        $questionTypes = ['identity', 'appearance', 'history'];
        
        // Create 30 questions (3 for each character from the first 10 characters)
        for ($i = 0; $i < 10; $i++) {
            foreach ($questionTypes as $type) {
                $question = new Question();
                $question->setType($type);
                $question->setExternalCharacterId($i + 1); // Using 1-indexed ID
                
                // Different data based on question type
                switch ($type) {
                    case 'identity':
                        $question->setCorrectAnswer($characters[$i]->getName());
                        $question->setPromptData('Who is this character?');
                        break;
                    case 'appearance':
                        $question->setCorrectAnswer($characters[$i]->getSpecies()[0]);
                        $question->setPromptData('What species is this character?');
                        break;
                    case 'history':
                        $question->setCorrectAnswer($characters[$i]->getStatus());
                        $question->setPromptData('What is the status of this character?');
                        break;
                }
                
                $manager->persist($question);
            }
        }
    }
}
