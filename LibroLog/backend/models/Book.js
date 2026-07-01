const mongoose = require('mongoose');

const BookSchema = new mongoose.Schema({

    user: {
        type: mongoose.Schema.Types.ObjectId,
        ref: 'User',
        required: true
    },

    title: {
        type: String,
        required: true
    },

    author: {
        type: String,
        required: true
    },

    isbn: String,

    genre: String,

    status: {
        type: String,
        enum: ['want-to-read', 'reading', 'completed'],
        default: 'want-to-read'
    },

    pages: Number,

    currentPage: Number,

    rating: Number,

    review: String

}, {
    timestamps: true
});

module.exports = mongoose.model('Book', BookSchema);