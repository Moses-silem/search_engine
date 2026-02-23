<?php
require "connection.php"; 
require "sample-data.php"; 

$sql = "INSERT INTO search_items
(title, description, page_name, page_fav_icon_path, page_url, created_at)
VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $connection->prepare($sql);

foreach($sampleData as $item) {
    $stmt->bind_param(
        "ssssss",
        $item["title"],
        $item["description"],
        $item["page_name"],
        $item["page_fav_icon_path"],
        $item['page_url'],
        $item['created_at']
    );
    $stmt->execute();
}
$stmt->close();
$connection->close();

echo "data inserted successfully";


foreach($sampleData as $item) {
    $stmt->bind_param(
        "ssssss",
        $item["title"],
        $item["description"],
        $item["page_name"],
        $item["page_fav_icon_path"],
        $item['page_url'],
        $item['created_at']
    );
    $stmt->execute();
}
$stmt->close();
$connection->close();

echo "data inserted successfully";
?>