<?php
// Universal Sidebar Component for MediCare Pharmacy System
// This file generates the appropriate sidebar based on user role

function getSidebarConfig($role) {
    $configs = [
        'HR Personnel' => [
            'title' => 'MediCare HR',
            'subtitle' => 'Human Resources Portal',
            'items' => [
                ['icon' => 'bi-speedometer2', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
                ['icon' => 'bi-file-earmark-text', 'text' => 'Applications', 'url' => 'internship_applications.php'],
                ['icon' => 'bi-calendar-event', 'text' => 'Interviews', 'url' => 'interview_schedule.php'],
                ['icon' => 'bi-people', 'text' => 'Employees', 'url' => 'employees.php'],
                ['icon' => 'bi-calendar-week', 'text' => 'Schedules', 'url' => 'create_work_schedule.php'],
                ['icon' => 'bi-clock-history', 'text' => 'Attendance', 'url' => 'attendance.php'],
                ['icon' => 'bi-list-task', 'text' => 'Tasks', 'url' => 'assign_task.php'],
                ['icon' => 'bi-star', 'text' => 'Evaluations', 'url' => 'view_evaluation.php'],
                ['icon' => 'bi-file-text', 'text' => 'Policies', 'url' => 'pharmacy_policies.php'],
                ['icon' => 'bi-check-circle', 'text' => 'Ready Interns', 'url' => 'view_ready_interns.php'],
                ['icon' => 'bi-file-earmark-pdf', 'text' => 'MOA Documents', 'url' => 'upload_moa_document.php'],
                ['icon' => 'bi-people-fill', 'text' => 'Interview Batches', 'url' => 'view_interview_batch.php'],
                ['icon' => 'bi-bell', 'text' => 'Notifications', 'url' => 'notifications.php']
            ]
        ],
        'Intern' => [
            'title' => 'MediCare Intern',
            'subtitle' => 'Internship Portal',
            'items' => [
                ['icon' => 'bi-speedometer2', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
                ['icon' => 'bi-file-earmark-plus', 'text' => 'Apply Internship', 'url' => 'apply_internship.php'],
                ['icon' => 'bi-calendar-check', 'text' => 'View Interview', 'url' => 'view_interview.php'],
                ['icon' => 'bi-calendar-week', 'text' => 'Work Schedule', 'url' => 'work_schedule.php'],
                ['icon' => 'bi-list-task', 'text' => 'My Tasks', 'url' => 'tasks.php'],
                ['icon' => 'bi-star', 'text' => 'My Evaluation', 'url' => 'my_evaluation.php'],
                ['icon' => 'bi-box-seam', 'text' => 'Product Inventory', 'url' => 'product_inventory.php'],
                ['icon' => 'bi-file-earmark-bar-graph', 'text' => 'Inventory Report', 'url' => 'inventory_report.php'],
                ['icon' => 'bi-file-text', 'text' => 'Policies & Guidelines', 'url' => 'policies_guidelines.php']
            ]
        ],
        'Pharmacist' => [
            'title' => 'MediCare Pharmacist',
            'subtitle' => 'Pharmacy Management',
            'items' => [
                ['icon' => 'bi-speedometer2', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
                ['icon' => 'bi-prescription2', 'text' => 'Prescriptions', 'url' => 'prescriptions.php'],
                ['icon' => 'bi-box-seam', 'text' => 'Manage Inventory', 'url' => 'manage_inventory.php'],
                ['icon' => 'bi-truck', 'text' => 'Dispense Products', 'url' => 'dispense_product.php'],
                ['icon' => 'bi-file-earmark-plus', 'text' => 'Purchase Orders', 'url' => 'generate_purchase_order.php'],
                ['icon' => 'bi-list-check', 'text' => 'Manage Requisitions', 'url' => 'manage_requisitions.php'],
                ['icon' => 'bi-clipboard-data', 'text' => 'Product Logs', 'url' => 'product_logs.php'],
                ['icon' => 'bi-receipt', 'text' => 'Process Orders', 'url' => 'process_order.php']
            ]
        ],
        'Technician' => [
            'title' => 'MediCare Technician',
            'subtitle' => 'Technical Operations',
            'items' => [
                ['icon' => 'bi-speedometer2', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
                ['icon' => 'bi-file-earmark-plus', 'text' => 'Create Purchase Order', 'url' => 'create_po.php'],
                ['icon' => 'bi-file-earmark-text', 'text' => 'Create Requisition', 'url' => 'create_requisition.php'],
                ['icon' => 'bi-list-ul', 'text' => 'My Requisitions', 'url' => 'my_requisitions.php'],
                ['icon' => 'bi-file-earmark-bar-graph', 'text' => 'Review Reports', 'url' => 'review_reports.php']
            ]
        ],
        'Assistant' => [
            'title' => 'MediCare Assistant',
            'subtitle' => 'Customer Service',
            'items' => [
                ['icon' => 'bi-speedometer2', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
                ['icon' => 'bi-telephone', 'text' => 'Call Center', 'url' => 'call_center.php'],
                ['icon' => 'bi-headset', 'text' => 'Customer Service', 'url' => 'customer_service.php'],
                ['icon' => 'bi-box-seam', 'text' => 'Inventory', 'url' => 'inventory.php'],
                ['icon' => 'bi-truck', 'text' => 'Dispense Product', 'url' => 'dispense_product.php'],
                ['icon' => 'bi-receipt', 'text' => 'Order Processing', 'url' => 'order_processing.php'],
                ['icon' => 'bi-clipboard-data', 'text' => 'Product Logs', 'url' => 'product_logs.php']
            ]
        ],
        'Customer' => [
            'title' => 'MediCare Customer',
            'subtitle' => 'Patient Portal',
            'items' => [
                ['icon' => 'bi-speedometer2', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
                ['icon' => 'bi-prescription2', 'text' => 'My Prescriptions', 'url' => 'my_prescriptions.php'],
                ['icon' => 'bi-file-earmark-plus', 'text' => 'Submit Prescription', 'url' => 'prescription_submit.php'],
                ['icon' => 'bi-truck', 'text' => 'Track Dispensing', 'url' => 'track_dispensing.php'],
                ['icon' => 'bi-receipt', 'text' => 'Purchase Orders', 'url' => 'purchase_order_view.php'],
                ['icon' => 'bi-credit-card', 'text' => 'Payment', 'url' => 'payment.php']
            ]
        ]
    ];
    
    return $configs[$role] ?? $configs['Customer'];
}

function renderSidebar($currentRole, $currentPage = '') {
    $config = getSidebarConfig($currentRole);
    $currentFile = basename($_SERVER['PHP_SELF']);
    
    echo '<div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-hospital"></i> ' . $config['title'] . '</h4>
            <small>' . $config['subtitle'] . '</small>
        </div>
        
        <nav class="sidebar-menu">';
    
    foreach ($config['items'] as $item) {
        $isActive = (basename($item['url']) === $currentFile) ? 'active' : '';
        echo '<a href="' . $item['url'] . '" class="menu-item ' . $isActive . '">
                <i class="bi ' . $item['icon'] . '"></i> ' . $item['text'] . '
              </a>';
    }
    
    echo '</nav>
    </div>';
}

function getSidebarCSS() {
    return '
    <style>
        body {
            background-color: #f8f9fa;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .sidebar-header h4 {
            margin: 0;
            font-weight: 600;
        }
        
        .sidebar-menu {
            padding: 0;
        }
        
        .sidebar-menu .menu-item {
            display: block;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu .menu-item:hover {
            background-color: #f8f9fa;
            color: #667eea;
            padding-left: 30px;
        }
        
        .sidebar-menu .menu-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-left: 4px solid #4c63d2;
        }
        
        .sidebar-menu .menu-item i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .top-navbar {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .btn-logout {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>';
}

function getSidebarJS() {
    return '
    <script>
        // Mobile sidebar toggle
        document.getElementById("sidebarToggle")?.addEventListener("click", function() {
            document.querySelector(".sidebar").classList.toggle("show");
        });
    </script>';
}
?>