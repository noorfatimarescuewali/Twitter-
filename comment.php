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

// Check if tweet_id is provided
if (!isset($_GET['tweet_id']) || !is_numeric($_GET['tweet_id'])) {
    die("Invalid tweet ID.");
}
$tweet_id = intval($_GET['tweet_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment'])) {
    $content = substr(trim($_POST['comment']), 0, 160);
    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO comments (tweet_id, user_id, content) VALUES (?, ?, ?)");
        if (!$stmt) {
            die("Prepare failed (comments table): " . $conn->error);
        }
        $stmt->bind_param("iis", $tweet_id, $user_id, $content);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: home.php?tweet_id=" . $tweet_id . "#comment-section");
            exit();
        } else {
            die("Insert failed: " . $stmt->error);
        }
        $stmt->close();
    } else {
        header("Location: home.php?tweet_id=" . $tweet_id . "?error=empty_comment#comment-section");
        exit();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Twitter - Add Comment</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #e6ecf0;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header a {
            color: #1da1f2;
            text-decoration: none;
            font-size: 14px;
        }
        .header a:hover {
            text-decoration: underline;
        }
        .comment-form {
            margin-bottom: 20px;
        }
        .comment-form textarea {
            width: 100%;
            height: 80px;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: vertical;
        }
        .comment-form button {
            background: #1da1f2;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .comment-form button:hover {
            background: #0d8ae6;
        }
        .error {
            color: #e0245e;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Add Comment to Tweet #<?php echo htmlspecialchars($tweet_id); ?></h2>
            <a href="home.php">Back to Home</a>
        </div>
        <?php if (isset($_GET['error']) && $_GET['error'] == 'empty_comment'): ?>
            <p class="error">Comment cannot be empty!</p>
        <?php endif; ?>
        <form class="comment-form" method="POST" action="">
            <textarea name="comment" placeholder="Write your comment (Max 160 characters)"></textarea>
            <button type="submit">Post Comment</button>
        </form>
    </div>
</body>
</html>
