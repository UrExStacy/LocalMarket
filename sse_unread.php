<?php
session_start();

$me = $_SESSION['user_id'] ?? null;
session_write_close();

if (!$me) {
    http_response_code(403);
    exit;
}

require_once 'includes/db.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
set_time_limit(0);

$start      = time();
$last_count = -1;

while (true) {
    if (time() - $start > 55) {
        echo "event: ping\ndata: reconnect\n\n";
        ob_flush(); flush();
        break;
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM messages WHERE receiver_id = :me AND is_read = 0"
        );
        $stmt->execute([':me' => $me]);
        $count = (int)$stmt->fetchColumn();

        if ($count !== $last_count) {
            echo "event: unread\n";
            echo "data: $count\n\n";
            ob_flush(); flush();
            $last_count = $count;
        } else {
            echo "event: ping\ndata: ok\n\n";
            ob_flush(); flush();
        }
    } catch (Exception $e) {
        echo "event: ping\ndata: error\n\n";
        ob_flush(); flush();
    }

    sleep(2);
}