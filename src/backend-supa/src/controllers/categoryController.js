import database from "../services/supabaseService.js";

// Tested and works
export const getAllCategories = async (req, res) => {
    const { data, error } = await database
        .from('categories')
        .select('*');

    if (error) {
        console.error('Error fetching categories:', error);
        return res.status(500).json({ error: 'Failed to fetch all categories' });
    }

    return res.json(data);
}

// Tested and works
export const createCategory = async (req, res) => {
    const { name } = req.body;

    const { data, error } = await database
        .from('categories')
        .insert([{ name }])
        .select(); // to return a more meaningful response

    if (error) {
        return res.status(500).json({ error: error.message });
    }

    return res.status(201).json(data[0]);
};

// Tested and works
export const updateCategory = async (req, res) => {
    const { id } = req.params;
    const { name } = req.body;

    const { data, error } = await database
        .from('categories')
        .update({ name })
        .eq('category_id', id);

    if (error) {
        return res.status(500).json({ error: error.message });
    }

    return res.json(data);
};
