<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     * @Template()
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/shows-{id}", name="shows", defaults={"id": "1"})
     * @Template()
     */
    public function showsAction($id)
    {
        //nombre de résultats par page
        $nbResultats = 10;
        //nombre de résultats à ignorer (pagination)
        $offset = ($id - 1) * $nbResultats;

        //sélection des résultats à afficher
        $em = $this->get('doctrine')->getManager();
        $repo = $em->getRepository('AppBundle:TVShow');
        $shows = $repo->findBy(
            array(),
            array('name' => 'asc'),
            $nbResultats,
            $offset
        );

        //nombre de résultats totaux, pour déterminer le nombre de pagination
        $em = $this->getDoctrine()->getManager();
        $query_shows = $em->createQueryBuilder();
        $query_shows->select('count(s.id) AS somme')
            ->from('AppBundle:TVShow', 's');
        $nbResultatsTot = $query_shows->getQuery()->getSingleScalarResult();

        //nombre de pages
        $pageMax = ceil($nbResultatsTot / $nbResultats);

        //page de début de pagination
        $pageDebut = $id - 2;
        if($pageDebut <= 2) {
            $pageDebut = 1;
        }
        //page de fin de pagination
        $pageFin = $id + 2;
        if($pageFin >= $pageMax - 1){
            $pageFin = $pageMax;
        }



        return $this->render('AppBundle:Default:shows.html.twig', array(
            'shows' => $shows,
            'pageActive' => $id,
            'pageMax' => $pageMax,
            'pageDebut' => $pageDebut,
            'pageFin' => $pageFin
        ));
    }



    /**
     * @Route("/show/{id}", name="show")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->get('doctrine')->getManager();
        $repo = $em->getRepository('AppBundle:TVShow');

        return [
            'show' => $repo->find($id)
        ];        
    }



    /**
     * @Route("/search", name="search")
     * @Template()
     */
    public function searchAction(Request $request)
    {
        if ($request->getMethod() == "POST") {
            $search = $request->request->get('search');

            //essai pour prendre en compte les mots indépendemment
//            $datas = explode(" ", $search);
//
//            $em = $this->getDoctrine()->getManager();
//            $query_shows = $em->createQueryBuilder();
//            $query_shows
//                ->select('s')
//                ->from('AppBundle:TVShow', 's');
//            foreach ($datas as $data) {
//                $query_shows
//                    ->andWhere('s.name LIKE :data OR s.synopsis LIKE :data')
//                    ->setParameter('data', '%'.$data.'%');
//            }

            $em = $this->getDoctrine()->getManager();
            $query_shows = $em->createQueryBuilder();
            $query_shows
                ->select('s')
                ->from('AppBundle:TVShow', 's')
                ->where('s.name LIKE :data OR s.synopsis LIKE :data')
                ->setParameter('data', '%'.$search.'%');

            $shows = $query_shows->getQuery()->getResult();
        }

        return $this->render('AppBundle:Default:shows.html.twig', array(
            'shows' => $shows
        ));
    }



    /**
     * @Route("/calendar", name="calendar")
     * @Template()
     */
    public function calendarAction()
    {
        //date actuelle
        $dateTime = new \DateTime(); //date actuelle

        //à l'aide du repository
//        $em = $this->get('doctrine')->getManager();
//        $episodes = $em->getRepository('AppBundle:Episode')
//            ->getEpisodeComingSoon($dateTime);

        //sélection des épisodes à paraitre
        $em = $this->getDoctrine()->getManager();
        $query_episodes = $em->createQueryBuilder();
        $query_episodes->select('e')
            ->from('AppBundle:Episode', 'e')
            ->where('e.date >= :DateTime')
            ->orderBy('e.date', 'ASC')
            ->setParameter('DateTime', $dateTime);
        $episodes = $query_episodes->getQuery()->getResult();

        return $this->render('AppBundle:Default:calendar.html.twig', array(
            'episodes' => $episodes
        ));
    }

    /**
     * @Route("/login", name="login")
     * @Template()
     */
    public function loginAction()
    {
        return [];
    }
}
