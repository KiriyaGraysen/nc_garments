<?php
require_once('../config/database.php');

$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['project_id'])) {
    $id = (int)$data['project_id'];
    $name = trim($data['project_name']);
    $due = empty($data['due_date']) ? null : trim($data['due_date']);
    $qty = (int)$data['quantity'];
    
    $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;

    $conn->begin_transaction();

    try {
        // ==========================================
        // 1. CAPTURE "BEFORE" SNAPSHOT
        // ==========================================
        $old_stmt = $conn->prepare("SELECT project_name, due_date, quantity FROM project WHERE project_id = ?");
        $old_stmt->bind_param("i", $id);
        $old_stmt->execute();
        $old_data = $old_stmt->get_result()->fetch_assoc();
        
        // Helper function to format sizes/measurements into a readable string
        function getSizingString($conn, $id) {
            $sz_res = $conn->query("SELECT size_label, quantity FROM project_sizing WHERE project_id = $id");
            if ($sz_res->num_rows > 0) {
                $arr = [];
                while($r = $sz_res->fetch_assoc()) $arr[] = $r['size_label'].": ".$r['quantity'];
                return "Standard (" . implode(", ", $arr) . ")";
            }
            $ms_res = $conn->query("SELECT body_part, measurement_value, unit FROM project_measurement WHERE project_id = $id");
            if ($ms_res->num_rows > 0) {
                $arr = [];
                while($r = $ms_res->fetch_assoc()) $arr[] = $r['body_part'].": ".$r['measurement_value'].$r['unit'];
                return "Custom (" . implode(", ", $arr) . ")";
            }
            return "None";
        }

        $old_sizing_str = getSizingString($conn, $id);

        // ==========================================
        // 2. EXECUTE ALL UPDATES (Your existing logic)
        // ==========================================
        $stmt = $conn->prepare("UPDATE project SET project_name = ?, due_date = ?, quantity = ? WHERE project_id = ?");
        $stmt->bind_param("ssii", $name, $due, $qty, $id);
        $stmt->execute();

        $sizingType = $data['sizing_type'] ?? 'unknown';
        if (isset($data['sizing_type']) && isset($data['sizing_data'])) {
            $sizingData = json_decode($data['sizing_data'], true);
            
            if ($sizingType === 'none') {
                $conn->query("DELETE FROM project_sizing WHERE project_id = $id");
                $conn->query("DELETE FROM project_measurement WHERE project_id = $id");
            } elseif ($sizingType === 'standard') {
                $conn->query("DELETE FROM project_measurement WHERE project_id = $id"); 
                
                $existing = [];
                $get_existing = $conn->query("SELECT size_label, quantity FROM project_sizing WHERE project_id = $id");
                while($row = $get_existing->fetch_assoc()) $existing[$row['size_label']] = (int)$row['quantity'];

                $new_labels = [];
                $update_stmt = $conn->prepare("UPDATE project_sizing SET quantity = ? WHERE project_id = ? AND size_label = ?");
                $insert_stmt = $conn->prepare("INSERT INTO project_sizing (project_id, size_label, quantity) VALUES (?, ?, ?)");

                foreach ($sizingData as $size) {
                    $label = trim($size['label']);
                    if(empty($label)) continue; 
                    
                    $sizeQty = (int)$size['qty'];
                    $new_labels[] = $label;

                    if (array_key_exists($label, $existing)) {
                        if ($existing[$label] !== $sizeQty) {
                            $update_stmt->bind_param("iis", $sizeQty, $id, $label);
                            $update_stmt->execute();
                        }
                    } else {
                        $insert_stmt->bind_param("isi", $id, $label, $sizeQty);
                        $insert_stmt->execute();
                    }
                }
                $to_delete = array_diff(array_keys($existing), $new_labels);
                if (!empty($to_delete)) {
                    $del_stmt = $conn->prepare("DELETE FROM project_sizing WHERE project_id = ? AND size_label = ?");
                    foreach ($to_delete as $del_label) {
                        $del_stmt->bind_param("is", $id, $del_label);
                        $del_stmt->execute();
                    }
                }
            } elseif ($sizingType === 'custom') {
                $conn->query("DELETE FROM project_sizing WHERE project_id = $id"); 
                
                $existing = [];
                $get_existing = $conn->query("SELECT body_part, measurement_value, unit FROM project_measurement WHERE project_id = $id");
                while($row = $get_existing->fetch_assoc()) {
                    $existing[$row['body_part']] = ['val' => (float)$row['measurement_value'], 'unit' => $row['unit']];
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
                        if ($existing[$part]['val'] !== $val || $existing[$part]['unit'] !== $unit) {
                            $update_stmt->bind_param("dsis", $val, $unit, $id, $part);
                            $update_stmt->execute();
                        }
                    } else {
                        $insert_stmt->bind_param("isds", $id, $part, $val, $unit);
                        $insert_stmt->execute();
                    }
                }
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

        // ==========================================
        // 3. CAPTURE "AFTER" SNAPSHOT & BUILD DELTA LOG
        // ==========================================
        $new_sizing_str = getSizingString($conn, $id);
        
        $changes = [];
        
        if ($old_data['project_name'] !== $name) {
            $changes[] = ['field' => 'Project Name', 'old' => $old_data['project_name'], 'new' => $name];
        }
        if ($old_data['due_date'] !== $due) {
            $changes[] = ['field' => 'Due Date', 'old' => $old_data['due_date'] ?? 'None', 'new' => $due ?? 'None'];
        }
        if ((int)$old_data['quantity'] !== $qty) {
            $changes[] = ['field' => 'Total Quantity', 'old' => $old_data['quantity'], 'new' => $qty];
        }
        if ($old_sizing_str !== $new_sizing_str) {
            $changes[] = ['field' => 'Sizing Breakdown', 'old' => $old_sizing_str, 'new' => $new_sizing_str];
        }

        // Only log if something actually changed!
        if (!empty($changes) && $admin_id > 0) {
            $formatted_prj = "PRJ-" . str_pad($id, 4, '0', STR_PAD_LEFT);
            $change_count = count($changes);
            
            // Using our new 'update_comparison' JSON type
            $log_payload = json_encode([
                'is_detailed' => true,
                'type' => 'update_comparison',
                'summary' => "Modified $change_count field(s) in project record.",
                'project' => $formatted_prj . ' - ' . $name,
                'changes' => $changes
            ]);

            $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, 'UPDATE', 'project', ?, ?)");
            $log_stmt->bind_param("iis", $admin_id, $id, $log_payload);
            $log_stmt->execute();
        }

        $conn->commit();
        echo json_encode(["status" => "success"]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
}