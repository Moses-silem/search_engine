 <?php
require_once "connection.php";

// Start timer for measuring search time
$start_time = microtime(true);
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Sanitize query for HTML display
$clean_query = htmlspecialchars($query);

// Pagination settings
$results_per_page = 20;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $results_per_page;

// Initialize variables
$results = [];
$total_results = 0;
$search_time = 0;
$search_terms = [];

// Perform search if query is not empty
if (!empty($query)) {
    // Split query into words and sanitize for SQL
    $words = explode(' ', $query);
    $words = array_filter(array_map('trim', $words));
    $search_terms = $words;
    
    if (!empty($words)) {
        // Build WHERE clause with LIKE conditions for each word
        $where_conditions = [];
        foreach ($words as $word) {
            $safe_word = mysqli_real_escape_string($connection, $word);
            $where_conditions[] = "(title LIKE '%$safe_word%' OR description LIKE '%$safe_word%')";
        }
        
        $where_sql = implode(' AND ', $where_conditions);
        
        // Count total results
        $count_sql = "SELECT COUNT(*) as total FROM search_items WHERE $where_sql";
        $count_result = mysqli_query($connection, $count_sql);
        
        if ($count_result) {
            $row = mysqli_fetch_assoc($count_result);
            $total_results = $row['total'];
            mysqli_free_result($count_result);
        }
        
        // Get paginated results with relevance ranking
        // Create a simple relevance score based on word matches
        $relevance_select = [];
        foreach ($words as $word) {
            $safe_word = mysqli_real_escape_string($connection, $word);
            $relevance_select[] = "(CASE WHEN LOWER(title) LIKE LOWER('%$safe_word%') THEN 3 ELSE 0 END)";
            $relevance_select[] = "(CASE WHEN LOWER(description) LIKE LOWER('%$safe_word%') THEN 1 ELSE 0 END)";
        }
        
        $relevance_sql = implode(' + ', $relevance_select);
        if (empty($relevance_sql)) $relevance_sql = '0';
        
        $select_sql = "SELECT *, ($relevance_sql) as relevance_score 
                      FROM search_items 
                      WHERE $where_sql 
                      ORDER BY relevance_score DESC, created_at DESC 
                      LIMIT $results_per_page OFFSET $offset";
        
        $result_set = mysqli_query($connection, $select_sql);
        
        if ($result_set) {
            while ($row = mysqli_fetch_assoc($result_set)) {
                $results[] = $row;
            }
            mysqli_free_result($result_set);
        }
        
        // Calculate search time
        $search_time = microtime(true) - $start_time;
    }
}

// Highlight matching words in text
function highlight_words($text, $words) {
    if (empty($words) || empty($text)) {
        // Return first 150 characters if no words to highlight
        return strlen($text) > 150 ? substr($text, 0, 150) . '...' : $text;
    }
    
    $highlighted = htmlspecialchars($text);
    
    foreach ($words as $word) {
        $word = trim($word);
        if (!empty($word)) {
            // Use a case-insensitive replace with word boundaries
            $pattern = '/\b(' . preg_quote($word, '/') . ')\b/i';
            $highlighted = preg_replace($pattern, '<span class="highlight">$1</span>', $highlighted);
        }
    }
    
    // If text is too long, truncate it
    if (strlen($highlighted) > 300) {
        $highlighted = substr($highlighted, 0, 300) . '...';
    }
    
    return $highlighted;
}

// Get snippet of text around search terms
function get_text_snippet($text, $words, $max_length = 200) {
    $text_lower = strtolower($text);
    
    foreach ($words as $word) {
        $word_lower = strtolower($word);
        $pos = stripos($text, $word);
        
        if ($pos !== false) {
            $start = max(0, $pos - 50);
            $snippet = substr($text, $start, $max_length);
            
            if ($start > 0) {
                $snippet = '...' . $snippet;
            }
            
            if (strlen($text) > $start + $max_length) {
                $snippet .= '...';
            }
            
            return $snippet;
        }
    }
    
    // If no word found, return beginning
    return strlen($text) > $max_length ? substr($text, 0, $max_length) . '...' : $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Search results for "<?php echo $clean_query; ?>"</title>
    <link rel="stylesheet" href="style.css" type="text/css"/>
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" />
</head>
<body>
    <header>
        <div class="search-bar-container">
            <a href="index.php" class="logo">Search</a>
            <form class="search-form" action="results.php" method="get">
                <span class="search-icon">🔍</span>
                <input type="search" name="q" class="search-input" 
                       value="<?php echo $clean_query; ?>" placeholder="Search..." autofocus>
                <button type="submit" style="display:none;">Search</button>
            </form>
        </div>
    </header>

    <main>
        <?php if (empty($query)): ?>
            <div class="no-results">
                <h2>Please enter a search term</h2>
                <p>Type something in the search box above to find results.</p>
            </div>
        
        <?php elseif (empty($results) && !empty($query)): ?>
            <div class="stats">
                No results found for "<?php echo $clean_query; ?>"
            </div>
            <div class="no-results">
                <h2>No results found</h2>
                <p>Try different keywords or check your spelling.</p>
            </div>
        
        <?php elseif (!empty($results)): ?>
            <div class="stats">
                About <?php echo number_format($total_results); ?> results 
                (<?php echo number_format($search_time, 3); ?> seconds)
            </div>
            
            <?php foreach ($results as $result): 
                // Get snippet for description
                $description_snippet = get_text_snippet($result['description'], $search_terms);
                $highlighted_description = highlight_words($description_snippet, $search_terms);
                $highlighted_title = highlight_words($result['title'], $search_terms);
            ?>
                <div class="result-item">
                    <div class="result-header">
                        <?php if (!empty($result['page_fav_icon_path'])): ?>
                            <img src="<?php echo htmlspecialchars($result['page_fav_icon_path']); ?>" 
                                 class="favicon" alt="" loading="lazy">
                        <?php else: ?>
                            <div class="favicon-placeholder"></div>
                        <?php endif; ?>
                        <span class="result-url">
                            <?php echo htmlspecialchars(parse_url($result['page_url'], PHP_URL_HOST) ?? $result['page_url']); ?>
                        </span>
                    </div>
                    <h3 class="result-title">
                        <a href="<?php echo htmlspecialchars($result['page_url']); ?>" target="_blank">
                            <?php echo $highlighted_title; ?>
                        </a>
                    </h3>
                    <div class="result-snippet">
                        <?php echo $highlighted_description; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <?php if ($total_results > $results_per_page): 
                $total_pages = ceil($total_results / $results_per_page);
            ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="results.php?q=<?php echo urlencode($query); ?>&page=<?php echo $current_page - 1; ?>" 
                           class="page-link">‹ Previous</a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="results.php?q=<?php echo urlencode($query); ?>&page=<?php echo $i; ?>" 
                           class="page-link <?php echo ($i == $current_page) ? 'current' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="results.php?q=<?php echo urlencode($query); ?>&page=<?php echo $current_page + 1; ?>" 
                           class="page-link">Next ›</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    
    <footer style="text-align: center; padding: 20px; color: #666; margin-top: 40px;">
        © 2026 — ITU/CSU07315
    </footer>
</body>
</html>
<?php
// Close database connection
if (isset($connection)) {
    mysqli_close($connection);
}
?>