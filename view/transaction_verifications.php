<?php
require_once '../controller/auth_header.php';

if (!in_array($current_user['user_type'], ['owner', 'manager'])) {
    header("Location: ../controller/dashboard_controller.php");
    exit();
}

// Get all verifications
$query = "SELECT tv.*, up.full_name as tenant_name, f.flat_number, b.building_name
          FROM transaction_verifications tv
          JOIN users u ON tv.tenant_id = u.user_id
          JOIN user_profiles up ON u.user_id = up.user_id
          JOIN flat_assignments fa ON tv.assignment_id = fa.assignment_id
          JOIN flats f ON fa.flat_id = f.flat_id
          JOIN buildings b ON f.building_id = b.building_id
          ORDER BY tv.verified_at DESC
          LIMIT 100";

$result = execute_prepared_query($query, array(), '');
$verifications = $result ? fetch_all_rows($result) : array();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transaction Verifications - SmartRent</title>
    <link rel="stylesheet" href="../view/css/dashboard.css">
</head>
<body>
    <h1>Transaction Verifications Log</h1>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Transaction Number</th>
                <th>Tenant</th>
                <th>Flat</th>
                <th>Expected Amount</th>
                <th>Verified Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($verifications as $v): ?>
            <tr>
                <td><?php echo date('M d, Y H:i', strtotime($v['verified_at'])); ?></td>
                <td><strong><?php echo htmlspecialchars($v['transaction_number']); ?></strong></td>
                <td><?php echo htmlspecialchars($v['tenant_name']); ?></td>
                <td><?php echo htmlspecialchars($v['building_name'] . ' - ' . $v['flat_number']); ?></td>
                <td>৳<?php echo number_format($v['expected_amount'], 2); ?></td>
                <td>৳<?php echo number_format($v['verified_amount'], 2); ?></td>
                <td><span class="status <?php echo $v['verification_status']; ?>"><?php echo ucfirst($v['verification_status']); ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>