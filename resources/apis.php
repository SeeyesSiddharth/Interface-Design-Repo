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

// ── Allowed tables ───────────────────────────────────────────
$ALLOWED_TABLES = ['games', 'reviews', 'game_platforms', 'game_tags', 'users'];

// ── Parse path:  /<table>[/<field>/<value>] ──────────────────
$pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
$parts    = array_values(array_filter(explode('/', trim($pathInfo, '/'))));

$table = isset($parts[0]) ? preg_replace('/[^a-z0-9_]/i', '', $parts[0]) : '';
$field = isset($parts[1]) ? preg_replace('/[^a-z0-9_]/i', '', $parts[1]) : '';
$key   = isset($parts[2]) ? urldecode($parts[2]) : '';

if (!$table || !in_array($table, $ALLOWED_TABLES)) {
    http_response_code(404);
    echo json_encode(['error' => "Unknown table '$table'. Allowed: " . implode(', ', $ALLOWED_TABLES)]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

// ── Helpers ──────────────────────────────────────────────────

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

function esc($val, $conn): string {
    return mysqli_real_escape_string($conn, (string)$val);
}

function fetchAll($result): array {
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

// ── GET games — joins platforms and tags into arrays ─────────
function getGames($conn, string $field, string $key): void {
    $where = $field && $key
        ? "WHERE g." . preg_replace('/[^a-z0-9_]/i', '', $field) . " = '" . esc($key, $conn) . "'"
        : '';

    $res = mysqli_query($conn, "SELECT g.* FROM games g $where ORDER BY g.id");
    if (!$res) {
        http_response_code(500);
        echo json_encode(['error' => mysqli_error($conn)]);
        return;
    }
    $games = fetchAll($res);
    if (!$games) { echo json_encode([]); return; }

    $ids = implode(',', array_column($games, 'id'));

    $platMap = [];
    $pRes = mysqli_query($conn, "SELECT game_id, platform FROM game_platforms WHERE game_id IN ($ids)");
    while ($row = mysqli_fetch_assoc($pRes)) {
        $platMap[$row['game_id']][] = $row['platform'];
    }

    $tagMap = [];
    $tRes = mysqli_query($conn, "SELECT game_id, tag FROM game_tags WHERE game_id IN ($ids)");
    while ($row = mysqli_fetch_assoc($tRes)) {
        $tagMap[$row['game_id']][] = $row['tag'];
    }

    // Return raw snake_case rows — JS handles camelCase conversion.
    // Platforms and tags are attached as arrays.
    foreach ($games as &$g) {
        $id = $g['id'];
        $g['platform'] = $platMap[$id] ?? [];
        $g['tags']     = $tagMap[$id] ?? [];
        // Nest min/rec so JS can map them to { min: {}, rec: {} }
        $g['min'] = [
            'cpu'     => $g['min_cpu'],
            'gpu'     => $g['min_gpu'],
            'ram'     => (int)$g['min_ram'],
            'storage' => (int)$g['min_storage'],
            'os'      => $g['min_os'],
        ];
        $g['rec'] = [
            'cpu'     => $g['rec_cpu'],
            'gpu'     => $g['rec_gpu'],
            'ram'     => (int)$g['rec_ram'],
            'storage' => (int)$g['rec_storage'],
            'os'      => $g['rec_os'],
        ];
        // Remove the flat columns so they aren't duplicated
        foreach (['min_cpu','min_gpu','min_ram','min_storage','min_os',
                  'rec_cpu','rec_gpu','rec_ram','rec_storage','rec_os'] as $col) {
            unset($g[$col]);
        }
    }

    echo json_encode(array_values($games));
}

// ── Flatten nested min/rec from JS payload ───────────────────
function flattenGame(array $input): array {
    $flat = [];
    $scalar = ['slug','title','studio','genre','release_year','rating','likes','cover_theme','summary','notes'];
    foreach ($scalar as $k) {
        if (array_key_exists($k, $input)) $flat[$k] = $input[$k];
    }
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

    case 'GET':
        if ($table === 'games') {
            getGames($conn, $field, $key);
            break;
        }
        $where = ($field && $key !== '') ? "WHERE `$field` = '" . esc($key, $conn) . "'" : '';
        $result = mysqli_query($conn, "SELECT * FROM `$table` $where");
        if (!$result) { http_response_code(500); echo json_encode(['error' => mysqli_error($conn)]); break; }
        echo json_encode(fetchAll($result));
        break;

    case 'POST':
        if (empty($input)) { http_response_code(400); echo json_encode(['error' => 'Request body is empty or not valid JSON']); break; }

        if ($table === 'games') {
            $flat = flattenGame($input);
            if (empty($flat['slug']) && !empty($flat['title'])) {
                $flat['slug'] = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($flat['title'])));
            }
            if (!mysqli_query($conn, "INSERT INTO `games` SET " . buildSet($flat, $conn))) {
                http_response_code(500); echo json_encode(['error' => mysqli_error($conn)]); break;
            }
            $newId = mysqli_insert_id($conn);
            foreach (($input['platform'] ?? []) as $p) {
                $p = esc(trim($p), $conn);
                mysqli_query($conn, "INSERT IGNORE INTO game_platforms (game_id, platform) VALUES ($newId, '$p')");
            }
            foreach (($input['tags'] ?? []) as $t) {
                $t = esc(trim($t), $conn);
                mysqli_query($conn, "INSERT IGNORE INTO game_tags (game_id, tag) VALUES ($newId, '$t')");
            }
            echo json_encode(['id' => $newId]);
            break;
        }

        if (!mysqli_query($conn, "INSERT INTO `$table` SET " . buildSet($input, $conn))) {
            http_response_code(500); echo json_encode(['error' => mysqli_error($conn)]); break;
        }
        echo json_encode(['id' => mysqli_insert_id($conn)]);
        break;

    case 'PUT':
        if (!$field || $key === '') { http_response_code(400); echo json_encode(['error' => 'PUT requires /<table>/<field>/<value> in the path']); break; }
        if (empty($input)) { http_response_code(400); echo json_encode(['error' => 'Request body is empty or not valid JSON']); break; }

        if ($table === 'games') {
            $flat = flattenGame($input);
            if (!empty($flat)) {
                if (!mysqli_query($conn, "UPDATE `games` SET " . buildSet($flat, $conn) . " WHERE `$field` = '" . esc($key, $conn) . "'")) {
                    http_response_code(500); echo json_encode(['error' => mysqli_error($conn)]); break;
                }
            }
            $gameId = (int)$key;
            if (isset($input['platform'])) {
                mysqli_query($conn, "DELETE FROM game_platforms WHERE game_id = $gameId");
                foreach ($input['platform'] as $p) {
                    $p = esc(trim($p), $conn);
                    mysqli_query($conn, "INSERT IGNORE INTO game_platforms (game_id, platform) VALUES ($gameId, '$p')");
                }
            }
            if (isset($input['tags'])) {
                mysqli_query($conn, "DELETE FROM game_tags WHERE game_id = $gameId");
                foreach ($input['tags'] as $t) {
                    $t = esc(trim($t), $conn);
                    mysqli_query($conn, "INSERT IGNORE INTO game_tags (game_id, tag) VALUES ($gameId, '$t')");
                }
            }
            echo json_encode(['affected' => mysqli_affected_rows($conn)]);
            break;
        }

        if (!mysqli_query($conn, "UPDATE `$table` SET " . buildSet($input, $conn) . " WHERE `$field` = '" . esc($key, $conn) . "'")) {
            http_response_code(500); echo json_encode(['error' => mysqli_error($conn)]); break;
        }
        echo json_encode(['affected' => mysqli_affected_rows($conn)]);
        break;

    case 'DELETE':
        if (!$field || $key === '') { http_response_code(400); echo json_encode(['error' => 'DELETE requires /<table>/<field>/<value> in the path']); break; }
        if (!mysqli_query($conn, "DELETE FROM `$table` WHERE `$field` = '" . esc($key, $conn) . "'")) {
            http_response_code(500); echo json_encode(['error' => mysqli_error($conn)]); break;
        }
        echo json_encode(['affected' => mysqli_affected_rows($conn)]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

mysqli_close($conn);
?>