class MovieApiClient {
    static async searchMovies(query, filters = {}, page = 1) {
        const params = new URLSearchParams({
            page: page,
            ...filters
        });
        
        if (query) {
            params.append('query', query);
        }

        const response = await fetch(`/api/movies/search?${params.toString()}`);
        if (!response.ok) {
            throw new Error('Erreur lors de la recherche de films');
        }
        return await response.json();
    }

    static async getMovieDetails(movieId) {
        const response = await fetch(`/api/movies/${movieId}`);
        if (!response.ok) {
            throw new Error('Erreur lors de la récupération des détails du film');
        }
        return await response.json();
    }

    static async getPopularMovies(page = 1) {
        const response = await fetch(`/api/movies/popular?page=${page}`);
        if (!response.ok) {
            throw new Error('Erreur lors de la récupération des films populaires');
        }
        return await response.json();
    }
}
