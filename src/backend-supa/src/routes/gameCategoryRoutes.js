import express from 'express';
import {
    createGameCategory,
    deleteGameCategory,
    getCategoriesForGame,
    getGamesForCategory
} from "../controllers/gameCategoriesController.js";

const router = express.Router();

router.post('/', createGameCategory); // Include game_id and category_id in the request body to link
router.delete('/:id', deleteGameCategory); // Delete a game-category link by ID
router.get('/games/:id/categories', getCategoriesForGame); // Include game_id
router.get('/categories/:id/games', getGamesForCategory); // Include category_id

export default router;