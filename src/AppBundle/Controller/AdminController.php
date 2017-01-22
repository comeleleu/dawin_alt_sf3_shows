<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Forms\ShowType;
use AppBundle\Forms\EpisodeType;
use AppBundle\Entity\TVShow;
use AppBundle\Entity\Season;
use AppBundle\Entity\Episode;
use aharen\OMDbAPI;

/**
 * @Route("/admin")
 */
class AdminController extends Controller
{
    /**
     * @Route("/addShow", name="admin_add_show")
     * @Template()
     */
    public function addShowAction(Request $request)
    {
    	$show = new TVShow;
    	$form = $this->createForm(ShowType::class, $show);
    	$success = false;

		$form->handleRequest($request);
    	if ($form->isSubmitted() && $form->isValid()) {
    		$file = $show->getImage();
    		if ($file) {
                // Handling file upload
    			$filename = md5(uniqid()).'.'.$file->guessExtension();
    			$webRoot = $this->get('kernel')->getRootDir().'/../web';

    			$file->move($webRoot . '/uploads', $filename);
    			$show->setImage($filename);
    		}

    		$em = $this->get('doctrine')->getManager();
    		$em->persist($show);
    		$em->flush();
    		$success = true;
    	}

        return [
        	'form' => $form->createView(),
        	'success' => $success
        ];
    }



    /**
     * @Route("/addSeason/{id}", name="admin_add_season")
     */
    public function addSeasonAction($id)
    {
        $em = $this->get('doctrine')->getManager();
        $repo = $em->getRepository('AppBundle:TVShow');

        if ($show = $repo->find($id)) {
            $season = new Season;
            $season
                ->setShow($show)
                ->setNumber(count($show->getSeasons())+1)
                ;
            $em->persist($season);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('show', ['id' => $id]));
    }



    /**
     * @Route("/deleteSeason/{id}", name="admin_delete_season")
     */
    public function deleteSeasonAction($id)
    {
        //sélection des résultats pour la saison
        $em = $this->get('doctrine')->getManager();
        $repo = $em->getRepository('AppBundle:Season');
        //si résultat
        if ($season = $repo->find($id)) {
            //sélection de l'id de la série (redirrection
            $show_id = $season->getShow()->getId();
            //supprimer la saison
            $em->remove($season);
            //sépection des épisodes de la saison
            $episodes = $season->getEpisodes();
            //supprimer chaque épisode
            foreach ($episodes as $episode) {
                $em->remove($episode);
            }
            //exécution des requetes
            $em->flush();
            //retour sur la page de la série
            return $this->redirect($this->generateUrl('show', ['id' => $show_id]));
        } else {
            return $this->redirect($this->generateUrl('homepage'));
        }
    }



    /**
     * @Route("/addEpisode/{id}", name="admin_add_episode")
     * @Template()
     */
    public function addEpisodeAction($id, Request $request)
    {
        $em = $this->get('doctrine')->getManager();
        $repo = $em->getRepository('AppBundle:Season');

        if ($season = $repo->find($id)) {
            $episode = new Episode;
            $episode
                ->setSeason($season)
                ->setNumber(count($season->getEpisodes())+1)
                ;

            $form = $this->createForm(EpisodeType::class, $episode);

            $form->handleRequest($request);
        	if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($episode);
                $em->flush();
                return $this->redirect($this->generateUrl('show',[
                    'id' => $episode->getSeason()->getShow()->getId()
                ]));
            }
        } else {
            return $this->redirect($this->generateUrl('homepage'));
        }

        return [
            'form' => $form->createView()
        ];
    }



    /**
     * @Route("/deleteEpisode/{id}", name="admin_delete_episode")
     */
    public function deleteEpisodeAction($id)
    {
        $em = $this->get('doctrine')->getManager();
        $repo = $em->getRepository('AppBundle:Episode');
        if ($episode = $repo->find($id)) {
            $id = $episode->getSeason()->getShow()->getId();
            $em->remove($episode);
            $em->flush();
            return $this->redirect($this->generateUrl('show', ['id' => $id]));
        } else {
            return $this->redirect($this->generateUrl('homepage'));
        }
    }



    /**
     * @Route("/omdb", name="admin_omdb")
     * @Template()
     */
    public function omdbAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('keyword')
            ->getForm()
        ;

        $result = [];
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $omdb = new OMDbAPI();
            $result = $omdb->search($data['keyword']);
            $result = $result->data->Search;
        }

        return [
            'form' => $form->createView(),
            'result' => $result
        ];
    }



    /**
     * @Route("/omdb-import-{id}", name="admin_omdb_import_show")
     * @Template()
     */
    public function omdbImportShowAction($id)
    {
        $omdb = new OMDbAPI();
        $result = $omdb->fetch('i', $id);
        if ($result != null) {

            $show = new TVShow();
            $show
                ->setName($result->data->Title)
                ->setSynopsis($result->data->Plot)
                ->setImage($result->data->Poster)
                ->setImage($id.".jpg");
            $image = file_get_contents($result->data->Poster);
            file_put_contents(__DIR__."/../../../web/uploads/".$id.".jpg", $image);


            //essaie d'ajout des saisons et episodes
//            for($numSeason=1; $numSeason <= $result->data->totalSeasons; $numSeason++) {
//                $s = $omdb->fetch('i', $id, ['Season' => $numSeason]);
//                $season = new Season();
//                $season
//                    ->setNumber($numSeason);
//                $show
//                    ->addSeason($season);
//
//                foreach ($s->data->Episodes as $e) {
//                    $episode = new Episode();
//                    $episode
//                        ->setNumber($e->Episode);
//                    if("N/A" != $e->Released)
//                        $episode
//                            ->setDate(new \Datetime($e->Released))
//                            ->setName($e->Title);
//                    $season
//                        ->addEpisode($episode);
//                }
//            }

            $em = $this->get('doctrine')->getManager();
            $em->persist($show);
            $em->flush();

            return $this->redirect($this->generateUrl('show', array('id' => $show->getId())));
        }
        return $this->redirect($this->generateUrl('shows'));
    }

}
