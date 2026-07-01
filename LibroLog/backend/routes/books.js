const express = require('express');
const router = express.Router();

const axios = require("axios");
const Book = require('../models/Book');
const auth = require('../middleware/auth');

// Helper: retry on 429 with exponential backoff
async function fetchWithRetry(url, options = {}, maxRetries = 3, baseDelay = 500) {
    for (let attempt = 0; attempt <= maxRetries; attempt++) {
        try {
            return await axios.get(url, options);
        } catch (err) {
            const status = err && err.response && err.response.status;
            if (status === 429 && attempt < maxRetries) {
                const jitter = Math.floor(Math.random() * 100);
                const delay = baseDelay * Math.pow(2, attempt) + jitter;
                await new Promise(resolve => setTimeout(resolve, delay));
                continue;
            }
            throw err;
        }
    }
}

/*
==========================================
GET /api/books/search/:title
Search Google Books API
==========================================
*/

router.get('/search/:title', auth, async (req, res) => {

    try {

        const title = req.params.title;

        let apiUrl = `https://www.googleapis.com/books/v1/volumes?q=intitle:${encodeURIComponent(title)}`;
        if (process.env.GOOGLE_BOOKS_API_KEY) {
            apiUrl += `&key=${process.env.GOOGLE_BOOKS_API_KEY}`;
        }

        const response = await fetchWithRetry(apiUrl, {
            headers: {
                'Accept': 'application/json',
                'User-Agent': 'BookTracker/1.0'
            },
            timeout: 5000
        });

        if (!response.data.items || response.data.items.length === 0) {
            return res.status(404).json({
                message: "No books found."
            });
        }

        const book = response.data.items[0].volumeInfo;

        res.json({

            title: book.title || "",

            author: book.authors
                ? book.authors.join(", ")
                : "Unknown",

            genre: book.categories
                ? book.categories.join(", ")
                : "Unknown",

            pages: book.pageCount || 0,

            description: book.description || "",

            cover: book.imageLinks
                ? book.imageLinks.thumbnail
                : ""

        });

    }

    catch(err){

    console.log(err.message);

    res.json({

        source: "Fallback",

        title: req.params.title,

        author: "Unknown",

        genre: "Unknown",

        pages: 0,

        description:
            "Google Books API unavailable. Using fallback data.",

        cover: ""

    });

}

});
/*
================================
POST /api/books
Create a new book
================================
*/

router.post('/', auth, async (req, res) => {

    try {

        const {
            title,
            author,
            isbn,
            genre,
            status,
            pages,
            currentPage,
            rating,
            review
        } = req.body;

        // Required fields
        if (!title || !author) {
            return res.status(400).json({
                message: 'Title and Author are required.'
            });
        }

        const newBook = new Book({

            user: req.user.id,

            title,
            author,
            isbn,
            genre,
            status,
            pages,
            currentPage,
            rating,
            review

        });

        await newBook.save();

        res.status(201).json({

            message: "Book added successfully.",
            book: newBook

        });

    }
    catch (err) {

        console.error(err);

        res.status(500).json({
            message: "Server Error"
        });

    }

});

/*
================================
GET /api/books
Get all books of logged-in user
================================
*/

router.get('/', auth, async (req, res) => {

    try {

        const books = await Book.find({
            user: req.user.id
        });

        res.status(200).json({
            books
        });

    }
    catch (err) {

        console.error(err);

        res.status(500).json({
            message: "Server Error"
        });

    }

});

/*
================================
PUT /api/books/:id
Update a book
================================
*/

router.put('/:id', auth, async (req, res) => {

    try {

        const book = await Book.findOne({
            _id: req.params.id,
            user: req.user.id
        });

        if (!book) {
            return res.status(404).json({
                message: "Book not found."
            });
        }

        const {
            title,
            author,
            isbn,
            genre,
            status,
            pages,
            currentPage,
            rating,
            review
        } = req.body;

        book.title = title || book.title;
        book.author = author || book.author;
        book.isbn = isbn;
        book.genre = genre;
        book.status = status;
        book.pages = pages;
        book.currentPage = currentPage;
        book.rating = rating;
        book.review = review;

        await book.save();

        res.status(200).json({
            message: "Book updated successfully.",
            book
        });

    }
    catch (err) {

        console.error(err);

        res.status(500).json({
            message: "Server Error"
        });

    }

});

/*
================================
DELETE /api/books/:id
Delete a book
================================
*/

router.delete('/:id', auth, async (req, res) => {

    try {

        const book = await Book.findOne({
            _id: req.params.id,
            user: req.user.id
        });

        if (!book) {
            return res.status(404).json({
                message: "Book not found."
            });
        }

        await book.deleteOne();

        res.status(200).json({
            message: "Book deleted successfully."
        });

    }
    catch (err) {

        console.error(err);

        res.status(500).json({
            message: "Server Error"
        });

    }

});
module.exports = router;