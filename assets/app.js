import $ from 'jquery'; // Assurez-vous que jQuery est importé
import 'jquery-ui/ui/widgets/autocomplete'; // Importer le module d'autocomplete
import 'bootstrap';
import './styles/app.css'; // On importe le fichier CSS ici
import '@fortawesome/fontawesome-free/css/all.min.css';

let currentPage = 1;
let searchTimeout;

// Sélecteurs jQuery
const $moviesList = $('#movies-list');
const $currentPage = $('#current-page');
const $prevPageBtn = $('#prev-page');
const $nextPageBtn = $('#next-page');
const $movieSearch = $('#movie-search');
const $searchOneMovie = $('#search-one-movie');


// Fonction pour charger les films par genre ou par recherche
function loadMovies(query = null) {
    const selectedGenres = $('.genre-checkbox:checked').map(function() {
        return $(this).val();
    }).get();

    const searchParams = {
        genres: selectedGenres,
        page: currentPage,
        ...(query && { query }) // Ajouter le paramètre de recherche si présent
    };

    $.getJSON('/movies/filter', searchParams)
        .done(function(data) {
            $moviesList.html(data.html);
            $currentPage.text(`Page ${data.currentPage}`);
            togglePaginationButtons(data.totalPages);
        })
        .fail(function() {
            alert('Erreur lors du chargement des films.');
        });
}

// Toggle visibility of pagination buttons
function togglePaginationButtons(totalPages) {
    $prevPageBtn.toggle(currentPage > 1);
    $nextPageBtn.toggle(currentPage < totalPages);
}

// Gestion des événements de changement de genre
$(document).on('change', '.genre-checkbox', function() {
    currentPage = 1; // Réinitialiser à la première page
    $movieSearch.val('');
    loadMovies();
});

// Utilisation de l'événement 'keyup' avec un délai (debounce)
$movieSearch.on('keyup', function() {
    const query = $(this).val().trim();
    clearTimeout(searchTimeout); // Annuler le timeout précédent

    searchTimeout = setTimeout(() => {
        currentPage = 1;
        $('.genre-checkbox').prop('checked', false); // Désélectionner les genres
        loadMovies(query.length >= 3 ? query : null); // Charger les films basés sur la recherche
    }, 500); // Délai de 500ms
});

// Gestion des boutons de pagination
$nextPageBtn.click(function() {
    if (currentPage < totalPages) {
        currentPage++;
        loadMovies(getCurrentQuery());
    }
});

$prevPageBtn.click(function() {
    if (currentPage > 1) {
        currentPage--;
        loadMovies(getCurrentQuery());
    }
});

// Fonction pour obtenir la requête de recherche actuelle
function getCurrentQuery() {
    return $movieSearch.val().trim().length >= 3 ? $movieSearch.val().trim() : null;
}

// Charger les films au chargement de la page
loadMovies();

// Fonction pour ouvrir le modal avec les détails d'un film
function openMovieModal(movieId) {
    
    // Appel à l'API pour obtenir les détails du film et la bande-annonce
    $.getJSON(`/movies/details/${movieId}`, function(data) {
        $('#movieDetailsModalLabel').text(data.title);
        $('#movie-details-content').html(`
            <p><strong>Description:</strong> ${data.overview}</p>
            <p><strong>Date de sortie:</strong> ${data.release_date}</p>
            <p><strong>Note:</strong> ${data.vote_average} (${data.vote_count} votes)</p>
        `);

        // Charger la bande-annonce
        const trailerKey = data.trailer_key;
        if (trailerKey) {
            $('#movie-trailer').attr('src', `https://www.youtube.com/embed/${trailerKey}`);
        } else {
            $('#movie-trailer').attr('src', ''); // Supprimer l'iframe si pas de bande-annonce
        }

        // Ouvrir le modal
        $('#movieDetailsModal').modal('show');
        $('#search-one-movie').val('');
    });
}

// Gestion des clics sur les boutons de détails
$(document).on('click', '.details-button', function() {
    openMovieModal($(this).data('movie-id'));
});

// Autocomplete pour la recherche de films
$searchOneMovie.autocomplete({
    source: function(request, response) {
        $.ajax({
            url: '/autocomplete',
            dataType: 'json',
            data: { query: request.term },
            success: function(data) {
                response(data.map(movie => ({
                    id: movie.id,
                    label: movie.label // juste le label, sans HTML
                })));
            },
            error: function() {
                response([]);
            }
        });
    },
    minLength: 2,
    select: function(event, ui) {
        openMovieModal(ui.item.id);
    }
});

// Personnalisation de l'affichage des éléments de la liste
$.ui.autocomplete.prototype._renderItem = function(ul, item) {
    const $link = $('<a>', {
        href: '#',
        class: 'autocomplete-link',
        'data-movie-id': item.id,
        text: item.label
    });

    return $('<li>').append($link).appendTo(ul);
};
