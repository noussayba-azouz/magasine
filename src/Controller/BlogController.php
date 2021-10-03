<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;


class BlogController extends AbstractController
{

    // fonction métier pour chercher tous les articles dans le repository

    /**
     * @Route("/blog", name="blog")
     */
    public function index(ArticleRepository $repo): Response
    {
        $articles = $repo->findAll();

        return $this->render('blog/index.html.twig', [
            'controller_name' => 'BlogController',
            'articles' => $articles
        ]);
    }

    //fonction métier pour trier les articles selon l'ordre aphabétique

    /**
     * @Route("/search", name="search_blog")
     */
    public function search(ArticleRepository $repo): Response
    {
        $articles = $repo->findBy(array(), array('title' => 'ASC'));

        return $this->render('blog/search.html.twig', [
            'controller_name' => 'BlogController',
            'articles' => $articles
        ]);
    }
    
   // /**
    // * @Route("/searchCategory", name="search_category")
    // */
    //public function searchCategory(ArticleRepository $repo, CategoryRepository $category): Response
    //{
        
       // $articles = $repo->findBy(array('category' => 'Sed sed distinctio eveniet delectus minima.'), array('title' => 'ASC'));

       // return $this->render('blog/search.html.twig', [
          //  'controller_name' => 'BlogController',
           // 'articles' => $articles
       // ]);
   // }
    



    /**
     * @Route("/", name="home")
     */

    public function home(){

        return $this->render('blog/home.html.twig', [
            'title' => "Magasine Scolaire"
        ]);
    }

    //fonction CRUD pour ajouter un nouveau article
    //fonction CRUD pour modifier un article

    /**
     * @Route("/blog/new", name="blog_create")
     * @Route("/blog/{id}/edit", name="blog_edit")
     */
       
     //le parameterConverter
    public function addArticle(Article $article = null, Request $request, EntityManagerInterface $manager){

    if(!$article){                                          //Création d'un nouveau article s'il n'existe pas deja
           $article = new Article();                        //totalement vide
    }   

        //le reste est le meme code soit pour l'ajout soit pour la modification

        $form = $this->createFormBuilder($article)          //a cette étape il est non configuré
                     ->add('title')                         //Ajouter des champs
                     ->add('category', EntityType::class, [ //Ajouter des infos de plus car c'est une relation
                         'class' => Category::class,
                         'choice_label' => 'title'
                     ])
                     ->add('content')
                     ->add('image')  
                     ->getForm();

              // Création d'un objet form complexe        

        $form->handleRequest($request); //s'occuper des données introduites: 
               //analyser la requette = les associer aux champs de l'article
        if($form->isSubmitted() && $form->isValid()) {
            if(!$article->getId()){
                //date créée seulement lorsque l'article est créé ET qu'il n'existe pas déjà
                $article->setCreatedAt(new \DateTime()); 
            }
            
            $manager->persist($article);
            $manager->flush();

            //se rediriger vers la page contenant le nouveau article lui meme 

            return $this->redirectToRoute('autre_blog', ['id' => $article->getId()]);

        }            
  
        return $this->render('blog/create.html.twig', [ 
            'formArticle' => $form->createView(),     //c'est la version "simple" de lobjet complexe form
            'editMode'    => $article->getId() !== null //boolean pour vérifier si l'article existe ou nn
            //editMode à utiliser dans fichier twig pour changer le texte de bouton submit, les titres ... 
        ]);
    }

    // CRUD pour supprimer un article

    /**
     * @Route("/blog/{id}/delete", name="delete_blog")
     */

    public function deleteArticle(int $id): Response
    {

        $entityManager = $this->getDoctrine()->getManager();
        $article = $entityManager->getRepository(Article::class)->find($id);
        $entityManager->remove($article);
        $entityManager->flush();
        return $this->redirectToRoute("blog");


    }
    
    //Ajout de commentaire pour chaque article avec le nom de l'auteur, la date ...

    /**
     * @Route("/blog/{id}", name="autre_blog")
     */

    public function autre(Article $article, Request $request, EntityManagerInterface $manager){
        $comment = new Comment();

        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            
            $manager->setCreatedAt(new \DateTime())
                    ->setArticle($article);
            $manager->persist($comment);
            $manager->flush();
            return $this->redirectToRoute('autre_blog', [
                'id' => $article->getId()
            ]);

        }
  
        return $this->render('blog/autre.html.twig', [

            'article' => $article,
            'commentForm' => $form->createView()
            
        ]);
    }


}
