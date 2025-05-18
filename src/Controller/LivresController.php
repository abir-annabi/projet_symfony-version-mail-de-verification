<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Entity\Livres;
use App\Form\LivresType;
use Doctrine\ORM\EntityManager;
use App\Repository\LivresRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class LivresController extends AbstractController
{


    #[Route('/home', name: 'home')]
    public function home(): Response
    {
       
        return $this->render('livres/Home.html.twig');
    }

    #[Route('/admin/livre/delete/{id}', name: 'app_livre_delete')]
    public function delete(LivresRepository $rep, Livres $livre):Response
    {
  //$livre=$rep->find($id);
  $rep->remove($livre);
  $rep->flush();
        dd($livre);// livre supprimé = id null
    }


    #[Route('/admin/livres', name: 'app_livre_all')]
    public function all(LivresRepository $rep, PaginatorInterface $paginator, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $query = $rep->createQueryBuilder('l')->getQuery();
        $livres = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            8
        );
        
        return $this->render('livres/all.html.twig', [
            'livres' => $livres
        ]);
    }


   #[Route('/livres', name: 'app_livre_all1')]
public function all1(LivresRepository $rep, PaginatorInterface $paginator, Request $request): Response
{
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    
    $user = $this->getUser();
    $query = $rep->createQueryBuilder('l')
        ->leftJoin('l.favoris', 'f', 'WITH', 'f.user = :user')
        ->addSelect('f')
        ->setParameter('user', $user)
        ->getQuery();
    
    $livres = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        8
    );
    
    return $this->render('livres/index.html.twig', [
        'livres' => $livres
    ]);
}




    #[Route('/admin/livre/show3', name: 'app_livre_show3')]
    public function show3(LivresRepository $rep):Response
    {
        $livre=$rep->findBy(['titre'=>'titre 1'],['editeur'=>'editeur 1'],/*['prix'=>'ASC']*/);
        if(!$livre){
            throw $this->createNotFoundException('le livre n\'existe pas');
        }
        dd($livre);
    }


    #[Route('/admin/livre/show2', name: 'app_livre_show2')]
    public function show2(LivresRepository $rep):Response
    {
        $livre=$rep->findOneBy(['titre'=>'titre 1']);
        if(!$livre){
            throw $this->createNotFoundException('le livre n\'existe pas');
        }
        dd($livre);
    }


//paramconverter
    #[Route('/admin/livre/show/{id}', name: 'app_livre_show')]
    public function show(Livre $livre):Response
    {
        
        if(!$livre){
            throw $this->createNotFoundException("le livre ayant id : {$livre->getId()} n\'existe pas");
        }
        return $this->render('livres/show.html.twig', ['livre'=>$livre]);    }    


    

  /*  #[Route('/admin/livre', name: 'app_livre_create')]
    public function create(EntityManagerInterface $em):Response //EntityManager ($em) qui sert à gérer les entités Doctrine (création, mise à jour, suppression, etc.).
    {
        $livre=new Livres();
        $livre->setTitre('titre 1')
              ->setSlug('slug 1')
              ->setImage('https://images.app.goo.gl/P42ziByspY6PYLh97')
              ->setResume('Lorem ipsum dolor sit amet consectetur adipisicing elit. Asperiores, umque deleniti harum? Blanditiis a cupiditate ipsum dignissimos quos!')
              ->setEditeur('editeur 1')
              ->setDateEdition(new \DateTime(datetime: "2024-03-10"))
              ->setPrix(12.5)
              ->setIsbn('599-548-1486-45');
              $em->persist($livre);//ne permet pas d'inserer dans la BD
              $em->flush();// permet  d'inserer dans la BD
              dd($livre);

    }*/


    #[Route('/livres/search', name: 'app_livre_search')]
public function search(Request $request, LivresRepository $rep): Response
{
    $searchTerm = $request->query->get('q', '');
    
    $livres = $rep->createQueryBuilder('l')
        ->where('l.titre LIKE :searchTerm')
        ->orWhere('l.editeur LIKE :searchTerm')
        ->orWhere('l.isbn LIKE :searchTerm')
        ->orWhere('l.resume LIKE :searchTerm')
        ->leftJoin('l.categorie', 'c')
        ->orWhere('c.libelle LIKE :searchTerm')
        ->setParameter('searchTerm', '%'.$searchTerm.'%')
        ->getQuery()
        ->getResult();
    
    return $this->render('livres/_search_results.html.twig', [
        'livres' => $livres
    ]);
}
#[Route('/livres/filter-by-price', name: 'app_livre_filter_price')]
public function filterByPrice(Request $request, LivresRepository $rep): Response
{
    $minPrice = $request->query->get('minPrice');
    $maxPrice = $request->query->get('maxPrice');

    $livres = $rep->createQueryBuilder('l')
        ->where('l.prix >= :minPrice')
        ->andWhere('l.prix <= :maxPrice')
        ->setParameter('minPrice', $minPrice)
        ->setParameter('maxPrice', $maxPrice)
        ->getQuery()
        ->getResult();

    return $this->render('livres/_search_results.html.twig', [
        'livres' => $livres
    ]);
}


    #[Route('admin/Livres/create', name: 'app_Livres_create')]
    public function create(EntityManagerInterface $em,Request $request): Response
    {
        $livre=new Livres();
        $form=$this->createForm(LivresType::class,$livre);
        $form->handleRequest($request); //yasmaa ken fama des données soumises ou non __ Cette ligne permet à Symfony de remplir l’objet $categorie avec les données soumises 
        if($form->isSubmitted() && $form->isValid()){
            //dd($categorie);
            $em->persist($livre);
            $em->flush();
            $this->addFlash('success',"le livre a été ajoutée avec succées");
            //$this->addFlash('success',"un mail est envoyée a l'admin");
            return $this->redirectToRoute('admin/livres');
        }


        return $this->render('livres/create.html.twig',['form'=>$form]);
        
    }

    #[Route('/admin/livre/edit/{id}', name: 'app_livre_edit')]
public function edit(Livre $livre, Request $request, EntityManagerInterface $em): Response
{
    $form = $this->createForm(LivresType::class, $livre);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        $this->addFlash('success', 'Le livre a été modifié avec succès.');
        return $this->redirectToRoute('app_livre_all');
    }

    return $this->render('livres/edit.html.twig', [
        'form' => $form->createView(),
        'livre' => $livre
    ]);
}


    #[Route('/toggle-favori/{id}', name: 'toggle_favori')]
public function toggleFavori(Livre $livre, EntityManagerInterface $em): Response
{
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    $user = $this->getUser();
    
    $favori = $em->getRepository(Favori::class)->findOneBy([
        'user' => $user,
        'livre' => $livre
    ]);
    
    if ($favori) {
        // Supprimer le favori s'il existe
        $em->remove($favori);
        $em->flush();
        $this->addFlash('success', 'Livre retiré de vos favoris');
    } else {
        // Ajouter le favori s'il n'existe pas
        $favori = new Favori();
        $favori->setUser($user);
        $favori->setLivre($livre);
        $favori->setCreatedAt(new \DateTime());
        
        $em->persist($favori);
        $em->flush();
        $this->addFlash('success', 'Livre ajouté à vos favoris');
    }
    
    return $this->redirectToRoute('app_livre_all1');
}

}