<?php
require_once('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $id = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;
    $name = trim($data['full_name']);
    $contact = isset($data['contact_number']) ? trim($data['contact_number']) : null;
    $address = isset($data['address']) ? trim($data['address']) : null;

    $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;

    // 🚨 START TRANSACTION
    $conn->begin_transaction();

    try {
        if ($id) {
            // ==========================================
            // FLOW 1: UPDATE EXISTING CUSTOMER (DELTA LOG)
            // ==========================================
            
            // 1. Fetch "Before" Snapshot
            $old_stmt = $conn->prepare("SELECT full_name, contact_number, address FROM customer WHERE customer_id = ?");
            $old_stmt->bind_param("i", $id);
            $old_stmt->execute();
            $old_data = $old_stmt->get_result()->fetch_assoc();

            // 2. Execute Update
            $stmt = $conn->prepare("UPDATE customer SET full_name=?, contact_number=?, address=? WHERE customer_id=?");
            $stmt->bind_param("sssi", $name, $contact, $address, $id);
            $stmt->execute();

            // 3. Calculate Deltas and Log
            if ($admin_id > 0 && $old_data) {
                $changes = [];
                
                if ($old_data['full_name'] !== $name) {
                    $changes[] = ['field' => 'Full Name / Organization', 'old' => $old_data['full_name'], 'new' => $name];
                }
                
                // Treat empty strings and nulls interchangeably for comparison
                $old_contact = $old_data['contact_number'] ?: 'None';
                $new_contact = $contact ?: 'None';
                if ($old_contact !== $new_contact) {
                    $changes[] = ['field' => 'Contact Number', 'old' => $old_contact, 'new' => $new_contact];
                }
                
                $old_address = $old_data['address'] ?: 'None';
                $new_address = $address ?: 'None';
                if ($old_address !== $new_address) {
                    $changes[] = ['field' => 'Address', 'old' => $old_address, 'new' => $new_address];
                }

                // Only log if something actually changed
                if (!empty($changes)) {
                    $formatted_id = "CUST-" . str_pad($id, 4, '0', STR_PAD_LEFT);
                    $change_count = count($changes);

                    $log_payload = json_encode([
                        'is_detailed' => true,
                        'type' => 'update_comparison',
                        'summary' => "Modified $change_count field(s) in customer profile.",
                        'project' => $formatted_id . ' - ' . $name,
                        'changes' => $changes
                    ]);

                    $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'UPDATE', 'customer', ?, ?)");
                    $log_stmt->bind_param("iis", $admin_id, $id, $log_payload);
                    $log_stmt->execute();
                }
            }

        } else {
            // ==========================================
            // FLOW 2: CREATE NEW CUSTOMER
            // ==========================================
            
            $stmt = $conn->prepare("INSERT INTO customer (full_name, contact_number, address) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $contact, $address);
            $stmt->execute();
            
            $new_id = $conn->insert_id;

            // Log the Creation
            if ($admin_id > 0) {
                $formatted_id = "CUST-" . str_pad($new_id, 4, '0', STR_PAD_LEFT);
                $description = "Created new customer profile for $name ($formatted_id).";

                $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'CREATE', 'customer', ?, ?)");
                $log_stmt->bind_param("iis", $admin_id, $new_id, $description);
                $log_stmt->execute();
            }
        }

        // 🚨 COMMIT TRANSACTION: Everything succeeded!
        $conn->commit();
        echo json_encode(["status" => "success"]);

    } catch (Exception $e) {
        // 🚨 ROLLBACK TRANSACTION: An error occurred, revert changes
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid payload received."]);
}