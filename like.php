<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$conn = new mysqli("localhost", "ucfpuartvseas", "nhfnfkrfzsjw", "dbeakoezkhjof0");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];
$tweet_id = isset($_POST['tweet_id']) ? intval($_POST['tweet_id']) : 0;

if ($tweet_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid tweet ID']);
    exit();
}

// Check if tweet exists
$stmt = $conn->prepare("SELECT id FROM tweets WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
    exit();
}
$stmt->bind_param("i", $tweet_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Tweet not found']);
    exit();
}
$stmt->close();

// Check if user already liked
$check_stmt = $conn->prepare("SELECT id FROM likes WHERE tweet_id = ? AND user_id = ?");
if (!$check_stmt) {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
    exit();
}
$check_stmt->bind_param("ii", $tweet_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$liked = $check_result->num_rows > 0;
$check_stmt->close();

if ($liked) {
    // Unlike
    $delete_stmt = $conn->prepare("DELETE FROM likes WHERE tweet_id = ? AND user_id = ?");
    if (!$delete_stmt) {
        echo json_encode(['success' => false, 'message' => 'Delete preparation failed']);
        exit();
    }
    $delete_stmt->bind_param("ii", $tweet_id, $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();
} else {
    // Like
    $insert_stmt = $conn->prepare("INSERT INTO likes (tweet_id, user_id) VALUES (?, ?)");
    if (!$insert_stmt) {
        echo json_encode(['success' => false, 'message' => 'Insert preparation failed']);
        exit();
    }
    $insert_stmt->bind_param("ii", $tweet_id, $user_id);
    $insert_stmt->execute();
    $insert_stmt->close();
}

// Get updated like count
$count_stmt = $conn->prepare("SELECT COUNT(*) AS like_count FROM likes WHERE tweet_id = ?");
if (!$count_stmt) {
    echo json_encode(['success' => false, 'message' => 'Count preparation failed']);
    exit();
}
$count_stmt->bind_param("i", $tweet_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$like_count = $count_result->fetch_assoc()['like_count'];
$count_stmt->close();

$conn->close();

echo json_encode(['success' => true, 'like_count' => $like_count]);
