<?php
/**
 * Chat Process API
 * Handles all real-time messaging endpoints.
 */
require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

// CSRF check for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf()) {
    echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Update last activity to keep user online while messenger is open
try {
    $nowStr = date('Y-m-d H:i:s');
    $db->prepare("UPDATE users SET last_activity = :now WHERE user_id = :uid")
       ->execute(['now' => $nowStr, 'uid' => $user_id]);
} catch (Exception $e) { /* ignore */ }

try {
    switch ($action) {
        case 'fetch_users':
            // Fetch all users except self
            $stmt = $db->prepare("
                SELECT u.user_id, u.username, u.role, u.last_activity,
                       u.first_name, u.last_name, u.profile_picture,
                       (SELECT status FROM user_logins WHERE user_id = u.user_id ORDER BY login_time DESC LIMIT 1) as current_status,
                       cp.status as permission_status,
                       cp.requester_id
                FROM users u
                LEFT JOIN staff_chat_permissions cp ON (cp.requester_id = :uid1 AND cp.target_id = u.user_id) OR (cp.requester_id = u.user_id AND cp.target_id = :uid2)
                WHERE u.user_id != :uid3
                ORDER BY u.last_activity DESC
            ");
            $stmt->execute(['uid1' => $user_id, 'uid2' => $user_id, 'uid3' => $user_id]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = [];
            foreach ($users as $u) {
                // Online logic matching session_history.php
                $isActiveByActivity = false;
                if (!empty($u['last_activity'])) {
                    $lastAct = strtotime($u['last_activity']);
                    if (time() - $lastAct < 300) $isActiveByActivity = true;
                }
                $isOnline = ($u['current_status'] == 'active' && $isActiveByActivity);

                $displayName = $u['username'];
                if ($u['first_name']) {
                    $displayName = 'Dr. ' . $u['first_name'] . ' ' . $u['last_name'];
                }

                $result[] = [
                    'id' => $u['user_id'],
                    'name' => $displayName,
                    'role' => ucfirst($u['role']),
                    'online' => $isOnline,
                    'permission' => $u['permission_status'],
                    'is_requester' => ($u['requester_id'] == $user_id),
                    'picture' => $u['profile_picture'] ? ('../uploads/users/' . $u['profile_picture']) : null
                ];
            }
            echo json_encode(['ok' => true, 'users' => $result]);
            break;

        case 'request_permission':
            $target_id = intval($_POST['target_id'] ?? 0);
            if (!$target_id) throw new Exception('Invalid target user.');

            $stmt = $db->prepare("SELECT id, status FROM staff_chat_permissions WHERE (requester_id = :uid1 AND target_id = :tid1) OR (requester_id = :tid2 AND target_id = :uid2)");
            $stmt->execute(['uid1' => $user_id, 'tid1' => $target_id, 'tid2' => $target_id, 'uid2' => $user_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Update to pending if it was rejected
                $db->prepare("UPDATE staff_chat_permissions SET status = 'pending', requester_id = :uid, target_id = :tid, updated_at = NOW() WHERE id = :id")
                   ->execute(['uid' => $user_id, 'tid' => $target_id, 'id' => $existing['id']]);
            } else {
                $db->prepare("INSERT INTO staff_chat_permissions (requester_id, target_id, status) VALUES (:uid, :tid, 'pending')")
                   ->execute(['uid' => $user_id, 'tid' => $target_id]);
            }
            echo json_encode(['ok' => true]);
            break;

        case 'respond_permission':
            $requester_id = intval($_POST['requester_id'] ?? 0);
            $status = $_POST['status'] === 'accepted' ? 'accepted' : 'rejected';
            if (!$requester_id) throw new Exception('Invalid requester.');

            $db->prepare("UPDATE staff_chat_permissions SET status = :status, updated_at = NOW() WHERE requester_id = :req AND target_id = :uid")
               ->execute(['status' => $status, 'req' => $requester_id, 'uid' => $user_id]);
            
            echo json_encode(['ok' => true]);
            break;

        case 'send_message':
            $receiver_id = intval($_POST['receiver_id'] ?? 0);
            $message = trim($_POST['message'] ?? '');
            
            if (!$receiver_id) throw new Exception('Invalid receiver.');
            if (empty($message) && empty($_FILES['chat_file']['name'])) {
                throw new Exception('Message cannot be empty.');
            }

            // Verify permission
            $stmt = $db->prepare("SELECT status FROM staff_chat_permissions WHERE ((requester_id = :uid1 AND target_id = :tid1) OR (requester_id = :tid2 AND target_id = :uid2)) AND status = 'accepted'");
            $stmt->execute(['uid1' => $user_id, 'tid1' => $receiver_id, 'tid2' => $receiver_id, 'uid2' => $user_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Permission to chat is not granted.');
            }

            $message = htmlspecialchars($message); // XSS prevention

            // Handle File Upload
            $file_path = null;
            $file_name = null;
            $file_type = null;

            if (isset($_FILES['chat_file']) && $_FILES['chat_file']['error'] === UPLOAD_ERR_OK) {
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt'];
                $file = $_FILES['chat_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed_extensions)) {
                    throw new Exception('Invalid file type. Only JPG, PNG, GIF, PDF, and TXT are allowed.');
                }
                if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                    throw new Exception('File size exceeds 5MB limit.');
                }

                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) $file_type = 'image';
                elseif ($ext === 'pdf') $file_type = 'pdf';
                else $file_type = 'text';

                $file_name = htmlspecialchars($file['name']);
                $new_file_name = uniqid('chat_') . '_' . time() . '.' . $ext;
                $upload_dir = __DIR__ . '/uploads/chat_files/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_file_name)) {
                    $file_path = 'uploads/chat_files/' . $new_file_name;
                } else {
                    throw new Exception('Failed to upload file.');
                }
            }

            $stmt = $db->prepare("INSERT INTO staff_chat_messages (sender_id, receiver_id, message, file_path, file_name, file_type) VALUES (:uid, :tid, :msg, :fpath, :fname, :ftype)");
            $stmt->execute([
                'uid' => $user_id, 
                'tid' => $receiver_id, 
                'msg' => $message,
                'fpath' => $file_path,
                'fname' => $file_name,
                'ftype' => $file_type
            ]);
            
            echo json_encode(['ok' => true, 'id' => $db->lastInsertId(), 'msg' => $message]);
            break;

        case 'delete_message':
            $msg_id = intval($_POST['message_id'] ?? 0);
            if (!$msg_id) throw new Exception('Invalid message ID.');

            // Allow hard delete or soft delete. We'll do soft delete to preserve thread flow if we want, or hard delete if it's a file to save space. Let's do soft delete but wipe file.
            $stmt = $db->prepare("SELECT sender_id, file_path FROM staff_chat_messages WHERE id = :id");
            $stmt->execute(['id' => $msg_id]);
            $msg = $stmt->fetch();

            if (!$msg || $msg['sender_id'] != $user_id) {
                throw new Exception('Unauthorized to delete this message.');
            }

            // Remove file from disk
            if (!empty($msg['file_path'])) {
                $physical_path = __DIR__ . '/' . $msg['file_path'];
                if (file_exists($physical_path)) @unlink($physical_path);
            }

            // Soft delete
            $db->prepare("UPDATE staff_chat_messages SET is_deleted = 1, message = 'This message was deleted.', file_path = NULL, file_name = NULL, file_type = NULL WHERE id = :id")
               ->execute(['id' => $msg_id]);

            echo json_encode(['ok' => true]);
            break;

        case 'fetch_messages':
            $with_user = intval($_GET['with_user'] ?? 0);
            $last_id = intval($_GET['last_id'] ?? 0);
            if (!$with_user) throw new Exception('Invalid user.');

            $sql = "SELECT id, sender_id, message, file_path, file_name, file_type, is_deleted, created_at, is_read FROM staff_chat_messages 
                    WHERE ((sender_id = :uid1 AND receiver_id = :wid1) OR (sender_id = :wid2 AND receiver_id = :uid2)) ";
            $params = ['uid1' => $user_id, 'wid1' => $with_user, 'wid2' => $with_user, 'uid2' => $user_id];

            if ($last_id > 0) {
                $sql .= " AND id > :last_id ";
                $params['last_id'] = $last_id;
            }

            $sql .= " ORDER BY created_at ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $messages = $stmt->fetchAll();

            // Mark received messages as read
            if (!empty($messages)) {
                $db->prepare("UPDATE staff_chat_messages SET is_read = 1 WHERE receiver_id = :uid AND sender_id = :wid AND is_read = 0")
                   ->execute(['uid' => $user_id, 'wid' => $with_user]);
            }

            // Check if the other user is online to determine "delivered" (2 ticks) status
            $u_stmt = $db->prepare("SELECT last_activity, (SELECT status FROM user_logins WHERE user_id = :tid ORDER BY login_time DESC LIMIT 1) as current_status FROM users WHERE user_id = :tid2");
            $u_stmt->execute(['tid' => $with_user, 'tid2' => $with_user]);
            $u_info = $u_stmt->fetch(PDO::FETCH_ASSOC);
            $otherOnline = false;
            if ($u_info) {
                $lastAct = strtotime($u_info['last_activity'] ?? '');
                if ($u_info['current_status'] == 'active' && (time() - $lastAct < 300)) $otherOnline = true;
            }

            // Fetch the ID of the latest message I sent that the other user has read
            $read_stmt = $db->prepare("SELECT MAX(id) FROM staff_chat_messages WHERE sender_id = :uid AND receiver_id = :wid AND is_read = 1");
            $read_stmt->execute(['uid' => $user_id, 'wid' => $with_user]);
            $maxReadId = $read_stmt->fetchColumn() ?: 0;

            echo json_encode([
                'ok' => true, 
                'messages' => $messages, 
                'deleted_ids' => $deleted_ids, 
                'recipient_online' => $otherOnline,
                'max_read_id' => $maxReadId
            ]);
            break;

        case 'poll':
            // 1. Fetch unread message count grouped by sender (ignore deleted)
            $stmt = $db->prepare("
                SELECT m.sender_id, COUNT(*) as unread, MAX(u.username) as username 
                FROM staff_chat_messages m
                JOIN users u ON m.sender_id = u.user_id
                WHERE m.receiver_id = :uid AND m.is_read = 0 AND m.is_deleted = 0 
                GROUP BY m.sender_id
            ");
            $stmt->execute(['uid' => $user_id]);
            $unread_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $unread_counts = [];
            $unread_senders = [];
            foreach ($unread_data as $row) {
                $unread_counts[$row['sender_id']] = $row['unread'];
                $unread_senders[$row['sender_id']] = $row['username'];
            }

            // 2. Fetch pending requests where I am the target
            $stmt = $db->prepare("
                SELECT p.requester_id, u.username 
                FROM staff_chat_permissions p
                JOIN users u ON p.requester_id = u.user_id
                WHERE p.target_id = :uid AND p.status = 'pending'
            ");
            $stmt->execute(['uid' => $user_id]);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Background cleanup of old messages (> 2 days)
            $cleanup_stmt = $db->query("SELECT id, file_path FROM staff_chat_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 DAY)");
            $old_msgs = $cleanup_stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($old_msgs)) {
                $old_ids = [];
                foreach ($old_msgs as $om) {
                    $old_ids[] = $om['id'];
                    if (!empty($om['file_path'])) {
                        $fpath = __DIR__ . '/' . $om['file_path'];
                        if (file_exists($fpath)) unlink($fpath);
                    }
                }
                $ids_placeholder = str_repeat('?,', count($old_ids) - 1) . '?';
                $del_stmt = $db->prepare("DELETE FROM staff_chat_messages WHERE id IN ($ids_placeholder)");
                $del_stmt->execute($old_ids);
            }

            echo json_encode([
                'ok' => true, 
                'unread' => $unread_counts, 
                'senders' => $unread_senders,
                'requests' => $requests
            ]);
            break;

        case 'fetch_user_profile':
            $target_id = intval($_GET['target_id'] ?? 0);
            if (!$target_id) throw new Exception('Invalid user.');

            $stmt = $db->prepare("SELECT username, first_name, last_name, phone, about, created_at, profile_picture FROM users WHERE user_id = :tid");
            $stmt->execute(['tid' => $target_id]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$profile) throw new Exception('User not found.');

            $displayName = $profile['username'];
            if ($profile['first_name']) {
                $displayName = 'Dr. ' . $profile['first_name'] . ' ' . $profile['last_name'];
            }

            echo json_encode([
                'ok' => true, 
                'profile' => [
                    'name' => $displayName,
                    'phone' => $profile['phone'] ?: 'Not provided',
                    'about' => $profile['about'] ?: 'Available',
                    'join_date' => date('F Y', strtotime($profile['created_at'])),
                    'picture' => $profile['profile_picture'] ? ('../uploads/users/' . $profile['profile_picture']) : null
                ]
            ]);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
