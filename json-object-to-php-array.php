<?php

session_start();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_timestamp'] = time();
}

$action = $_GET['action'] ?? 'default';
$data = $_POST['data'] ?? null;
$convertedArrayString = '';
$errMsg = '';

if ($action == 'convert' && isset($_POST['_csrf_token_'], $_POST['_csrf_timestamp_'])) {
    $clientToken = $_POST['_csrf_token_'];
    $serverToken = $_SESSION['csrf_token'];

    if (hash_equals($serverToken, $clientToken) && (time() - (int)$_POST['_csrf_timestamp_']) <= 300) {
        if (!empty($data)) {
            $jsonArray = json_decode($data, true);

            if ($jsonArray !== null) {
                $convertedArrayString = '$jsonArray = [' . PHP_EOL;

                foreach ($jsonArray as $item) {
                    $convertedArrayString .= '   [' . PHP_EOL;
                    foreach ($item as $key => $value) {
                        $convertedArrayString .= '      "' . $key . '" => "' . $value . '",' . PHP_EOL;
                    }
                    $convertedArrayString .= '   ],' . PHP_EOL;
                }

                $convertedArrayString .= '];';
            } else {
                $errMsg = 'Invalid JSON string';
            }
        }
    } else {
        $errMsg = 'CSRF token validation failed';
    }
    $response = json_encode(['errMsg' => $errMsg, 'convertedArrayString' => $convertedArrayString]);

    header('Content-Type: application/json');
    echo $response;
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="Description" content="Convert JSON Object To PHP Array" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Convert JSON Object To PHP Array</title>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h1 class="mb-0">Convert JSON Object to PHP Array</h1>
            </div>
            <div class="card-body">
                <form id="jsonForm" method="post">
                    <div class="form-group">
                        <label for="jsonInput">Enter your JSON Object or Array:</label>
                        <textarea class="form-control" cols="100" rows="8" id="jsonInput" required></textarea>
                    </div>
                    <input type="hidden" name="_csrf_token_" value="<?= $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="_csrf_timestamp_" value="<?= time(); ?>">
                    <button type="button" onclick="convertToPHPArray()" class="btn btn-primary btn-sm">
                        <i class="fas fa-exchange-alt"></i> Convert to PHP Array
                    </button>
                    <hr>
                    <div class="form-group">
                        <label for="convertedJsonData">Converted PHP Array:</label>
                        <textarea readonly="readonly" class="form-control" cols="100" rows="8" id="convertedJsonData"></textarea>
                    </div>
                    <button type="button" onclick="copyToClipboard()" class="btn btn-warning my-1 btn-sm">
                        <i class="far fa-clipboard"></i> Copy to Clipboard
                    </button>
                    <div class="alert alert-warning mt-2" role="alert" id="errMsg"></div>
                </form>
            </div>
        </div>
    </div>
    <footer class="text-center mt-5">
        <p>&copy; 2024 RetiredQQ. All rights reserved.</p>
    </footer>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.min.js"></script>
    <script>
        $('#errMsg').css('display', 'none');

        function convertToPHPArray() {
            var jsonInput = $('#jsonInput').val();
            var csrfToken = $('input[name="_csrf_token_"]').val();
            var csrfTimestamp = $('input[name="_csrf_timestamp_"]').val();

            $.post("json-object-to-php-array.php?action=convert", {
                data: jsonInput,
                _csrf_token_: csrfToken,
                _csrf_timestamp_: csrfTimestamp
            }, function(response) {
                if (response.errMsg == '') {
                    $('#errMsg').css('display', 'none');
                    $('#errMsg').text(response.errMsg);
                    $('#convertedJsonData').val(response.convertedArrayString);
                } else {
                    $('#errMsg').css('display', '');
                    $('#errMsg').text(response.errMsg);
                    $('#convertedJsonData').val(response.convertedArrayString);
                }
            }, 'json');
        }

        function copyToClipboard() {
            var copyText = $('#convertedJsonData').val();

            navigator.clipboard.writeText(copyText)
                .then(function() {
                    alert('Copied to clipboard!');
                })
                .catch(function(err) {
                    console.error('Unable to copy to clipboard', err);
                });
        }
    </script>
</body>

</html>