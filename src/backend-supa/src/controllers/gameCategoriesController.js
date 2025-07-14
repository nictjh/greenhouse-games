import database from '../services/supabaseService.js';

/*
 *  Linking games to a certain category
 *  Tested and working
 */
export const createGameCategory = async (req, res) => {
    const { game_id, category_id } = req.body;

    if (!game_id || !category_id) {
        return res.status(400).json({ error: 'Both game_id and category_id are required.' });
    }

    const { data, error } = await database
        .from('gamecategories')
        .insert([{ game_id, category_id }])
        .select();

    if (error) {
        console.error('Error linking game and category:', error);
        return res.status(500).json({ error: error.message });
    }

    return res.status(201).json(data[0]);
};

// Unlink a game from a category
export const deleteGameCategory = async (req, res) => {
    const { id } = req.params;

    const { error } = await database
        .from('gamecategories')
        .delete()
        .eq('id', id);

    if (error) {
        console.error('Error deleting game-category link:', error);
        return res.status(500).json({ error: error.message });
    }

    return res.status(204).send();
};

/*
 *  Get all categories for a specific game
 *  Tested and working
 */
export const getCategoriesForGame = async (req, res) => {
    const { id } = req.params;

    // Nested Select to fetch categories for a specific game
    const { data, error } = await database
        .from('gamecategories')
        .select(
              `id,
              categories (
                category_id,
                name
              )`
        )
        .eq('game_id', id);

    if (error) {
        console.error('Error fetching categories for game:', error);
        return res.status(500).json({ error: error.message });
    }

    // Return just the categories array
    if (!data || data.length === 0) {
        return res.json([]);
    }

    // Safely extract categories
    const categories = data
        .filter(row => row.categories != null)
        .map(row => row.categories);

    return res.json(categories);
};

/*
 *  Get all games for a specific category
 *  Tested and working
 */
export const getGamesForCategory = async (req, res) => {
    const { id } = req.params;

    const { data, error } = await database
        .from('gamecategories')
        .select(
          `id,
          games (
            game_id,
            title,
            price,
            thumbnail_url
          )`
        )
        .eq('category_id', id);

    if (error) {
        console.error('Error fetching games for category:', error);
        return res.status(500).json({ error: error.message });
    }

    return res.json(data.map(row => row.games));
};