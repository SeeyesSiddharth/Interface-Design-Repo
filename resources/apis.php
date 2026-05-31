<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

mysqli_report(MYSQLI_REPORT_OFF);

function send_json($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function esc($value, $conn)
{
    return mysqli_real_escape_string($conn, $value);
}

function fetch_all_assoc($result)
{
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function build_set($data, $conn)
{
    $pairs = [];

    foreach ($data as $col => $val) {
        $col = preg_replace('/[^a-z0-9_]/i', '', (string)$col);

        if ($col === '') {
            continue;
        }

        if ($val === null) {
            $pairs[] = "`$col` = NULL";
        } else {
            $escaped = mysqli_real_escape_string($conn, (string)$val);
            $pairs[] = "`$col` = '$escaped'";
        }
    }

    return implode(', ', $pairs);
}

function flatten_game($input)
{
    $flat = [];

    $scalar = [
        'slug',
        'title',
        'studio',
        'genre',
        'release_year',
        'rating',
        'likes',
        'cover_theme',
        'summary',
        'notes',
    ];

    foreach ($scalar as $key) {
        if (array_key_exists($key, $input)) {
            $flat[$key] = $input[$key];
        }
    }

    foreach (['min', 'rec'] as $tier) {
        if (!empty($input[$tier]) && is_array($input[$tier])) {
            foreach ($input[$tier] as $col => $val) {
                $safeCol = preg_replace('/[^a-z0-9_]/i', '', (string)$col);
                if ($safeCol !== '') {
                    $flat["{$tier}_{$safeCol}"] = $val;
                }
            }
        }
    }

    return $flat;
}

function parse_route_parts()
{
    $pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';

    if ($pathInfo === '' || $pathInfo === null) {
        $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';

        if ($requestUri !== '' && $scriptName !== '') {
            $requestPath = parse_url($requestUri, PHP_URL_PATH);

            if (is_string($requestPath) && strpos($requestPath, $scriptName) === 0) {
                $pathInfo = substr($requestPath, strlen($scriptName));
            }
        }
    }

    $parts = array_values(array_filter(explode('/', trim((string)$pathInfo, '/'))));

    if (empty($parts) && isset($_GET['table'])) {
        $parts[] = (string)$_GET['table'];

        if (isset($_GET['field'])) {
            $parts[] = (string)$_GET['field'];
        }

        if (isset($_GET['key'])) {
            $parts[] = (string)$_GET['key'];
        }
    }

    $table = isset($parts[0]) ? preg_replace('/[^a-z0-9_]/i', '', (string)$parts[0]) : '';
    $field = isset($parts[1]) ? preg_replace('/[^a-z0-9_]/i', '', (string)$parts[1]) : '';
    $key = isset($parts[2]) ? urldecode((string)$parts[2]) : '';

    return [$table, $field, $key];
}

function get_games($conn, $field, $key)
{
    $where = '';

    if ($field !== '' && $key !== '') {
        $safeField = preg_replace('/[^a-z0-9_]/i', '', $field);
        $safeKey = esc($key, $conn);
        $where = "WHERE g.`$safeField` = '$safeKey'";
    }

    $sql = "SELECT g.* FROM games g $where ORDER BY g.id";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        send_json(['error' => mysqli_error($conn)], 500);
    }

    $games = fetch_all_assoc($res);

    if (!$games) {
        send_json([]);
    }

    $rawIds = [];
    foreach ($games as $g) {
        $rawIds[] = (int)$g['id'];
    }
    $ids = implode(',', $rawIds);

    $platformMap = [];
    $platformRes = mysqli_query(
        $conn,
        "SELECT game_id, platform FROM game_platforms WHERE game_id IN ($ids)"
    );

    if ($platformRes) {
        while ($row = mysqli_fetch_assoc($platformRes)) {
            $platformMap[$row['game_id']][] = $row['platform'];
        }
    }

    $tagMap = [];
    $tagRes = mysqli_query(
        $conn,
        "SELECT game_id, tag FROM game_tags WHERE game_id IN ($ids)"
    );

    if ($tagRes) {
        while ($row = mysqli_fetch_assoc($tagRes)) {
            $tagMap[$row['game_id']][] = $row['tag'];
        }
    }

    foreach ($games as &$g) {
        $id = $g['id'];

        $g['platform'] = isset($platformMap[$id]) ? $platformMap[$id] : [];
        $g['tags'] = isset($tagMap[$id]) ? $tagMap[$id] : [];

        $g['min'] = [
            'cpu' => $g['min_cpu'],
            'gpu' => $g['min_gpu'],
            'ram' => (int)$g['min_ram'],
            'storage' => (int)$g['min_storage'],
            'os' => $g['min_os'],
        ];

        $g['rec'] = [
            'cpu' => $g['rec_cpu'],
            'gpu' => $g['rec_gpu'],
            'ram' => (int)$g['rec_ram'],
            'storage' => (int)$g['rec_storage'],
            'os' => $g['rec_os'],
        ];

        foreach (
            [
                'min_cpu',
                'min_gpu',
                'min_ram',
                'min_storage',
                'min_os',
                'rec_cpu',
                'rec_gpu',
                'rec_ram',
                'rec_storage',
                'rec_os',
            ] as $col
        ) {
            unset($g[$col]);
        }
    }

    send_json(array_values($games));
}

// ── DB connection ──────────────────────────────────────────────
// $conn = mysqli_connect('localhost', 'root', '', 'gamebench');
$conn = mysqli_connect('feenix-mariadb.swin.edu.au', 's105571938', '020496', 's105571938_db');

if (!$conn) {
    send_json(
        ['error' => 'Database connection failed: ' . mysqli_connect_error()],
        500
    );
}

mysqli_set_charset($conn, 'utf8');

$allowedTables = ['games', 'reviews', 'game_platforms', 'game_tags', 'users'];
list($table, $field, $key) = parse_route_parts();

if ($table === '' || !in_array($table, $allowedTables, true)) {
    send_json(
        ['error' => "Unknown table '$table'. Allowed: " . implode(', ', $allowedTables)],
        404
    );
}

$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$input = is_array($input) ? $input : [];

switch ($method) {
    case 'GET':
        if ($table === 'games') {
            get_games($conn, $field, $key);
        }

        $where = '';
        if ($field !== '' && $key !== '') {
            $safeField = preg_replace('/[^a-z0-9_]/i', '', $field);
            $safeKey = esc($key, $conn);
            $where = "WHERE `$safeField` = '$safeKey'";
        }

        $result = mysqli_query($conn, "SELECT * FROM `$table` $where");

        if (!$result) {
            send_json(['error' => mysqli_error($conn)], 500);
        }

        send_json(fetch_all_assoc($result));
        break;

    case 'POST':
        if (empty($input)) {
            send_json(['error' => 'Request body is empty or not valid JSON'], 400);
        }

        if ($table === 'games') {
            $flat = flatten_game($input);

            if (empty($flat['slug']) && !empty($flat['title'])) {
                $flat['slug'] = strtolower(
                    preg_replace('/[^a-z0-9]+/i', '-', trim((string)$flat['title']))
                );
                $flat['slug'] = trim($flat['slug'], '-');
            }

            $setClause = build_set($flat, $conn);

            if ($setClause === '') {
                send_json(['error' => 'No valid game fields were provided'], 400);
            }

            if (!mysqli_query($conn, "INSERT INTO `games` SET $setClause")) {
                send_json(['error' => mysqli_error($conn)], 500);
            }

            $newId = mysqli_insert_id($conn);

            $platforms = isset($input['platform']) ? $input['platform'] : [];
            foreach ($platforms as $p) {
                $p = trim((string)$p);
                if ($p === '') {
                    continue;
                }
                $p = esc($p, $conn);
                mysqli_query(
                    $conn,
                    "INSERT IGNORE INTO game_platforms (game_id, platform) VALUES ($newId, '$p')"
                );
            }

            $tags = isset($input['tags']) ? $input['tags'] : [];
            foreach ($tags as $t) {
                $t = trim((string)$t);
                if ($t === '') {
                    continue;
                }
                $t = esc($t, $conn);
                mysqli_query(
                    $conn,
                    "INSERT IGNORE INTO game_tags (game_id, tag) VALUES ($newId, '$t')"
                );
            }

            send_json(['id' => $newId], 201);
        }

        $setClause = build_set($input, $conn);

        if ($setClause === '') {
            send_json(['error' => 'No valid fields were provided'], 400);
        }

        if (!mysqli_query($conn, "INSERT INTO `$table` SET $setClause")) {
            send_json(['error' => mysqli_error($conn)], 500);
        }

        send_json(['id' => mysqli_insert_id($conn)], 201);
        break;

    case 'PUT':
        if ($field === '' || $key === '') {
            send_json(['error' => 'PUT requires /table/field/key in the path'], 400);
        }

        if (empty($input)) {
            send_json(['error' => 'Request body is empty or not valid JSON'], 400);
        }

        if ($table === 'games') {
            $flat = flatten_game($input);

            if (!empty($flat)) {
                $setClause = build_set($flat, $conn);
                $safeField = preg_replace('/[^a-z0-9_]/i', '', $field);
                $safeKey = esc($key, $conn);

                if (!mysqli_query($conn, "UPDATE `games` SET $setClause WHERE `$safeField` = '$safeKey'")) {
                    send_json(['error' => mysqli_error($conn)], 500);
                }
            }

            $gameId = (int)$key;

            if (isset($input['platform']) && is_array($input['platform'])) {
                mysqli_query($conn, "DELETE FROM game_platforms WHERE game_id = $gameId");

                foreach ($input['platform'] as $p) {
                    $p = trim((string)$p);
                    if ($p === '') {
                        continue;
                    }
                    $p = esc($p, $conn);
                    mysqli_query(
                        $conn,
                        "INSERT IGNORE INTO game_platforms (game_id, platform) VALUES ($gameId, '$p')"
                    );
                }
            }

            if (isset($input['tags']) && is_array($input['tags'])) {
                mysqli_query($conn, "DELETE FROM game_tags WHERE game_id = $gameId");

                foreach ($input['tags'] as $t) {
                    $t = trim((string)$t);
                    if ($t === '') {
                        continue;
                    }
                    $t = esc($t, $conn);
                    mysqli_query(
                        $conn,
                        "INSERT IGNORE INTO game_tags (game_id, tag) VALUES ($gameId, '$t')"
                    );
                }
            }

            send_json(['affected' => mysqli_affected_rows($conn)]);
        }

        $setClause = build_set($input, $conn);
        $safeField = preg_replace('/[^a-z0-9_]/i', '', $field);
        $safeKey = esc($key, $conn);

        if ($setClause === '') {
            send_json(['error' => 'No valid fields were provided'], 400);
        }

        if (!mysqli_query($conn, "UPDATE `$table` SET $setClause WHERE `$safeField` = '$safeKey'")) {
            send_json(['error' => mysqli_error($conn)], 500);
        }

        send_json(['affected' => mysqli_affected_rows($conn)]);
        break;

    case 'DELETE':
        if ($field === '' || $key === '') {
            send_json(['error' => 'DELETE requires /table/field/key in the path'], 400);
        }

        $safeField = preg_replace('/[^a-z0-9_]/i', '', $field);
        $safeKey = esc($key, $conn);

        if (!mysqli_query($conn, "DELETE FROM `$table` WHERE `$safeField` = '$safeKey'")) {
            send_json(['error' => mysqli_error($conn)], 500);
        }

        send_json(['affected' => mysqli_affected_rows($conn)]);
        break;

    default:
        send_json(['error' => 'Method not allowed'], 405);
        break;
}

mysqli_close($conn);