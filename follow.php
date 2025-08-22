<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "ucfpuartvseas", "nhfnfkrfzsjw", "dbeakoezkhjof0");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    header("Location: home.php?error=invalid_user_id");
    exit();
}
$follow_user_id = intval($_GET['user_id']);

if ($follow_user_id == $user_id) {
    header("Location: home.php?error=cannot_follow_self");
    exit();
}

// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
if (!$stmt) {
    die("Prepare failed (users table): " . $conn->error);
}
$stmt->bind_param("i", $follow_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    header("Location: home.php?error=nonexistent_user_id");
    exit();
}
$stmt->close();

// Check if user is already following
$check_stmt = $conn->prepare("SELECT id FROM followers WHERE user_id = ? AND follower_id = ?");
if (!$check_stmt) {
    die("Prepare failed (followers table check): " . $conn->error);
}
$check_stmt->bind_param("ii", $follow_user_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$following = $check_result->num_rows > 0;
$check_stmt->close();

if ($following) {
    $delete_stmt = $conn->prepare("DELETE FROM followers WHERE user_id = ? AND follower_id = ?");
    if (!$delete_stmt) {
        die("Prepare failed (delete followers): " . $delete_stmt->error);
    }
    $delete_stmt->bind_param("ii", $follow_user_id, $user_id);
    if ($delete_stmt->execute()) {
        $delete_stmt->close();
        // Temporarily commented out to avoid header error
        // echo "Debug: Unfollow executed successfully.<br>";
        header("Location: home.php?status=unfollowed&user_id=" . $follow_user_id . "#follow-section");
        exit();
    } else {
        die("Delete failed: " . $delete_stmt->error);
    }
    $delete_stmt->close();
} else {
    $insert_stmt = $conn->prepare("INSERT INTO followers (user_id, follower_id) VALUES (?, ?)");
    if (!$insert_stmt) {
        die("Prepare failed (insert followers): " . $insert_stmt->error);
    }
    $insert_stmt->bind_param("ii", $follow_user_id, $user_id);
    if ($insert_stmt->execute()) {
        $insert_stmt->close();
        // Temporarily commented out to avoid header error
        // echo "Debug: Follow executed successfully.<br>";
        header("Location: home.php?status=followed&user_id=" . $follow_user_id . "#follow-section");
        exit();
    } else {
        die("Insert failed: " . $insert_stmt->error);
    }
    $insert_stmt->close();
}

$conn->close();
