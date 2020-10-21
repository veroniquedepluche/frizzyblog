<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\User;
use App\Form\ArticleType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class BlogController extends AbstractController
{

	public function index()
	{
		$articles = $this->getDoctrine()->getRepository(Article::class)->findBy(
			['isPublished' => true],
			['publicationDate' => 'desc']
		);

		return $this->render('blog/index.html.twig', ['articles' => $articles]);
	}
	public function add(Request $request)
	{
		$article = new Article();
		$form = $this->createForm(ArticleType::class, $article);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$article->setLastUpdateDate(new \DateTime());

			if ($article->getPicture() !== null) {
				$file = $form->get('picture')->getData();
				$fileName =  uniqid(). '.' .$file->guessExtension();

				try {
					$file->move(
						$this->getParameter('images_directory'), // Le dossier dans le quel est le fichier va être chargé
						$fileName
					);
				} catch (FileException $e) {
					return new Response($e->getMessage());
				}

				$article->setPicture($fileName);
			}

			if ($article->getIsPublished()) {
				$article->setPublicationDate(new \DateTime());
			}

			$em = $this->getDoctrine()->getManager(); // On récupère l'entity manager
			$em->persist($article); // On confie notre entité à l'entity manager (on persist l'entité)
			$em->flush(); // On execute la requete

			return $this->redirectToRoute('admin');
		}

		return $this->render('blog/add.html.twig', [
			'form' => $form->createView()
		]);
	}


	public function show(Article $article)
	{
		return $this->render('blog/show.html.twig', [
			'article' => $article
		]);
	}

	public function edit(Article $article, Request $request)
	{
		$oldPicture = $article->getPicture();

		$form = $this->createForm(ArticleType::class, $article);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$article->setLastUpdateDate(new \DateTime());

			if ($article->getIsPublished()) {
				$article->setPublicationDate(new \DateTime());
			}

			if ($article->getPicture() !== null && $article->getPicture() !== $oldPicture) {
				$file = $form->get('picture')->getData();
				$fileName = uniqid(). '.' .$file->guessExtension();

				try {
					$file->move(
						$this->getParameter('images_directory'),
						$fileName
					);
				} catch (FileException $e) {
					return new Response($e->getMessage());
				}

				$article->setPicture($fileName);
			} else {
				$article->setPicture($oldPicture);
			}

			$em = $this->getDoctrine()->getManager();
			$em->persist($article);
			$em->flush();

			return $this->redirectToRoute('admin');
		}

		return $this->render('blog/edit.html.twig', [
			'article' => $article,
			'form' => $form->createView()
		]);
	}


	public function remove($id)
	{
		return new Response('<h1>Delete article: ' .$id. '</h1>');
	}

	public function admin()
	{
		$articles = $this->getDoctrine()->getRepository(Article::class)->findBy(
			[],
			['lastUpdateDate' => 'DESC']
		);

		$users = $this->getDoctrine()->getRepository(User::class)->findAll();

		return $this->render('admin/index.html.twig', [
			'articles' => $articles,
			'users' => $users
		]);
	}
}
