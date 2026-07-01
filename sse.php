<?php
session_start();

$me      = $_SESSION['user_id'] ?? null;
$with    = (int)($_GET['with'] ?? 0);
$last_id = (int)($_GET['last_id'] ?? 0);
$listing = (int)($_GET['listing'] ?? 0) ?: null;

session_write_close();

if (!$me || $with === 0) {
    http_response_code(403);
    exit;
}

require_once 'includes/db.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
set_time_limit(0);

try {
    $pdo->prepare("
        UPDATE messages SET is_read = 1
        WHERE receiver_id = :me AND sender_id = :them
    ")->execute([':me' => $me, ':them' => $with]);
} catch (Exception $e) {}

$start = time();

while (true) {
    if (time() - $start > 55) {
        echo "event: ping\ndata: reconnect\n\n";
        ob_flush(); flush();
        break;
    }

    try {
        $sql = "SELECT m.id, m.sender_id, m.receiver_id, m.message, m.created_at
                FROM messages m
                WHERE m.id > :last_id
                AND (
                    (m.sender_id = :me  AND m.receiver_id = :them)
                 OR (m.sender_id = :them2 AND m.receiver_id = :me2)
                )";

        $params = [
            ':last_id' => $last_id,
            ':me'      => $me,
            ':them'    => $with,
            ':them2'   => $with,
            ':me2'     => $me,
        ];

        if ($listing) {
            $sql .= " AND m.listing_id = :listing";
            $params[':listing'] = $listing;
        }

        $sql .= " ORDER BY m.id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $last_id = max(array_column($rows, 'id'));

            $pdo->prepare("
                UPDATE messages SET is_read = 1
                WHERE receiver_id = :me AND sender_id = :them AND id <= :lid
            ")->execute([':me' => $me, ':them' => $with, ':lid' => $last_id]);

            echo "event: message\n";
            echo "data: " . json_encode(array_values($rows)) . "\n\n";
            ob_flush(); flush();
        } else {
            echo "event: ping\ndata: ok\n\n";
            ob_flush(); flush();
        }
    } catch (Exception $e) {
        echo "event: ping\ndata: error\n\n";
        ob_flush(); flush();
    }

    sleep(1);
}