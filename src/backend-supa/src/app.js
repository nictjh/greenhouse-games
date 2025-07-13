import express from 'express';
import dotenv from 'dotenv';
import cors from 'cors';

import gameRoutes from './routes/gameRoutes.js';

dotenv.config();

// Creates instance of express app
const app = express();
app.use(cors());
app.use(express.json());

// Routes registering
app.use('/api/games', gameRoutes);

// Start the server and listen for HTTP requests
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Backend running on http://localhost:${PORT}`);
});
