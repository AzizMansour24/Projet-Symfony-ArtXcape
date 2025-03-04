<?php

namespace App\Controller;

use App\Entity\Model3D;
use App\Form\Model3DType;
use App\Repository\Model3DRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Event;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

final class ModelController extends AbstractController
{
    #[Route('/admin/models', name: 'app_models_show')]
    public function show(Model3DRepository $model3DRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $models = $model3DRepository->findAll();
        
        // Create forms for each model for editing
        $forms = [];
        foreach ($models as $model) {
            $forms[$model->getId()] = $this->createForm(Model3DType::class, $model)->createView();
        }

        return $this->render('backOffice/events/modelsshow.html.twig', [
            'models' => $models,
            'forms' => $forms,
        ]);
    }

    #[Route('/admin/model/edit/{id}', name: 'app_model_edit')]
    public function edit(Request $request, Model3D $model, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Model3DType::class, $model);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Model updated successfully!'
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Error updating model'
        ]);
    }

    #[Route('/admin/model/delete/{id}', name: 'app_model_delete')]
    public function delete(Model3D $model, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($model);
        $entityManager->flush();

        return $this->redirectToRoute('app_models_show');
    }

    #[Route('/admin/model/new', name: 'app_model_new')]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response
    {
        $model = new Model3D();
        $form = $this->createForm(Model3DType::class, $model);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $modelFile = $form->get('fileUrl')->getData();

            if ($modelFile) {
                $originalFilename = pathinfo($modelFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$modelFile->guessExtension();

                try {
                    $modelFile->move(
                        $this->getParameter('models_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload the model file');
                    return $this->redirectToRoute('app_model_new');
                }

                $model->setFileUrl($newFilename);
            }

            $entityManager->persist($model);
            $entityManager->flush();

            $this->addFlash('success', 'Model added successfully!');
            return $this->redirectToRoute('app_models_show');
        }

        return $this->render('backOffice/events/modeladd.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
