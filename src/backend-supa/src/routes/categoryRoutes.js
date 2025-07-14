import express from 'express';
import { getAllCategories, createCategory, updateCategory } from "../controllers/categoryController.js";

const router = express.Router();

/*
 *  Define GET route for fetching all categories
 *  Define POST route for creating a new category
 *  Define PUT route for updating an existing category by ID
 */
router.get('/', getAllCategories);
router.post('/', createCategory); // Remember to pass name in req body
router.put('/:id', updateCategory); // Remember to pass new name in req body


export default router;