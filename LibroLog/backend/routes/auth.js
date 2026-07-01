const express = require('express');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');

const User = require('../models/User');

const router = express.Router();

/*
===========================
POST /api/auth/register
===========================
*/

router.post('/register', async (req, res) => {

    try {

        const {
            username,
            email,
            password,
            confirmPassword
        } = req.body;

        // Check required fields
        if (!username || !email || !password || !confirmPassword) {
            return res.status(400).json({
                message: "All fields are required."
            });
        }

        // Passwords must match
        if (password !== confirmPassword) {
            return res.status(400).json({
                message: "Passwords do not match."
            });
        }

        // Check if email already exists
        const existingUser = await User.findOne({ email });

        if (existingUser) {
            return res.status(400).json({
                message: "Email already exists."
            });
        }

        // Encrypt password
        const hashedPassword = await bcrypt.hash(password, 10);

        // Create user
        const newUser = new User({
            username,
            email,
            password: hashedPassword
        });

        await newUser.save();

        res.status(201).json({
            message: "User registered successfully."
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
===========================
POST /api/auth/login
===========================
*/

router.post('/login', async (req, res) => {

    try {

        const { email, password } = req.body;

        // Check required fields
        if (!email || !password) {
            return res.status(400).json({
                message: "Email and password are required."
            });
        }

        // Find user
        const user = await User.findOne({ email });

        if (!user) {
            return res.status(400).json({
                message: "Invalid email or password."
            });
        }

        // Compare password
        const isMatch = await bcrypt.compare(password, user.password);

        if (!isMatch) {
            return res.status(400).json({
                message: "Invalid email or password."
            });
        }

        // Generate JWT Token
        const token = jwt.sign(
            {
                id: user._id
            },
            process.env.JWT_SECRET,
            {
                expiresIn: "1d"
            }
        );

        res.status(200).json({
            message: "Login successful.",
            token,
            username: user.username
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