<?php
include 'config.php';

function generateShortCode($length = 6)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $shortCode = '';
    for ($i = 0; $i < $length; $i++) {
        $shortCode .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $shortCode;
}

function shortenURL($originalURL)
{
    $conn = getDbConnection();
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        return null;
    }

    $shortCode = generateShortCode();

    // Check if it's already used
    $stmt = $conn->prepare("SELECT * FROM urls WHERE short_code = ?");
    $stmt->bind_param("s", $shortCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return shortenURL($originalURL);
    }

    // Save to database
    $stmt = $conn->prepare("INSERT INTO urls (original_url, short_code) VALUES (?, ?)");
    $stmt->bind_param("ss", $originalURL, $shortCode);
    if (!$stmt->execute()) {
        error_log("Error inserting URL: " . $stmt->error);
        $conn->close();
        return null;
    }

    $conn->close(); // Close connection

    return $shortCode;
}

function getOriginalURL($shortCode)
{
    $conn = getDbConnection();
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        return null;
    }

    $stmt = $conn->prepare("SELECT original_url FROM urls WHERE short_code = ?");
    $stmt->bind_param("s", $shortCode);
    $stmt->execute();
    $result = $stmt->get_result();

    $conn->close(); // Close connection

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['original_url'];
    }

    return null;
}

// If the user sends a URL, then shorten it.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['url'])) {
        $originalURL = filter_var($_POST['url'], FILTER_SANITIZE_URL);
        if (filter_var($originalURL, FILTER_VALIDATE_URL)) {
            $shortCode = shortenURL($originalURL);
            if ($shortCode) {
                $shortenedURL = "/shortened.php?code=$shortCode";
                echo "<div class='container'>";
                echo "<div class='row justify-content-center'>";
                echo "<div class='col-12 col-md-8 col-lg-6'>";
                echo "<div class='card p-4'>";
                echo "<div class='text-center mt-3'>";
                echo "<p>URL Shortened: <a href='$shortenedURL' id='shortened-url'>$shortenedURL</a></p>";
                echo "<button class='btn btn-secondary' onclick='copyToClipboard()'>Copy to Clipboard</button>";
                echo "</div>"; 
                echo "</div>"; 
                echo "</div>"; 
                echo "</div>"; 
                echo "</div>";
            } else {
                echo "<div class='text-center mt-3 text-danger'>Error shortening URL.</div>";
            }
        } else {
            echo "<div class='text-center mt-3 text-danger'>Invalid URL.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URL Shortener</title>
    <script>
        function copyToClipboard() {
            var urlElement = document.getElementById('shortened-url');
            var url = urlElement.href;
            navigator.clipboard.writeText(url).then(function () {
                alert('URL copied to clipboard');
            }, function (err) {
                alert('Failed to copy URL: ', err);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('shorten-form');
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var url = document.getElementById('url').value;
                if (url) {
                    form.submit();
                } else {
                    alert('Please enter a URL');
                }
            });
        });
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body class="d-flex align-items-center justify-content-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card p-4">
                    <h1 class="text-center">Shortly URL Shortener</h1>
                    <form id="shorten-form" method="POST">
                        <div class="mb-3">
                            <label for="url" class="form-label">Enter URL to shorten:</label>
                            <input class="form-control" type="text" name="url" id="url" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Shorten</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>

</html>