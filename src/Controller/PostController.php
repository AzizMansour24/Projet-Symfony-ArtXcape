<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Comment;
use App\Entity\Like;
use App\Entity\Reaction;
use App\Form\PostType;
use App\Form\CommentType;
use App\Repository\PostRepository;
use App\Repository\LikeRepository;
use App\Repository\ReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Repository\CommentRepository;
use App\Service\ContentFilterService;
use App\Entity\BadWord;
use App\Repository\BadWordRepository;
use App\Form\BadWordType;

#[Route('/forum')]
class PostController extends AbstractController
{
    private $contentFilter;

    public function __construct(ContentFilterService $contentFilter)
    {
        $this->contentFilter = $contentFilter;
    }

    #[Route('/back', name: 'forum_back', methods: ['GET'])]
    public function forum_back(PostRepository $postRepository)
    {
        $posts = $postRepository->findBy([], ['createdAt' => 'DESC']);
        $mostLikedPosts = $postRepository->findMostLikedPosts(3);
        
        return $this->render('backOffice/forum/ForumBack.html.twig', [
            'posts' => $posts,
            'mostLikedPosts' => $mostLikedPosts,
            'controller_name' => 'BackController',
        ]);
    }
    #[Route('/comments/back', name: 'comments_back', methods: ['GET'])]
    public function commentsBack(CommentRepository $commentRepository): Response
    {
        $comments = $commentRepository->findBy([], ['createdAt' => 'DESC']);
        return $this->render('backOffice/forum/CommentBack.html.twig', [
            'comments' => $comments,
        ]);
    }
    #[Route('/', name: 'forum_index', methods: ['GET', 'POST'])]
    public function index(Request $request, PostRepository $postRepository, EntityManagerInterface $em): Response
    {
        $post = new Post();
        $post->setAuthor($this->getUser());
        
        $postForm = $this->createForm(PostType::class, $post);
        $postForm->handleRequest($request);
    
        if ($postForm->isSubmitted() && $postForm->isValid()) {
            try {
                // Filtrer le contenu avant de le sauvegarder
                $filteredTitle = $this->contentFilter->filterContent($post->getTitle());
                $filteredContent = $this->contentFilter->filterContent($post->getContent());
                
                $post->setTitle($filteredTitle);
                $post->setContent($filteredContent);

                // Handle file upload
                $imageFile = $postForm->get('imageFile')->getData();
                if ($imageFile) {
                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    $post->setImage($newFilename);
                }
    
                $em->persist($post);
                $em->flush();
    
                $this->addFlash('success', 'Post added successfully.');
                return $this->redirectToRoute('forum_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while creating the post.');
            }
        }
    
        $posts = $postRepository->findBy([], ['createdAt' => 'DESC']);
        return $this->render('post/index.html.twig', [
            'postForm' => $postForm->createView(),
            'posts' => $posts,
        ]);
    }
    
    #[Route('/{id}/update-inline', name: 'forum_update_inline', methods: ['POST'])]
    public function updateInline(Post $post, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if ($user !== $post->getAuthor()) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        $form=$this->createForm(PostType::class,$post,[
            'csrf_protection'=>false,
        ]);
        $data=array_merge($request->request->all(),$request->files->all());
        $form->submit($data);
        if(!$form->isValid()){
            $errors=[];
            foreach($form->getErrors(true) as $error){
                $fieldName=$error->getOrigin()->getName();
                $errors[$fieldName][]=$error->getMessage();
            }
            return new JsonResponse(['errors'=>$errors], Response::HTTP_BAD_REQUEST);
        }


        

        $imageFile = $request->files->get('imageFile');
        if ($imageFile) {
            $newFilename = uniqid().'.'.$imageFile->guessExtension();
            try {
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                return new JsonResponse(['error' => 'Failed to upload image.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $post->setImage($newFilename);
        }

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'title'   => $post->getTitle(),
            'content' => nl2br($post->getContent()),
            'image'   => $post->getImage() ? '/uploads/images/' . $post->getImage() : null
        ]);
    }


    
    #[Route('/{id}/delete', name: 'forum_delete', methods: ['POST'])]
    public function delete(Post $post, Request $request, EntityManagerInterface $em): Response
    {
        // Only allow the author to delete the post.
        if ($this->getUser() !== $post->getAuthor()) {
            $this->addFlash('error', 'You are not authorized to delete this post.');
            return $this->redirectToRoute('forum_index');
        }
    
        // Validate CSRF token
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $em->remove($post);
            $em->flush();
    
            $this->addFlash('success', 'Post deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }
    
        return $this->redirectToRoute('forum_index');
    }
    #[Route('/{id}/delete/back', name: 'forum_delete_back', methods: ['POST'])]
    public function delete_back(Post $post, Request $request, EntityManagerInterface $em): Response
    {
        // Only allow the author to delete the post.
        if ($this->getUser() !== $post->getAuthor()) {
            $this->addFlash('error', 'You are not authorized to delete this post.');
            return $this->redirectToRoute('forum_index');
        }
    
        // Validate CSRF token
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $em->remove($post);
            $em->flush();
    
            $this->addFlash('success', 'Post deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }
    
        return $this->redirectToRoute('forum_back');
    }
    #[Route('/comment/{id}/delete/back', name: 'comment_delete_back', methods: ['POST'])]
    public function deleteCommentBack(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        // Only allow the comment author to delete the comment
        if ($this->getUser() !== $comment->getAuthor()) {
            $this->addFlash('error', 'You are not authorized to delete this comment.');
            return $this->redirectToRoute('comments_back');
        }

        // Validate CSRF token
        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Comment deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('comments_back');
    }
    
    #[Route('/{id}/like', name: 'forum_like', methods: ['POST'])]
    public function like(Post $post, LikeRepository $likeRepository, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'You must be logged in.'], Response::HTTP_FORBIDDEN);
        }
    
        $existingLike = $likeRepository->findOneBy([
            'post' => $post,
            'user' => $user,
        ]);
    
        if ($existingLike) {
            $em->remove($existingLike);
            $em->flush();
            return new JsonResponse(['liked' => false, 'likeCount' => count($post->getLikes())]);
        } else {
            $like = new Like();
            $like->setPost($post);
            $like->setUser($user);
            $em->persist($like);
            $em->flush();
            return new JsonResponse(['liked' => true, 'likeCount' => count($post->getLikes())]);
        }
    }
    
