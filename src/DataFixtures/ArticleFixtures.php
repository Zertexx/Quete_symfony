<?php
/**
 * Created by PhpStorm.
 * User: zerto
 * Date: 30/05/2019
 * Time: 18:07
 */

namespace App\DataFixtures;


use App\Entity\Article;
use App\Service\Slugify;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker;


class ArticleFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies()
    {
        return [CategoryFixtures::class];
    }


    public function load(ObjectManager $manager)
    {
        $slugify = new Slugify();
        for ($i = 0; $i < 10; $i++)
        {
        $faker  =  Faker\Factory::create('fr_FR');
        $article = new Article();
            $article->setTitle(mb_strtolower($faker->sentence()));
            $article->setContent(mb_strtolower($faker->text()));
            $article->setSlug($slugify->generate($article->getTitle()));
            $article = $article->setTitle($article->getSlug());
            $manager->persist($article);

            $article->setCategory($this->getReference('categorie_' . rand(0 , 4)));

            $manager->flush();
        }
    }

}