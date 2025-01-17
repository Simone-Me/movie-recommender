class MovieApi {

  static async searchMovies(query = "", year = "", genre = "") {
    const params = new URLSearchParams();

    // N'ajouter les paramètres que s'ils ne sont pas vides
    if (query?.trim()) params.append("query", query.trim());
    if (year?.toString().trim()) params.append("year", year.toString().trim());
    if (genre?.toString().trim())
      params.append("genre", genre.toString().trim());

    try {
      const response = await fetch(`/api/movies?${params.toString()}`, {
        headers: {
          Accept: "application/json",
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      console.log("API Response:", data);
      return Array.isArray(data) ? data : [];
    } catch (error) {
      console.error("Error fetching movies:", error);
      return [];
    }
  }

  static async getMovieDetails(id) {
    try {
      const response = await fetch(`/api/movies/${id}`, {
        headers: {
          Accept: "application/json",
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      console.error("Error fetching movie details:", error);
      return null;
    }
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("search-form");
  const resultsContainer = document.getElementById("results-container");

  if (form) {
    form.addEventListener("submit", async function (e) {
      e.preventDefault();

      const formData = new FormData(form);
      const query = formData.get("form[query]")?.trim() || "";
      const year = formData.get("form[year]")?.trim() || "";
      const genre = formData.get("form[genre]")?.trim() || "";

      // Vérifier si au moins un critère de recherche est présent
      if (!query && !year && !genre) {
        resultsContainer.innerHTML =
          '<div class="alert alert-warning">Veuillez entrer au moins un critère de recherche (titre ou année ou genre).</div>';
        return;
      }

      try {
        resultsContainer.innerHTML =
          '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Chargement...</span></div></div>';

        const movies = await MovieApi.searchMovies(query, year, genre);
        updateResults(movies);
      } catch (error) {
        console.error("Error fetching movies:", error);
        resultsContainer.innerHTML =
          '<div class="alert alert-danger">Une erreur est survenue lors de la recherche des films.</div>';
      }
    });
  }

  function updateResults(movies) {
    if (!resultsContainer) return;

    if (!movies || !movies.length) {
      resultsContainer.innerHTML =
        '<div class="alert alert-info">Aucun film trouvé avec ces critères.</div>';
      return;
    }

    resultsContainer.innerHTML = movies
      .map(
        (movie) => `
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    ${
                      movie.posterPath

                        ? `<img src="https://image.tmdb.org/t/p/w500${movie.posterPath}" class="card-img-top" alt="${movie.title}">`
                        : '<div class="card-img-top bg-secondary" style="height: 300px;"></div>'
                    }
                    <div class="card-body">

                        <h5 class="card-title">${
                          movie.title || "Sans titre"
                        }</h5>
                        <p class="card-text">${
                          movie.overview
                            ? movie.overview.substring(0, 150) + "..."
                            : "Aucune description disponible"
                        }</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary">${
                              movie.voteAverage || "?"
                            }/10</span>
                            <a href="/movie/${
                              movie.id
                            }" class="btn btn-sm btn-outline-primary">Voir plus</a>

                        </div>
                    </div>
                </div>
            </div>

        `
      )
      .join("");
  }
});
