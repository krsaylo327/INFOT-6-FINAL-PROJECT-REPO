const jwt = require('jsonwebtoken');

const auth = (req, res, next) => {

    try {

        // Get token from Authorization header
        const authHeader = req.header('Authorization');

        if (!authHeader) {
            return res.status(401).json({
                message: 'Access denied. No token provided.'
            });
        }

        // Remove "Bearer "
        const token = authHeader.replace('Bearer ', '');

        // Verify token
        const decoded = jwt.verify(token, process.env.JWT_SECRET);

        // Save user info to request
        req.user = decoded;

        // Continue to next function
        next();

    }
    catch (err) {

        return res.status(401).json({
            message: 'Invalid or expired token.'
        });

    }

};

module.exports = auth;