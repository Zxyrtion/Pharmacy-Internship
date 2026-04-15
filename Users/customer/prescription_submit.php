<?php
require_once '../../config.php';

if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }
if ($_SESSION['role_name'] !== 'Customer') { header('Location: ../../index.php'); exit(); }

$full_name = $_SESSION['full_name'];
$success = '';
$errors = [];

// Note: Using existing prescriptions table structure
// Table has: customer_id, prescription_id, patient_id, patient_name, medicine_name, 
// dosage, quantity, instructions, doctor_name, date_prescribed, status

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_name   = trim($_POST['doctor_name'] ?? '');
    $patient_name  = trim($_POST['patient_name'] ?? '');
    $rx_date       = trim($_POST['prescription_date'] ?? date('Y-m-d'));
    $items         = $_POST['items'] ?? [];

    if (empty($doctor_name))  $errors[] = 'Doctor name is required.';
    if (empty($patient_name)) $errors[] = 'Patient name is required.';
    if (empty($rx_date))      $errors[] = 'Prescription date is required.';

    $filtered = array_filter($items, fn($i) => !empty($i['medicine_name']));
    if (empty($filtered)) $errors[] = 'At least one medicine is required.';

    if (empty($errors)) {
        // Generate unique prescription_id
        $prescription_id = 'RX-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Get customer_id from session
        $customer_id = (int)$_SESSION['user_id'];
        
        // Generate a unique patient_id (use timestamp + random for uniqueness)
        // Or use customer_id if patient is the customer themselves
        $patient_id = $customer_id;
        
        // Insert each medicine as a separate row (based on existing table structure)
        $stmt = $conn->prepare("INSERT INTO prescriptions 
            (customer_id, prescription_id, patient_id, patient_name, medicine_name, dosage, quantity, instructions, doctor_name, date_prescribed, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
        
        if ($stmt === false) {
            $errors[] = 'Database error: ' . $conn->error;
        } else {
            $success_count = 0;
            foreach ($filtered as $item) {
                $med  = $item['medicine_name'];
                $dosage = $item['generic_name'] ?? ''; // Using generic_name field as dosage
                $qty  = $item['quantity'] ?? '1';
                $instructions  = $item['sig'] ?? '';
                
                $stmt->bind_param('isiissssss',
                    $customer_id, $prescription_id, $patient_id, $patient_name,
                    $med, $dosage, $qty, $instructions, $doctor_name, $rx_date);
                
                if ($stmt->execute()) {
                    $success_count++;
                }
            }
            
            if ($success_count > 0) {
                $success = 'Prescription ' . $prescription_id . ' submitted successfully with ' . $success_count . ' medicine(s). Awaiting pharmacist processing.';
            } else {
                $errors[] = 'Failed to save prescription items.';
            }
        }
    }
}

$rows = isset($_POST['items']) ? $_POST['items'] : array_fill(0, 3, []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Prescription - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; }
        .rx-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 2rem; margin-top: 1.5rem; }
        .rx-header { text-align: center; border-bottom: 2px solid #c0392b; padding-bottom: 1rem; margin-bottom: 1.5rem; }
        .rx-header h2 { color: #c0392b; font-weight: 700; font-style: italic; }
        .rx-header p { color: #2563b0; font-weight: 600; margin: 0; }
        .rx-title { text-align: center; font-weight: 700; font-size: 1.1rem; letter-spacing: 2px; margin: 1rem 0; }
        .med-table thead { background: #2c3e50; color: white; }
        .med-table .form-control { border: none; border-bottom: 1px solid #ccc; border-radius: 0; font-size: 0.9rem; padding: 4px 6px; }
        .med-table .form-control:focus { box-shadow: none; border-bottom-color: #c0392b; }
        .validity-note { color: #c0392b; font-style: italic; font-size: 0.85rem; }
        .sig-section { border-top: 1px solid #ddd; margin-top: 1.5rem; padding-top: 1rem; }
        .sig-line { border-top: 1px solid #333; margin-top: 2rem; padding-top: 0.3rem; font-size: 0.85rem; color: #555; }
        @media print { .no-print { display: none !important; } body { background: white; } .rx-card { box-shadow: none; } }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm no-print">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-hospital"></i> MediCare Pharmacy</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($full_name) ?></span>
            <a href="../logout.php" class="btn btn-danger btn-sm rounded-pill"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="no-print mt-3 mb-2">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success no-print mt-3"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger no-print mt-3">
            <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="rx-card">
        <form method="POST" id="rxForm">
            <!-- Doctor Header -->
            <div class="rx-header">
                <div class="mb-2">
                    <input type="text" name="doctor_name" class="form-control text-center fw-bold fs-5"
                           style="color:#c0392b; border:none; border-bottom:1px solid #ccc;"
                           value="<?= htmlspecialchars($_POST['doctor_name'] ?? '') ?>"
                           placeholder="Doctor Full Name, MD" required>
                </div>
                <input type="text" name="doctor_specialization" class="form-control text-center"
                       style="color:#2563b0; border:none; border-bottom:1px solid #ccc;"
                       value="<?= htmlspecialchars($_POST['doctor_specialization'] ?? '') ?>"
                       placeholder="Specialization">
            </div>

            <!-- Date & Patient Info -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Prescription Date</label>
                    <input type="date" name="prescription_date" class="form-control"
                           value="<?= htmlspecialchars($_POST['prescription_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Patient Name</label>
                    <input type="text" name="patient_name" class="form-control"
                           value="<?= htmlspecialchars($_POST['patient_name'] ?? $full_name) ?>"
                           placeholder="Last Name, First Name" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Age</label>
                    <input type="text" name="patient_age" class="form-control"
                           value="<?= htmlspecialchars($_POST['patient_age'] ?? '') ?>" placeholder="e.g. 61 y">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Gender</label>
                    <select name="patient_gender" class="form-select">
                        <option value="">Select</option>
                        <option value="Male" <?= ($_POST['patient_gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($_POST['patient_gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Date of Birth</label>
                    <input type="date" name="patient_dob" class="form-control"
                           value="<?= htmlspecialchars($_POST['patient_dob'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Next Appointment</label>
                    <input type="date" name="next_appointment" class="form-control"
                           value="<?= htmlspecialchars($_POST['next_appointment'] ?? '') ?>">
                </div>
            </div>

            <!-- PRESCRIPTION label -->
            <div class="rx-title">PRESCRIPTION</div>

            <!-- Medicine Items -->
            <div class="table-responsive mb-3">
                <table class="table table-bordered med-table">
                    <thead>
                        <tr>
                            <th style="width:30%">Medicine Name</th>
                            <th style="width:25%">Generic Name</th>
                            <th style="width:10%">Qty (#)</th>
                            <th style="width:30%">Sig. (Instructions)</th>
                            <th class="no-print" style="width:5%"></th>
                        </tr>
                    </thead>
                    <tbody id="medBody">
                        <?php foreach ($rows as $i => $item): ?>
                        <tr>
                            <td><input type="text" name="items[<?= $i ?>][medicine_name]" class="form-control"
                                       value="<?= htmlspecialchars($item['medicine_name'] ?? '') ?>" placeholder="e.g. BASAGLAR KWIKPEN 100U/ML"></td>
                            <td><input type="text" name="items[<?= $i ?>][generic_name]" class="form-control"
                                       value="<?= htmlspecialchars($item['generic_name'] ?? '') ?>" placeholder="e.g. INSULIN GLARGINE"></td>
                            <td><input type="text" name="items[<?= $i ?>][quantity]" class="form-control"
                                       value="<?= htmlspecialchars($item['quantity'] ?? '') ?>" placeholder="#1"></td>
                            <td><input type="text" name="items[<?= $i ?>][sig]" class="form-control"
                                       value="<?= htmlspecialchars($item['sig'] ?? '') ?>" placeholder="Sig.: instructions"></td>
                            <td class="no-print text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="no-print mb-3">
                <button type="button" class="btn btn-outline-primary btn-sm" id="addMed">
                    <i class="bi bi-plus-circle"></i> Add Medicine
                </button>
            </div>

            <!-- Notes -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Additional Notes / Next Appointment Instructions</label>
                <textarea name="notes" class="form-control" rows="2"
                          placeholder="e.g. Monitor CBG at home 2x/day..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
            </div>

            <p class="validity-note">This prescription is valid for THREE (3) MONTHS from the date of issue.</p>

            <!-- Doctor Info Footer -->
            <div class="sig-section row">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Clinic / Hospital</label>
                    <input type="text" name="doctor_clinic" class="form-control"
                           value="<?= htmlspecialchars($_POST['doctor_clinic'] ?? '') ?>"
                           placeholder="e.g. Davao Doctors Hospital, 324 Medical Tower">
                    <div class="mt-2">
                        <label class="form-label fw-semibold">Doctor Contact</label>
                        <input type="text" name="doctor_contact" class="form-control"
                               value="<?= htmlspecialchars($_POST['doctor_contact'] ?? '') ?>"
                               placeholder="e.g. 0949-9720815">
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="sig-line">
                        <input type="text" name="doctor_prc" class="form-control text-end"
                               style="border:none; border-bottom:1px solid #ccc;"
                               value="<?= htmlspecialchars($_POST['doctor_prc'] ?? '') ?>"
                               placeholder="PRC No.">
                        <input type="text" name="doctor_ptr" class="form-control text-end mt-1"
                               style="border:none; border-bottom:1px solid #ccc;"
                               value="<?= htmlspecialchars($_POST['doctor_ptr'] ?? '') ?>"
                               placeholder="PTR No.">
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4 no-print">
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-send"></i> Submit Prescription</button>
                <button type="button" class="btn btn-outline-secondary px-4" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
                <button type="reset" class="btn btn-outline-danger px-4"><i class="bi bi-x-circle"></i> Clear</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let rowIdx = <?= count($rows) ?>;

    function attachRemove(row) {
        row.querySelector('.remove-row').addEventListener('click', () => {
            if (document.querySelectorAll('#medBody tr').length > 1) row.remove();
        });
    }
    document.querySelectorAll('#medBody tr').forEach(attachRemove);

    document.getElementById('addMed').addEventListener('click', () => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="items[${rowIdx}][medicine_name]" class="form-control" placeholder="e.g. BASAGLAR KWIKPEN 100U/ML"></td>
            <td><input type="text" name="items[${rowIdx}][generic_name]" class="form-control" placeholder="e.g. INSULIN GLARGINE"></td>
            <td><input type="text" name="items[${rowIdx}][quantity]" class="form-control" placeholder="#1"></td>
            <td><input type="text" name="items[${rowIdx}][sig]" class="form-control" placeholder="Sig.: instructions"></td>
            <td class="no-print text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button>
            </td>`;
        document.getElementById('medBody').appendChild(tr);
        attachRemove(tr);
        rowIdx++;
    });
</script>
</body>
</html>
