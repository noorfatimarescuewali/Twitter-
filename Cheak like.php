<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['liked' => false]);
    exit();
}

$conn = new mysqli("localhost", "ucfpuartvseas", "nhfnfkrfzsjw", "dbeakoezkhjof0");
if ($conn->connect_error) {
    echo json_encode(['liked' => false]);
    exit();
}

$user_id = $_SESSION['user_id'];
$tweet_id = isset($_POST['tweet_id']) ? intval($_POST['tweet_id']) : 0;

if ($tweet_id <= 0) {
    echo json_encode(['liked' => false]);
    exit();
}

// Check if user liked the tweet
$stmt = $conn->prepare("SELECT id FROM likes WHERE tweet_id = ? AND user_id = ?");
if (!$stmt) {
    echo json_encode(['liked' => false]);
    exit();
}
$stmt->bind_param("ii", $tweet_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$liked = $result->num_rows > 0;
$stmt->close();

$conn->close();

echo json_encode(['liked' => $liked]);
