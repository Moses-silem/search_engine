<?php
// index.php - Home page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Search • Home</title>
    <link rel="stylesheet" href="style.css" type="text/css"/>
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
    <meta name="description" content="Search anything with our simple and fast search engine." />
    <meta name="keywords" content="search, engine, simple, fast, web search" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" />
</head>
<body>
    <div class="container">
        <h1 class="home-logo">Search</h1>
        
        <!-- Form with proper button -->
        <form class="home-search-form" action="results.php" method="get" id="searchForm">
            <span class="search-icon">🔍</span>
            <input type="search" name="q" class="search-input" 
                   placeholder="Search anything..." autocomplete="off" autofocus
                   value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
        </form>

        <div class="buttons">
            <button type="submit" form="searchForm" class="btn btn-primary">Search</button>
            <button type="button" class="btn" onclick="window.location.href='https://www.google.com'">I'm Feeling Lucky</button>
        </div>
    </div>

    <footer>
        © 2026 — ITU/CSU07315
    </footer>
</body>
</html>
?>