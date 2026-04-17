<?php
require_once('../config/database.php');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['project_id'])) {
    $id = (int)$data['project_id'];
    $name = trim($data['project_name']);
    $due = empty($data['due_date']) ? null : $data['due_date'];
    $qty = (int)$data['quantity'];
    
    // 🚨 START TRANSACTION: Guarantees all updates happen safely
    $conn->begin_transaction();

    try {
        // 1. Update the main Project Table
        $stmt = $conn->prepare("UPDATE project SET project_name = ?, due_date = ?, quantity = ? WHERE project_id = ?");
        $stmt->bind_param("ssii", $name, $due, $qty, $id);
        $stmt->execute();

        // 2. Perform a "Delta Sync" on the Sizing/Measurements
        if (isset($data['sizing_type']) && isset($data['sizing_data'])) {
            $sizingType = $data['sizing_type'];
            $sizingData = json_decode($data['sizing_data'], true);
            
            if ($sizingType === 'none') {
                // Checkbox was UNCHECKED! Wipe all sizing data.
                $conn->query("DELETE FROM project_sizing WHERE project_id = $id");
                $conn->query("DELETE FROM project_measurement WHERE project_id = $id");
                
            } elseif ($sizingType === 'standard') {
                $conn->query("DELETE FROM project_measurement WHERE project_id = $id"); // Wipe custom
                
                $existing = [];
                $get_existing = $conn->prepare("SELECT size_label, quantity FROM project_sizing WHERE project_id = ?");
                $get_existing->bind_param("i", $id);
                $get_existing->execute();
                $res = $get_existing->get_result();
                while($row = $res->fetch_assoc()) {
                    $existing[$row['size_label']] = (int)$row['quantity'];
                }

                $new_labels = [];
                $update_stmt = $conn->prepare("UPDATE project_sizing SET quantity = ? WHERE project_id = ? AND size_label = ?");
                $insert_stmt = $conn->prepare("INSERT INTO project_sizing (project_id, size_label, quantity) VALUES (?, ?, ?)");

                foreach ($sizingData as $size) {
                    $label = trim($size['label']);
                    if(empty($label)) continue; 
                    
                    $sizeQty = (int)$size['qty'];
                    $new_labels[] = $label;

                    if (array_key_exists($label, $existing)) {
                        // Only run UPDATE if the value actually changed
                        if ($existing[$label] !== $sizeQty) {
                            $update_stmt->bind_param("iis", $sizeQty, $id, $label);
                            $update_stmt->execute();
                        }
                    } else {
                        $insert_stmt->bind_param("isi", $id, $label, $sizeQty);
                        $insert_stmt->execute();
                    }
                }

                // Delete sizes that the user removed
                $to_delete = array_diff(array_keys($existing), $new_labels);
                if (!empty($to_delete)) {
                    $del_stmt = $conn->prepare("DELETE FROM project_sizing WHERE project_id = ? AND size_label = ?");
                    foreach ($to_delete as $del_label) {
                        $del_stmt->bind_param("is", $id, $del_label);
                        $del_stmt->execute();
                    }
                }

            } elseif ($sizingType === 'custom') {
                $conn->query("DELETE FROM project_sizing WHERE project_id = $id"); // Wipe standard
                
                $existing = [];
                $get_existing = $conn->prepare("SELECT body_part, measurement_value, unit FROM project_measurement WHERE project_id = ?");
                $get_existing->bind_param("i", $id);
                $get_existing->execute();
                $res = $get_existing->get_result();
                while($row = $res->fetch_assoc()) {
                    $existing[$row['body_part']] = [
                        'val' => (float)$row['measurement_value'], 
                        'unit' => $row['unit']
                    ];
                }

                $new_parts = [];
                $update_stmt = $conn->prepare("UPDATE project_measurement SET measurement_value = ?, unit = ? WHERE project_id = ? AND body_part = ?");
                $insert_stmt = $conn->prepare("INSERT INTO project_measurement (project_id, body_part, measurement_value, unit) VALUES (?, ?, ?, ?)");

                foreach ($sizingData as $measure) {
                    $part = trim($measure['part']);
                    if(empty($part)) continue; 
                    
                    $val = (float)$measure['val'];
                    $unit = trim($measure['unit']);
                    $new_parts[] = $part;

                    if (array_key_exists($part, $existing)) {
                        // Only run UPDATE if the value or unit actually changed
                        if ($existing[$part]['val'] !== $val || $existing[$part]['unit'] !== $unit) {
                            $update_stmt->bind_param("dsis", $val, $unit, $id, $part);
                            $update_stmt->execute();
                        }
                    } else {
                        $insert_stmt->bind_param("isds", $id, $part, $val, $unit);
                        $insert_stmt->execute();
                    }
                }

                // Delete measurements that the user removed
                $to_delete = array_diff(array_keys($existing), $new_parts);
                if (!empty($to_delete)) {
                    $del_stmt = $conn->prepare("DELETE FROM project_measurement WHERE project_id = ? AND body_part = ?");
                    foreach ($to_delete as $del_part) {
                        $del_stmt->bind_param("is", $id, $del_part);
                        $del_stmt->execute();
                    }
                }
            }
        }

        // 🚨 COMMIT TRANSACTION: Everything succeeded!
        $conn->commit();
        echo json_encode(["status" => "success"]);

    } catch (Exception $e) {
        // 🚨 ROLLBACK TRANSACTION: An error occurred, revert all changes
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
}