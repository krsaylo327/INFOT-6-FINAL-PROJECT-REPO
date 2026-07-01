require('dotenv').config();

const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const connectDB = require('./config/db');
const authRoutes = require('./routes/auth');
const bookRoutes = require('./routes/books');

const app = express();

// Middleware
app.use(express.json());
app.use(cors());
app.use(helmet());

app.use('/api/auth', authRoutes);
app.use('/api/books', bookRoutes);

// Test Route
app.get('/', (req, res) => {
    res.send('LibroLog API is running!');
});

const PORT = process.env.PORT || 5000;

// Connect to MongoDB before starting the server
const startServer = async () => {
    await connectDB();

    app.listen(PORT, () => {
        console.log(`Server running on http://localhost:${PORT}`);
    });
};

startServer();