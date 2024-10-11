<?php
// src/Service/TmdbService.php
namespace App\Service;

use GuzzleHttp\Client;

class TmdbService
{
    private $client;
    private $apiKey;

    public function __construct(string $tmdbApiKey)
    {
        $this->client = new Client();
        $this->apiKey = $tmdbApiKey;
    }

    public function fetchGenres()
    {
        return $this->fetchFromApi('genre/movie/list');
    }

    public function fetchTopRatedMovie()
    {
        return $this->fetchFromApi('movie/top_rated')['results'][0];
    }

    public function fetchMovieVideos(int $movieId)
    {
        return $this->fetchFromApi("movie/{$movieId}/videos")['results'];
    }

    public function fetchMoviesByGenre(string $genre, int $page = 1)
    {
        return $this->fetchFromApi('discover/movie', [
            'with_genres' => $genre,
            'page' => $page
        ]);
    }

    public function searchMovies(string $query, int $page = 1)
    {
        return $this->fetchFromApi('search/movie', [
            'query' => $query,
            'page' => $page
        ]);
    }

    public function fetchMovieDetails(int $movieId)
    {
        return $this->fetchFromApi("movie/{$movieId}");
    }

    private function fetchFromApi(string $path, array $params = [])
    {
        $response = $this->client->request('GET', "https://api.themoviedb.org/3/{$path}", [
            'query' => array_merge(['api_key' => $this->apiKey, 'language' => 'fr-FR'], $params)
        ]);
        return json_decode($response->getBody(), true);
    }
}


