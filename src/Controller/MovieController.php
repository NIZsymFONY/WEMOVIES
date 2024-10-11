<?php

// src/Controller/MovieGenreController.php
namespace App\Controller;

use App\Service\TmdbService;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class MovieController extends AbstractController
{
    private $tmdbService;

    public function __construct(TmdbService $tmdbService)
    {
        $this->tmdbService = $tmdbService;
    }

    #[Route('/', name: 'movies')]
    public function genres(): Response
    {
        // Appel au service pour obtenir les genres et le meilleur film
        $genres = $this->tmdbService->fetchGenres();
        $bestMovie = $this->tmdbService->fetchTopRatedMovie();
        $videos = $this->tmdbService->fetchMovieVideos($bestMovie['id']);
        
        // Filtrer la première bande-annonce YouTube
        $trailer = null;
        foreach ($videos as $video) {
            if ($video['type'] === 'Trailer' && $video['site'] === 'YouTube') {
                $trailer = $video;
                break;
            }
        }

        return $this->render('index.html.twig', [
            'genres' => $genres['genres'],
            'bestMovie' => $bestMovie,
            'trailer' => $trailer
        ]);
    }

    #[Route('/movies/filter', name: 'filter_movies')]
    public function filterMovies(Request $request, PaginatorInterface $paginator): Response
    {
        $selectedGenres = implode('|', (array)$request->query->get('genres', '28'));
        $page = (int) $request->query->get('page', 1);
        $query = $request->query->get('query', null);

        if ($query) {
            // Recherche par texte
            $data = $this->tmdbService->searchMovies($query, $page);
        } else {
            // Recherche par genre
            $data = $this->tmdbService->fetchMoviesByGenre($selectedGenres, $page);
        }

        $movies = $data['results'];
        $totalMovies = $data['total_results'];

        // Paginator de Knp
        $pagination = $paginator->paginate(
            $movies,
            $page,
            5
        );

        $html = $this->renderView('partials/_movie_cards.html.twig', [
            'movies' => $pagination->getItems()
        ]);

        return new JsonResponse([
            'html' => $html,
            'movies' => $pagination->getItems(),
            'total' => $totalMovies,
            'currentPage' => $pagination->getCurrentPageNumber(),
            'totalPages' => ceil($totalMovies / 5),
        ]);
    }

    #[Route('/movies/details/{id}', name: 'movie_details')]
    public function movieDetails(int $id): Response
    {
        $movieDetails = $this->tmdbService->fetchMovieDetails($id);
        $videos = $this->tmdbService->fetchMovieVideos($id);

        // Récupérer la bande-annonce YouTube
        $trailerKey = '';
        foreach ($videos as $video) {
            if ($video['type'] === 'Trailer' && $video['site'] === 'YouTube') {
                $trailerKey = $video['key'];
                break;
            }
        }

        return new JsonResponse([
            'title' => $movieDetails['title'],
            'overview' => $movieDetails['overview'],
            'release_date' => $movieDetails['release_date'],
            'vote_average' => $movieDetails['vote_average'],
            'vote_count' => $movieDetails['vote_count'],
            'trailer_key' => $trailerKey,
        ]);
    }


#[Route('/autocomplete', name: 'movie_autocomplete')]
public function searchMovies(Request $request)
{
    $query = $request->query->get('query', null);
    $client = new Client();
    $apiKey = $this->getParameter('tmdb_api_key');
    $response = $client->request('GET', 'https://api.themoviedb.org/3/search/movie', [
        'query' => [
            'api_key' => $apiKey,
            'query' => $query,
            'language' => 'fr-FR',
        ]
    ]);

    $data = json_decode($response->getBody(), true);

    // Préparer les résultats pour l'autocomplétion
    $movies = [];
    foreach ($data['results'] as $movie) {
        $movies[] = [
            'label' => $movie['title'], // Texte à afficher dans l'autocomplétion
            'id' => $movie['id'], // ID du film pour une éventuelle utilisation ultérieure
        ];
    }

    return new JsonResponse($movies); // Retourner les résultats en JSON
}

}
