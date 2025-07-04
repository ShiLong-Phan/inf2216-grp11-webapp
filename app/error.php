<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Page Not Found</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background-color: #f9f9f9;
            color: #333;
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 100px 20px;
        }
        h1 {
            font-size: 4em;
            margin-bottom: 0.2em;
        }
        p {
            font-size: 1.2em;
            color: #666;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            color: #007BFF;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>404</h1>
    <p>Oops! The page you're looking for doesn't exist.</p>
    <a href="/">Return to homepage</a>
</body>
</html>
