<?php
// ============================================================
// Simple REST API — supports multiple tables
// Routes:  /apis.php/<table>
//          /apis.php/<table>/<field>/<value>
// Methods: GET, POST, PUT, DELETE
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Pre-flight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── DB connection ────────────────────────────────────────────
$conn = mysqli_connect('localhost', 'root', '', 'gamebench');

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}
mysqli_set_charset($conn, 'utf8');

// ── Allowed tables (whitelist — never expose arbitrary tables) ──
$ALLOWED_TABLES = ['games', 'reviews', 'game_platforms', 'game_tags', 'users'];

// ── Parse path:  /<table>[/<field>/<value>] ──────────────────
$pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
$parts    = array_values(array_filter(explode('/', trim($pathInfo, '/'))));

$table = isset($parts[0]) ? preg_replace('/[^a-z0-9_]/i', '', $parts[0]) : '';
$field = isset($parts[1]) ? preg_replace('/[^a-z0-9_]/i', '', $parts[1]) : '';
$key   = isset($parts[2]) ? $parts[2] : '';

if (!$table || !in_array($table, $ALLOWED_TABLES)) {
    http_response_code(404);
    echo json_encode(['error' => "Unknown table '$table'. Allowed: " . implode(', ', $ALLOWED_TABLES)]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

// ── Helper: build SET clause safely ─────────────────────────
function buildSet(array $data, $conn): string {
    $pairs = [];
    foreach ($data as $col => $val) {
        $col = preg_replace('/[^a-z0-9_]/i', '', $col);
        if ($val === null) {
            $pairs[] = "`$col` = NULL";
        } else {
            $escaped = mysqli_real_escape_string($conn, (string)$val);
            $pairs[] = "`$col` = '$escaped'";
        }
    }
    return implode(', ', $pairs);
}

// ── Helper: escape a WHERE value ────────────────────────────
function esc($val, $conn): string {
    return mysqli_real_escape_string($conn, (string)$val);
}

// ── Helper: fetch rows as assoc array ───────────────────────
function fetchAll($result): array {
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

// ── Special enriched GET for games ──────────────────────────
// Joins platforms and tags so the client gets a complete object.
function getGames($conn, string $field, string $key): void {
    $where = $field && $key
        ? "WHERE g." . preg_replace('/[^a-z0-9_]/i', '', $field) . " = '" . esc($key, $conn) . "'"
        : '';

    // Main game rows
    $sql = "SELECT g.* FROM games g $where ORDER BY g.id";
    $res = mysqli_query($conn, $sql);
    if (!$res) {
        http_response_code(500);
        echo json_encode(['error' => mysqli_error($conn)]);
        return;
    }
    $games = fetchAll($res);
    if (!$games) { echo json_encode([]); return; }

    $ids = implode(',', array_column($games, 'id'));

    // Platforms
    $platMap = [];
    $pRes = mysqli_query($conn, "SELECT game_id, platform FROM game_platforms WHERE game_id IN ($ids)");
    while ($row = mysqli_fetch_assoc($pRes)) {
        $platMap[$row['game_id']][] = $row['platform'];
    }

    // Tags
    $tagMap = [];
    $tRes = mysqli_query($conn, "SELECT game_id, tag FROM game_tags WHERE game_id IN ($ids)");
    while ($row = mysqli_fetch_assoc($tRes)) {
        $tagMap[$row['game_id']][] = $row['tag'];
    }

    // Merge and reshape to match the JS object shape
    $out = [];
    foreach ($games as $g) {
        $id = $g['id'];
        $out[] = [
            'id'          => (int)$g['id'],
            'slug'        => $g['slug'],
            'title'       => $g['title'],
            'studio'      => $g['studio'],
            'genre'       => $g['genre'],
            'releaseYear' => (int)$g['release_year'],
            'rating'      => (float)$g['rating'],
            'likes'       => (int)$g['likes'],
            'coverTheme'  => $g['cover_theme'],
            'summary'     => $g['summary'],
            'notes'       => $g['notes'],
            'platform'    => $platMap[$id] ?? [],
            'tags'        => $tagMap[$id] ?? [],
            'min' => [
                'cpu'     => $g['min_cpu'],
                'gpu'     => $g['min_gpu'],
                'ram'     => (int)$g['min_ram'],
                'storage' => (int)$g['min_storage'],
                'os'      => $g['min_os'],
            ],
            'rec' => [
                'cpu'     => $g['rec_cpu'],
                'gpu'     => $g['rec_gpu'],
                'ram'     => (int)$g['rec_ram'],
                'storage' => (int)$g['rec_storage'],
                'os'      => $g['rec_os'],
            ],
        ];
    }

    echo json_encode($out);
}

// ── Special POST/PUT for games (flatten nested fields) ──────
function upsertGame(array $input, $conn): array {
    $flat = [];
    $scalar = ['slug','title','studio','genre','release_year','releaseYear',
               'rating','likes','cover_theme','coverTheme','summary','notes'];
    foreach ($scalar as $k) {
        if (isset($input[$k])) $flat[$k] = $input[$k];
    }
    // camelCase → snake_case aliases
    if (isset($flat['releaseYear']))  { $flat['release_year'] = $flat['releaseYear'];  unset($flat['releaseYear']); }
    if (isset($flat['coverTheme']))   { $flat['cover_theme']  = $flat['coverTheme'];   unset($flat['coverTheme']); }

    // Flatten min/rec
    foreach (['min','rec'] as $tier) {
        if (!empty($input[$tier]) && is_array($input[$tier])) {
            foreach ($input[$tier] as $col => $val) {
                $flat["{$tier}_{$col}"] = $val;
            }
        }
    }
    return $flat;
}

// ── Route ────────────────────────────────────────────────────
switch ($method) {

    // ── GET ─────────────────────────────────────────────────
    case 'GET':
        if ($table === 'games') {
            getGames($conn, $field, $key);
            break;
        }

        // Generic GET for other tables
        $where = ($field && $key !== '')
            ? "WHERE `$field` = '" . esc($key, $conn) . "'"
            : '';
        $result = mysqli_query($conn, "SELECT * FROM `$table` $where");
        if (!$result) {
            http_response_code(500);
            echo json_encode(['error' => mysqli_error($conn)]);
            break;
        }
        echo json_encode(fetchAll($result));
        break;

    // ── POST ────────────────────────────────────────────────
    case 'POST':
        if (empty($input)) {
            http_response_code(400);
            echo json_encode(['error' => 'Request body is empty or not valid JSON']);
            break;
        }

        if ($table === 'games') {
            $flat = upsertGame($input, $conn);

            // Derive slug if missing
            if (empty($flat['slug']) && !empty($flat['title'])) {
                $flat['slug'] = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($flat['title'])));
            }

            $sql = "INSERT INTO `games` SET " . buildSet($flat, $conn);
            if (!mysqli_query($conn, $sql)) {
                http_response_code(500);
                echo json_encode(['error' => mysqli_error($conn)]);
                break;
            }
            $newId = mysqli_insert_id($conn);

            // Insert platforms
            if (!empty($input['platform'])) {
                $platforms = is_array($input['platform'])
                    ? $input['platform']
                    : array_map('trim', explode(',', $input['platform']));
                foreach ($platforms as $p) {
                    $p = esc($p, $conn);
                    mysqli_query($conn, "INSERT IGNORE INTO game_platforms (game_id, platform) VALUES ($newId, '$p')");
                }
            }
            // Insert tags
            if (!empty($input['tags'])) {
                $tags = is_array($input['tags'])
                    ? $input['tags']
                    : array_map('trim', explode(',', $input['tags']));
                foreach ($tags as $t) {
                    $t = esc($t, $conn);
                    mysqli_query($conn, "INSERT IGNORE INTO game_tags (game_id, tag) VALUES ($newId, '$t')");
                }
            }
            echo json_encode(['id' => $newId]);
            break;
        }

        // Generic POST
        $sql = "INSERT INTO `$table` SET " . buildSet($input, $conn);
        if (!mysqli_query($conn, $sql)) {
            http_response_code(500);
            echo json_encode(['error' => mysqli_error($conn)]);
            break;
        }
        echo json_encode(['id' => mysqli_insert_id($conn)]);
        break;

    // ── PUT ─────────────────────────────────────────────────
    case 'PUT':
        if (!$field || $key === '') {
            http_response_code(400);
            echo json_encode(['error' => 'PUT requires /<table>/<field>/<value> in the path']);
            break;
        }
        if (empty($input)) {
            http_response_code(400);
            echo json_encode(['error' => 'Request body is empty or not valid JSON']);
            break;
        }

        if ($table === 'games') {
            $flat = upsertGame($input, $conn);
            if (!empty($flat)) {
                $sql = "UPDATE `games` SET " . buildSet($flat, $conn)
                     . " WHERE `$field` = '" . esc($key, $conn) . "'";
                if (!mysqli_query($conn, $sql)) {
                    http_response_code(500);
                    echo json_encode(['error' => mysqli_error($conn)]);
                    break;
                }
            }

            // Re-sync platforms
            if (isset($input['platform'])) {
                $gameId = (int)$key; // assume field is 'id' when syncing related tables
                mysqli_query($conn, "DELETE FROM game_platforms WHERE game_id = $gameId");
                $platforms = is_array($input['platform'])
                    ? $input['platform']
                    : array_map('trim', explode(',', $input['platform']));
                foreach ($platforms as $p) {
                    $p = esc($p, $conn);
                    mysqli_query($conn, "INSERT IGNORE INTO game_platforms (game_id, platform) VALUES ($gameId, '$p')");
                }
            }
            // Re-sync tags
            if (isset($input['tags'])) {
                $gameId = (int)$key;
                mysqli_query($conn, "DELETE FROM game_tags WHERE game_id = $gameId");
                $tags = is_array($input['tags'])
                    ? $input['tags']
                    : array_map('trim', explode(',', $input['tags']));
                foreach ($tags as $t) {
                    $t = esc($t, $conn);
                    mysqli_query($conn, "INSERT IGNORE INTO game_tags (game_id, tag) VALUES ($gameId, '$t')");
                }
            }
            echo json_encode(['affected' => mysqli_affected_rows($conn)]);
            break;
        }

        // Generic PUT
        $sql = "UPDATE `$table` SET " . buildSet($input, $conn)
             . " WHERE `$field` = '" . esc($key, $conn) . "'";
        if (!mysqli_query($conn, $sql)) {
            http_response_code(500);
            echo json_encode(['error' => mysqli_error($conn)]);
            break;
        }
        echo json_encode(['affected' => mysqli_affected_rows($conn)]);
        break;

    // ── DELETE ──────────────────────────────────────────────
    case 'DELETE':
        if (!$field || $key === '') {
            http_response_code(400);
            echo json_encode(['error' => 'DELETE requires /<table>/<field>/<value> in the path']);
            break;
        }
        $sql = "DELETE FROM `$table` WHERE `$field` = '" . esc($key, $conn) . "'";
        if (!mysqli_query($conn, $sql)) {
            http_response_code(500);
            echo json_encode(['error' => mysqli_error($conn)]);
            break;
        }
        echo json_encode(['affected' => mysqli_affected_rows($conn)]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

mysqli_close($conn);
?>