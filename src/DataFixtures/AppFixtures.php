<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker;


class AppFixtures extends Fixture
{

    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('bap@bapfr');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->hasher->hashPassword($user, 'local'));

        $manager->persist($user);

        $faker = Faker\Factory::create('fr_FR');

        for($i = 0; $i <10; $i++){
            $user = new User();
            $user->setEmail($faker->email());
            $user->setPassword($this->hasher->hashPassword($user, 'local'));
            $manager->persist($user);
        }

        $product = new Product();
        $product->setName($faker->name());
        $product->setPrice($faker->randomFloat(2,5,30));
        $product->setIngredients($faker->text());
        $product->setImage($faker->image());

        $manager->persist($product);

        $manager->flush();


    }
}
