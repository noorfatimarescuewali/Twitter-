?php
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
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$tweets = $conn->query("SELECT content, created_at FROM tweets WHERE user_id = $user_id ORDER BY created_at DESC");

if (isset($_GET['follow_id'])) {
    $follow_id = $_GET['follow_id'];
    $stmt = $conn->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $follow_id);
    $stmt->execute();
    $stmt->close();
    echo "<script>window.location.href = 'profile.php';</script>";
}

if (isset($_GET['unfollow_id'])) {
    $unfollow_id = $_GET['unfollow_id'];
    $stmt = $conn->prepare("DELETE FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param("ii", $user_id, $unfollow_id);
    $stmt->execute();
    $stmt->close();
    echo "<script>window.location.href = 'profile.php';</script>";
}

$users = $conn->query("SELECT id, username FROM users WHERE id != $user_id");
$following = $conn->query("SELECT following_id FROM followers WHERE follower_id = $user_id");

$following_ids = [];
while ($row = $following->fetch_assoc()) {
    $following_ids[] = $row['following_id'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - My Twitter</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f8fa;
            margin: 0;
            color: #14171a;
        }
        .header {
            background: #fff;
            border-bottom: 1px solid #e1e8ed;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header h1 {
            font-size: 24px;
            color: #1da1f2;
            margin: 0;
        }
        .header a {
            color: #1da1f2;
            text-decoration: none;
            font-size: 16px;
        }
        .header a:hover {
            text-decoration: underline;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .profile-box {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e1e8ed;
            margin-bottom: 20px;
        }
        .profile-box h2 {
            color: #14171a;
            margin: 0 0 10px;
        }
        .tweet {
            background: #fff;
            padding: 15px;
            border-bottom: 1px solid #e1e8ed;
        }
        .tweet p {
            margin: 5px 0;
        }
        .tweet .time {
            color: #657786;
            font-size: 14px;
        }
        .users-list {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e1e8ed;
        }
        .users-list p {
            margin: 10px 0;
        }
        .btn {
            background: #1da1f2;
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
            transition: background 0.3s ease;
        }
        .btn:hover {
            background: #0d8ae6;
        }
        .btn-unfollow {
            background: #e0245e;
        }
        .btn-unfollow:hover {
            background: #c81b4d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>My Twitter</h1>
        <div>
            <a href="home.php">Home</a> | 
            <a href="logout.php">Log Out</a>
        </div>
    </div>
    <div class="container">
        <div class="profile-box">
            <h2><?php echo htmlspecialchars($user['username']); ?>'s Profile</h2>
            <p><a href="followers.php">View Followers</a></p>
        </div>
        <div class="users-list">
            <h3>Users</h3>
            <?php while ($u = $users->fetch_assoc()): ?>
                <p>
                    <?php echo htmlspecialchars($u['username']); ?>
                    <?php if (in_array($u['id'], $following_ids)): ?>
                        <a href="?unfollow_id=<?php echo $u['id']; ?>" class="btn btn-unfollow">Unfollow</a>
                    <?php else: ?>
                        <a href="?follow_id=<?php echo $u['id']; ?>" class="btn">Follow</a>
                    <?php endif; ?>
                </p>
            <?php endwhile; ?>
        </div>
        <?php while ($tweet = $tweets->fetch_assoc()): ?>
            <div class="tweet">
                <p><?php echo htmlspecialchars($tweet['content']); ?></p>
                <p class="time"><?php echo $tweet['created_at']; ?></p>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>
