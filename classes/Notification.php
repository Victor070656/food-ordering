<?php
/**
 * Notification Class
 * Handles SMS and WhatsApp notifications
 * Abstracted to work with multiple providers
 */

class Notification {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Send notification
     */
    public function send($orderId, $type, $channel = 'sms') {
        $order = $this->db->selectOne(
            "SELECT o.*, u.name as rider_name, u.phone as rider_phone
             FROM orders o
             LEFT JOIN riders r ON o.rider_id = r.id
             LEFT JOIN users u ON r.user_id = u.id
             WHERE o.id = ?",
            [$orderId]
        );

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        // Get notification template
        $template = $this->db->selectOne(
            "SELECT * FROM notification_templates WHERE type = ? AND is_active = 1",
            [$type]
        );

        if (!$template) {
            return ['success' => false, 'message' => 'Template not found'];
        }

        // Get message based on channel
        $message = $channel === 'whatsapp' ? $template['whatsapp_template'] : $template['sms_template'];

        // Replace placeholders
        $placeholders = [
            '{customer_name}' => $order['customer_name'],
            '{order_number}' => $order['order_number'],
            '{total_amount}' => formatCurrency($order['total_amount']),
            '{rider_name}' => $order['rider_name'] ?? 'Not assigned',
            '{rider_phone}' => $order['rider_phone'] ?? 'Not assigned'
        ];

        foreach ($placeholders as $key => $value) {
            $message = str_replace($key, $value, $message);
        }

        $recipientPhone = formatPhoneNumber($order['customer_phone']);

        // Send based on channel
        $result = $channel === 'whatsapp' ? $this->sendWhatsApp($recipientPhone, $message) : $this->sendSMS($recipientPhone, $message);

        // Log notification
        $this->logNotification(
            $orderId,
            $recipientPhone,
            $type,
            $channel,
            $message,
            $result['success'] ? 'sent' : 'failed',
            $result['response'] ?? null
        );

        return $result;
    }

    /**
     * Send SMS
     * Abstracted to work with Termii, Twilio, etc.
     */
    private function sendSMS($phone, $message) {
        if (!SMS_ENABLED) {
            return ['success' => true, 'message' => 'SMS disabled - logging only', 'response' => 'SMS disabled'];
        }

        // Example implementation for Termii (Nigeria)
        // $apiKey = SMS_API_KEY;
        // $senderId = SMS_SENDER_ID;

        // $endpoint = "https://termii.com/api/sms/send";
        // $data = [
        //     'to' => $phone,
        //     'from' => $senderId,
        //     'sms' => $message,
        //     'type' => 'plain',
        //     'channel' => 'dnd',
        //     'api_key' => $apiKey
        // ];

        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $endpoint);
        // curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        // $response = curl_exec($ch);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close($ch);

        // $result = json_decode($response, true);
        // return ['success' => $httpCode == 200, 'response' => $response];

        return ['success' => true, 'message' => 'SMS simulated', 'response' => 'Simulation mode'];
    }

    /**
     * Send WhatsApp message
     * Abstracted to work with WhatsApp Cloud API, Twilio, etc.
     */
    private function sendWhatsApp($phone, $message) {
        if (!WHATSAPP_ENABLED) {
            return ['success' => true, 'message' => 'WhatsApp disabled - logging only', 'response' => 'WhatsApp disabled'];
        }

        // Example implementation for WhatsApp Cloud API
        // $phoneId = WHATSAPP_PHONE_ID;
        // $accessToken = WHATSAPP_ACCESS_TOKEN;
        // $version = 'v18.0';

        // $endpoint = "https://graph.facebook.com/$version/$phoneId/messages";
        // $data = [
        //     'messaging_product' => 'whatsapp',
        //     'to' => $phone,
        //     'type' => 'text',
        //     'text' => ['body' => $message]
        // ];

        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $endpoint);
        // curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, [
        //     'Content-Type: application/json',
        //     'Authorization: Bearer ' . $accessToken
        // ]);
        // $response = curl_exec($ch);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close($ch);

        // return ['success' => $httpCode == 200, 'response' => $response];

        return ['success' => true, 'message' => 'WhatsApp simulated', 'response' => 'Simulation mode'];
    }

    /**
     * Log notification
     */
    private function logNotification($orderId, $recipientPhone, $type, $channel, $message, $status, $response = null) {
        $this->db->insert(
            "INSERT INTO notification_logs (order_id, recipient_phone, type, channel, message, status, response)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$orderId, $recipientPhone, $type, $channel, $message, $status, $response]
        );
    }

    /**
     * Get notification templates
     */
    public function getTemplates() {
        return $this->db->select(
            "SELECT * FROM notification_templates ORDER BY type ASC"
        );
    }

    /**
     * Get template by type
     */
    public function getTemplate($type) {
        return $this->db->selectOne(
            "SELECT * FROM notification_templates WHERE type = ?",
            [$type]
        );
    }

    /**
     * Update template
     */
    public function updateTemplate($id, $smsTemplate, $whatsappTemplate, $isActive = null) {
        $fields = ["sms_template = ?", "whatsapp_template = ?"];
        $params = [$smsTemplate, $whatsappTemplate];

        if ($isActive !== null) {
            $fields[] = "is_active = ?";
            $params[] = $isActive ? 1 : 0;
        }

        $params[] = $id;
        $query = "UPDATE notification_templates SET " . implode(', ', $fields) . " WHERE id = ?";

        $affected = $this->db->update($query, $params);

        return ['success' => $affected > 0];
    }

    /**
     * Get notification logs
     */
    public function getLogs($filters = []) {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['order_id'])) {
            $where .= " AND nl.order_id = ?";
            $params[] = $filters['order_id'];
        }

        if (!empty($filters['type'])) {
            $where .= " AND nl.type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['channel'])) {
            $where .= " AND nl.channel = ?";
            $params[] = $filters['channel'];
        }

        if (!empty($filters['status'])) {
            $where .= " AND nl.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['date'])) {
            $where .= " AND DATE(nl.created_at) = ?";
            $params[] = $filters['date'];
        }

        // Pagination
        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 50;
        $offset = ($page - 1) * $perPage;

        // Get total count
        $countResult = $this->db->selectOne("SELECT COUNT(*) as total FROM notification_logs nl $where", $params);
        $total = (int)$countResult['total'];

        // Get logs
        $params[] = $perPage;
        $params[] = $offset;

        $logs = $this->db->select(
            "SELECT nl.*, o.order_number, o.customer_name
             FROM notification_logs nl
             INNER JOIN orders o ON nl.order_id = o.id
             $where
             ORDER BY nl.created_at DESC
             LIMIT ? OFFSET ?",
            $params
        );

        return [
            'logs' => $logs,
            'pagination' => getPaginationInfo($total, $perPage, $page)
        ];
    }

    /**
     * Resend notification
     */
    public function resend($logId) {
        $log = $this->db->selectOne(
            "SELECT * FROM notification_logs WHERE id = ?",
            [$logId]
        );

        if (!$log) {
            return ['success' => false, 'message' => 'Log not found'];
        }

        return $this->send(
            $log['order_id'],
            $log['type'],
            $log['channel']
        );
    }
}