    #[Route('/{id}/comment', name: 'forum_comment', methods: ['POST'])]
    public function comment(Post $post, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'You must be logged in to comment.'], Response::HTTP_FORBIDDEN);
        }

        $comment = new Comment();
        
        // Get the raw data from the request
        $content = $request->request->get('content');
        $gifUrl = $request->request->get('gifUrl');
        
        // Set the data
        $comment->setContent($content);
        $comment->setGifUrl($gifUrl);

        // Validate that either content or gifUrl is provided
        if (empty($content) && empty($gifUrl)) {
            $this->addFlash('error', 'Please provide either a comment or a GIF.');
            return $this->redirectToRoute('forum_index');
        }

        // Set the author and post
        $comment->setAuthor($user);
        $comment->setPost($post);

        // Filter content if present
        if ($content) {
            $filteredContent = $this->contentFilter->filterContent($content);
            $comment->setContent($filteredContent);
        }

        try {
            // Save the comment
            $em->persist($comment);
            $em->flush();
            $this->addFlash('success', 'Comment added successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while saving the comment.');
        }

        return $this->redirectToRoute('forum_index');
    }

    #[Route('/{id}/react', name: 'forum_react', methods: ['POST'])]
    public function react(Post $post, Request $request, EntityManagerInterface $em, ReactionRepository $reactionRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'You must be logged in.'], Response::HTTP_FORBIDDEN);
        }

        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('react', $submittedToken)) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $type = $request->request->get('type');
        $emoji = $request->request->get('emoji');

        if (!$type || !$emoji) {
            return new JsonResponse(['error' => 'Type and emoji are required.'], Response::HTTP_BAD_REQUEST);
        }

        // Chercher uniquement la réaction de l'utilisateur courant pour ce post
        $existingReaction = $reactionRepository->findOneBy([
            'post' => $post,
            'user' => $user,
        ]);

        if ($existingReaction && $existingReaction->getEmoji() === $emoji) {
            // Si l'utilisateur clique sur le même emoji, on supprime sa réaction
            $em->remove($existingReaction);
            $em->flush();
            $message = 'Reaction removed';
            $added = false;
        } else {
            if ($existingReaction) {
                // Si l'utilisateur avait déjà réagi avec un autre emoji, on met à jour sa réaction
                $existingReaction->setType($type);
                $existingReaction->setEmoji($emoji);
                $em->persist($existingReaction);
            } else {
                // Si l'utilisateur n'avait pas encore réagi, on crée une nouvelle réaction
                $reaction = new Reaction();
                $reaction->setPost($post);
                $reaction->setUser($user);
                $reaction->setType($type);
                $reaction->setEmoji($emoji);
                $em->persist($reaction);
            }
            $em->flush();
            $message = 'Reaction added';
            $added = true;
        }

        // Récupérer le nombre total de réactions pour ce post
        $totalReactions = $reactionRepository->count(['post' => $post]);

        $uniqueReactions = [];
        foreach ($post->getReactions() as $reaction) {
            $emoji = $reaction->getEmoji();
            if (!isset($uniqueReactions[$emoji])) {
                $uniqueReactions[$emoji] = 1;
            } else {
                $uniqueReactions[$emoji]++;
            }
        }

        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'added' => $added,
            'reactions' => $uniqueReactions,
            'emoji' => $emoji
        ]);
    }

    #[Route('/comment/{id}/delete', name: 'forum_comment_delete', methods: ['POST'])]
    public function deleteComment(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        // Only allow the comment author to delete the comment
        if ($this->getUser() !== $comment->getAuthor()) {
            $this->addFlash('error', 'You are not authorized to delete this comment.');
            return $this->redirectToRoute('forum_index');
        }

        // Validate CSRF token
        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();

            $this->addFlash('success', 'Comment deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('forum_index');
    }

    #[Route('/badwords/back', name: 'badwords_back', methods: ['GET', 'POST'])]
    public function badWordsBack(BadWordRepository $badWordRepository, Request $request, EntityManagerInterface $em): Response
    {
        $badWords = $badWordRepository->findAll();
        $badWord = new BadWord();
        $badWordForm = $this->createForm(BadWordType::class, $badWord);
        $badWordForm->handleRequest($request);

        if ($badWordForm->isSubmitted() && $badWordForm->isValid()) {
            $em->persist($badWord);
            $em->flush();
            $this->addFlash('success', 'Bad word added successfully.');
            return $this->redirectToRoute('badwords_back');
        }

        return $this->render('backOffice/forum/BadWordsBack.html.twig', [
            'badWords' => $badWords,
            'badWordForm' => $badWordForm->createView(),
        ]);
    }

    #[Route('/badword/{id}/delete', name: 'badword_delete', methods: ['POST'])]
    public function deleteBadWord(BadWord $badWord, EntityManagerInterface $em): Response
    {
        $em->remove($badWord);
        $em->flush();

        $this->addFlash('success', 'Bad word deleted successfully.');
        return $this->redirectToRoute('badwords_back');
    }
}
