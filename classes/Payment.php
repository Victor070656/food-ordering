<?php
/**
 * Payment Class
 * Handles payment tracking and reconciliation
 */

class Payment {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all payments with filters
     */
    public function getAll($filters = []) {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $where .= " AND p.payment_status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['method'])) {
            $where .= " AND p.payment_method = ?";
            $params[] = $filters['method'];
        }

        if (!empty($filters['date_from'])) {
            $where .= " AND DATE(p.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where .= " AND DATE(p.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['today'])) {
            $where .= " AND DATE(p.created_at) = CURDATE()";
        }

        // Pagination
        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 20;
        $offset = ($page - 1) * $perPage;

        // Get total count
        $countResult = $this->db->selectOne("SELECT COUNT(*) as total FROM payments p $where", $params);
        $total = (int)$countResult['total'];

        // Get payments
        $params[] = $perPage;
        $params[] = $offset;

        $payments = $this->db->select(
            "SELECT p.*,
                    o.order_number, o.customer_name, o.total_amount as order_amount,
                    CASE WHEN p.payment_status = 'paid' THEN p.amount ELSE 0 END as paid_amount
             FROM payments p
             INNER JOIN orders o ON p.order_id = o.id
             $where
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?",
            $params
        );

        return [
            'payments' => $payments,
            'pagination' => getPaginationInfo($total, $perPage, $page)
        ];
    }

    /**
     * Get payment by ID
     */
    public function getById($id) {
        return $this->db->selectOne(
            "SELECT p.*,
                    o.order_number, o.customer_name, o.customer_phone,
                    o.total_amount as order_amount, o.delivery_address
             FROM payments p
             INNER JOIN orders o ON p.order_id = o.id
             WHERE p.id = ?",
            [$id]
        );
    }

    /**
     * Get payment by order ID
     */
    public function getByOrderId($orderId) {
        return $this->db->selectOne(
            "SELECT * FROM payments WHERE order_id = ?",
            [$orderId]
        );
    }

    /**
     * Create payment record
     */
    public function create($data) {
        $paymentId = $this->db->insert(
            "INSERT INTO payments (order_id, amount, payment_method, payment_status, transaction_reference, notes)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['order_id'],
                $data['amount'],
                $data['payment_method'],
                $data['payment_status'] ?? 'pending',
                $data['transaction_reference'] ?? null,
                $data['notes'] ?? null
            ]
        );

        if ($paymentId) {
            return ['success' => true, 'payment_id' => $paymentId];
        }

        return ['success' => false, 'message' => 'Failed to create payment'];
    }

    /**
     * Update payment status
     */
    public function updateStatus($paymentId, $status, $reference = null) {
        $fields = ["payment_status = ?"];
        $params = [$status];

        if ($reference) {
            $fields[] = "transaction_reference = ?";
            $params[] = $reference;
        }

        if ($status === 'paid') {
            $fields[] = "paid_at = NOW()";
        }

        $params[] = $paymentId;
        $query = "UPDATE payments SET " . implode(', ', $fields) . " WHERE id = ?";

        $affected = $this->db->update($query, $params);

        if ($affected > 0) {
            // Also update order payment status
            $payment = $this->getById($paymentId);
            if ($payment) {
                $this->db->update(
                    "UPDATE orders SET payment_status = ? WHERE id = ?",
                    [$status, $payment['order_id']]
                );
            }

            return ['success' => true, 'message' => 'Payment status updated'];
        }

        return ['success' => false, 'message' => 'Failed to update payment status'];
    }

    /**
     * Get reconciliation report
     */
    public function getReconciliation($dateFrom, $dateTo) {
        // Summary by payment method
        $methodSummary = $this->db->select(
            "SELECT
                payment_method,
                COUNT(*) as total_transactions,
                SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN payment_status = 'failed' THEN amount ELSE 0 END) as failed_amount
             FROM payments
             WHERE DATE(created_at) BETWEEN ? AND ?
             GROUP BY payment_method",
            [$dateFrom, $dateTo]
        );

        // Summary by status
        $statusSummary = $this->db->select(
            "SELECT
                payment_status,
                COUNT(*) as count,
                COALESCE(SUM(amount), 0) as total_amount
             FROM payments
             WHERE DATE(created_at) BETWEEN ? AND ?
             GROUP BY payment_status",
            [$dateFrom, $dateTo]
        );

        // Pending payments with order details
        $pendingPayments = $this->db->select(
            "SELECT p.*, o.order_number, o.customer_name, o.customer_phone, o.created_at as order_date
             FROM payments p
             INNER JOIN orders o ON p.order_id = o.id
             WHERE p.payment_status = 'pending'
             AND DATE(p.created_at) BETWEEN ? AND ?
             ORDER BY p.created_at DESC",
            [$dateFrom, $dateTo]
        );

        // Daily breakdown
        $dailyBreakdown = $this->db->select(
            "SELECT
                DATE(created_at) as date,
                payment_method,
                COUNT(*) as count,
                SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as paid_amount
             FROM payments
             WHERE DATE(created_at) BETWEEN ? AND ?
             GROUP BY DATE(created_at), payment_method
             ORDER BY date DESC, payment_method",
            [$dateFrom, $dateTo]
        );

        return [
            'method_summary' => $methodSummary,
            'status_summary' => $statusSummary,
            'pending_payments' => $pendingPayments,
            'daily_breakdown' => $dailyBreakdown
        ];
    }

    /**
     * Get today's reconciliation summary
     */
    public function getTodaySummary() {
        $result = $this->db->selectOne(
            "SELECT
                COUNT(*) as total_transactions,
                SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as total_collected,
                SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END) as total_pending,
                SUM(CASE WHEN payment_status = 'failed' THEN amount ELSE 0 END) as total_failed,
                COUNT(CASE WHEN payment_method = 'cash_on_delivery' AND payment_status = 'paid' THEN 1 END) as cod_paid,
                SUM(CASE WHEN payment_method = 'cash_on_delivery' AND payment_status = 'paid' THEN amount ELSE 0 END) as cod_collected,
                COUNT(CASE WHEN payment_method = 'bank_transfer' AND payment_status = 'paid' THEN 1 END) as transfer_paid,
                SUM(CASE WHEN payment_method = 'bank_transfer' AND payment_status = 'paid' THEN amount ELSE 0 END) as transfer_collected,
                COUNT(CASE WHEN payment_method = 'pos' AND payment_status = 'paid' THEN 1 END) as pos_paid,
                SUM(CASE WHEN payment_method = 'pos' AND payment_status = 'paid' THEN amount ELSE 0 END) as pos_collected
             FROM payments
             WHERE DATE(created_at) = CURDATE()",
            []
        );

        return $result;
    }

    /**
     * Get failed payments
     */
    public function getFailedPayments($limit = 50) {
        return $this->db->select(
            "SELECT p.*, o.order_number, o.customer_name, o.customer_phone, o.created_at
             FROM payments p
             INNER JOIN orders o ON p.order_id = o.id
             WHERE p.payment_status = 'failed'
             ORDER BY p.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Mark payment as paid
     */
    public function markAsPaid($paymentId, $reference = null) {
        return $this->updateStatus($paymentId, 'paid', $reference);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed($paymentId, $notes = null) {
        $affected = $this->db->update(
            "UPDATE payments SET payment_status = 'failed', notes = ? WHERE id = ?",
            [$notes, $paymentId]
        );

        if ($affected > 0) {
            // Update order payment status
            $payment = $this->getById($paymentId);
            if ($payment) {
                $this->db->update(
                    "UPDATE orders SET payment_status = 'failed' WHERE id = ?",
                    [$payment['order_id']]
                );
            }

            return ['success' => true, 'message' => 'Payment marked as failed'];
        }

        return ['success' => false, 'message' => 'Failed to update payment'];
    }
}
