<?php

namespace NewsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use TechEventBundle\Entity\Article;
use TechEventBundle\Entity\Domain;
use TechEventBundle\Entity\Saved;
use TechEventBundle\Entity\User;

class SaveController extends Controller
{
    public function addBookmarkAction($id) {
        $article=$this->getDoctrine()->getRepository(Article::class)->find($id);
        $user=$this->getUser();
        $save=new Saved();
        $save->setUser($user);
        $save->setArticle($article);
        $save->setDate_Save(new \DateTime());
        $em = $this->getDoctrine()->getManager();
        $em->persist($save);
        $em->flush();
        $addToBookmark=array();
        if($this->getUser()) {
            $addToBookmark=$this->getDoctrine()->getRepository(Saved::class)->IAddedItBefore($id, $this->getUser()->getId());
        }
        return $this->render('@News/Article/front/article.html.twig', array(
            'article'=>$article,
            'added'=>sizeof($addToBookmark)
        ));
    }

    public function showBookmarksAction(Request $request) {
        $articles=$this->getDoctrine()->getRepository(Saved::class)->findSavedByUserId($this->getUser()->getId());
        $domains=$this->getDoctrine()->getRepository(Domain::class)->findAll();
        $paginator  = $this->get('knp_paginator');
        $res = $paginator->paginate(
            $articles, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            10/*limit per page*/
        );
        return $this->render('@News/Article/front/show.html.twig', array(
            'articles'=>$res,
            'domains'=>$domains,
            'bookmark'=>1
        ));
    }

    public function searchFrontBookmarkAction(Request $request) {
        $domain=$request->get('domain');
        $orderBy=$request->get('orderBy');
        $keyword=$request->get('keyword');
        $articles=$this->getDoctrine()->getRepository(Saved::class)->findByDomainKeywordAndOrderBy($domain, $keyword, $orderBy, $this->getUser()->getId());
        $paginator  = $this->get('knp_paginator');
        $res = $paginator->paginate(
            $articles, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            10/*limit per page*/
        );
        $domains = $this->getDoctrine()->getRepository(Domain::class)->findAll();
        return $this->render('@News/Article/front/show.html.twig', array(
            'articles'=>$res,
            'domains'=>$domains,
            'domain'=>$domain,
            'orderBy'=>$orderBy,
            'keyword'=>$keyword,
            'bookmark'=>1
        ));
    }

    public function removeBookmarkAction(Request $request, $id){
        $saved=$this->getDoctrine()->getRepository(Saved::class)->IAddedItBeforeObj($id, $this->getUser()->getId());
        $em = $this->getDoctrine()->getManager();
        $em->remove(current($saved));
        $em->flush();
        $article=$this->getDoctrine()->getRepository(Article::class)->find($id);
        $addToBookmark=array();
        if($this->getUser()) {
            $addToBookmark=$this->getDoctrine()->getRepository(Saved::class)->IAddedItBefore($id, $this->getUser()->getId());
        }
        return $this->render('@News/Article/front/article.html.twig', array(
            'article'=>$article,
            'added'=>sizeof($addToBookmark)
        ));
    }


    //mobile

    public function saveBookmarkAction($idArticle, $idUser) {
        $article=$this->getDoctrine()->getRepository(Article::class)->find($idArticle);
        $user=$this->getDoctrine()->getRepository(User::class)->find($idUser);
        $save=new Saved();
        $save->setUser($user);
        $save->setArticle($article);
        $save->setDate_Save(new \DateTime());
        $em = $this->getDoctrine()->getManager();
        $em->persist($save);
        $em->flush();
        $serializer = new Serializer([new ObjectNormalizer()]);
        $formatted = $serializer->normalize($save);
        return new JsonResponse($formatted);
    }

    public function unsaveBookmarkAction($idArticle, $idUser){
        $saved=$this->getDoctrine()->getRepository(Saved::class)->IAddedItBeforeObj($idArticle, $idUser);
        $em = $this->getDoctrine()->getManager();
        $em->remove(current($saved));
        $em->flush();
        $serializer = new Serializer([new ObjectNormalizer()]);
        $formatted = $serializer->normalize($saved);
        return new JsonResponse($formatted);
    }


    public function allBookmarksAction($idUser) {
        $articles=$this->getDoctrine()->getRepository(Saved::class)->findSavedByUserId($idUser);
        $serializer = new Serializer([new ObjectNormalizer()]);
        $formatted = $serializer->normalize($articles);
        return new JsonResponse($formatted);
    }
}
