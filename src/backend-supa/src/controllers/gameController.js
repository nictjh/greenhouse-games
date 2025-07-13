import database from "../services/supabaseService.js";

export const getAllGames = async (req, res) => {
    const { data, error } = await database
        .from('games')
        .select('*');

    if (error) {
        console.error('Error fetching games:', error);
        return res.status(500).json({ error: 'Failed to fetch all games' });
    }

    return res.json(data);
}