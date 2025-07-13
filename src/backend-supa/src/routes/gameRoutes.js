import express from 'express';
import { getAllGames } from '../controllers/gameController.js';

// Creates the router object
const router = express.Router();

/*
 * Define GET /api/games
 */
router.get('/', getAllGames);

export default router;
