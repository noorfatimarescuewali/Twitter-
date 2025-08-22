<?php
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

// Fetch current user username
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
if (!$stmt) {
    die("Prepare failed (users table): " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $current_user = $result->fetch_assoc()['username'];
} else {
    die("User not found for ID: " . $user_id);
}
$stmt->close();

// Handle tweet post
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tweet'])) {
    $content = substr(trim($_POST['tweet']), 0, 160);
    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO tweets (user_id, content) VALUES (?, ?)");
        if (!$stmt) {
            die("Prepare failed (tweets table): " . $conn->error);
        }
        $stmt->bind_param("is", $user_id, $content);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: home.php");
            exit();
        } else {
            die("Insert failed: " . $stmt->error);
        }
        $stmt->close();
    } else {
        header("Location: home.php?error=empty");
        exit();
    }
}

// Fetch tweets and their authors with like count
$tweets = $conn->prepare("SELECT tweets.id, tweets.content, tweets.created_at, users.username, users.id AS user_id, 
                         (SELECT COUNT(*) FROM likes WHERE likes.tweet_id = tweets.id) AS like_count 
                         FROM tweets 
                         JOIN users ON tweets.user_id = users.id 
                         ORDER BY tweets.created_at DESC");
if (!$tweets) {
    die("Query failed: " . $conn->error);
}
$tweets->execute();
$result = $tweets->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Twitter - Home</title>
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
        .tweet-form {
            margin-bottom: 20px;
        }
        .tweet-form textarea {
            width: 100%;
            height: 80px;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: vertical;
        }
        .tweet-form button {
            background: #1da1f2;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .tweet-form button:hover {
            background: #0d8ae6;
        }
        .tweets {
            margin-top: 20px;
        }
        .tweet {
            border-bottom: 1px solid #ccc;
            padding: 10px 0;
        }
        .tweet .username {
            font-weight: bold;
            color: #1da1f2;
            margin-right: 10px;
        }
        .tweet .time {
            color: #657786;
            font-size: 0.9em;
        }
        .tweet-actions {
            margin-top: 10px;
        }
        .tweet-actions button {
            background: #1da1f2;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
            transition: background 0.3s;
        }
        .tweet-actions button:hover {
            background: #0d8ae6;
        }
        .like-button {
            background: none;
            border: none;
            color: #1da1f2;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }
        .like-button.liked {
            color: #ff0000;
        }
        .status-message {
            color: #28a745;
            margin-top: 10px;
            font-weight: bold;
            padding: 5px;
            border-radius: 3px;
            background-color: #e6ffe6;
        }
        .error {
            color: #e0245e;
            margin-bottom: 10px;
        }
        .like-count {
            margin-left: 5px;
            color: #1da1f2;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $(".like-button").click(function() {
                var tweetId = $(this).data("tweet-id");
                var button = $(this);
                var likeCount = button.next(".like-count");
                $.ajax({
                    url: "like.php",
                    type: "POST",
                    data: { tweet_id: tweetId, user_id: <?php echo $user_id; ?> },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            button.toggleClass("liked");
                            likeCount.text(data.like_count);
                        } else {
                            alert("Error: " + data.message);
                        }
                    },
                    error: function() {
                        alert("An error occurred. Please try again.");
                    }
                });
            });

            // Check initial like status
            $(".like-button").each(function() {
                var tweetId = $(this).data("tweet-id");
                var button = $(this);
                $.ajax({
                    url: "check_like.php",
                    type: "POST",
                    data: { tweet_id: tweetId, user_id: <?php echo $user_id; ?> },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.liked) {
                            button.addClass("liked");
                        }
                    }
                });
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>My Twitter</h2>
            <a href="logout.php">Log Out</a>
        </div>
        <?php if (isset($_GET['error']) && $_GET['error'] == 'empty'): ?>
            <p class="error">Tweet cannot be empty!</p>
        <?php endif; ?>
        <?php
        $status_message = '';
        if (isset($_GET['status'])) {
            if ($_GET['status'] == 'followed') {
                $status_message = "Followed successfully!";
            } elseif ($_GET['status'] == 'unfollowed') {
                $status_message = "Unfollowed successfully!";
            }
        }
        if ($status_message): ?>
            <p class="status-message"><?php echo htmlspecialchars($status_message); ?></p>
        <?php endif; ?>
        <form class="tweet-form" method="POST" action="">
            <textarea name="tweet" placeholder="What's happening? (Max 160 characters)"></textarea>
            <button type="submit">Tweet</button>
        </form>
        <div class="tweets">
            <?php while ($tweet = $result->fetch_assoc()): ?>
                <div class="tweet">
                    <span class="username"><?php echo htmlspecialchars($tweet['username']); ?></span>
                    <p><?php echo htmlspecialchars($tweet['content']); ?></p>
                    <span class="time"><?php echo htmlspecialchars(date('F j, Y g:i A', strtotime($tweet['created_at']))); ?></span>
                    <div class="tweet-actions">
                        <button onclick="window.location.href='comment.php?tweet_id=<?php echo htmlspecialchars($tweet['id']); ?>'">Comment</button>
                        <button onclick="window.location.href='follow.php?user_id=<?php echo htmlspecialchars($tweet['user_id']); ?>'">Follow</button>
                    </div>
                    <button class="like-button" data-tweet-id="<?php echo $tweet['id']; ?>">
                        ♥
                    </button>
                    <span class="like-count"><?php echo $tweet['like_count']; ?></span> Likes
                </div>
            <?php endwhile; ?>
            <?php if ($result->num_rows == 0): ?>
                <p>No tweets yet!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
