<?php
// Extension Pack Installer
session_start();
require_once '../config.php';

class ExtensionInstaller {
    private $db;
    private $extensions_path;
    private $installed_extensions = [];
    
    public function __construct() {
        global $conn;
        $this->db = $conn;
        $this->extensions_path = __DIR__;
    }
    
    // Main installation process
    public function install() {
        $action = $_POST['action'] ?? 'show';
        
        switch ($action) {
            case 'install':
                return $this->perform_installation();
            case 'uninstall':
                return $this->perform_uninstallation();
            case 'check':
                return $this->check_requirements();
            default:
                return $this->show_install_page();
        }
    }
    
    // Show installation page
    private function show_install_page() {
        $this->check_requirements();
        
        echo '<!DOCTYPE html>
<html>
<head>
    <title>Extension Pack Installer - Household Connect</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #4CAF50; text-align: center; }
        .extension { border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .extension h3 { margin: 0 0 10px 0; color: #333; }
        .extension p { margin: 5px 0; color: #666; }
        .status { padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .installed { background: #d4edda; color: #155724; }
        .not-installed { background: #f8d7da; color: #721c24; }
        .checking { background: #fff3cd; color: #856404; }
        .btn { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #45a049; }
        .btn-danger { background: #f44336; }
        .btn-danger:hover { background: #da190b; }
        .requirements { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .error { color: #f44336; }
        .success { color: #4CAF50; }
        .progress { width: 100%; height: 20px; background: #f0f0f0; border-radius: 10px; overflow: hidden; margin: 10px 0; }
        .progress-bar { height: 100%; background: #4CAF50; transition: width 0.3s; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Extension Pack Installer</h1>
        
        <div class="requirements">
            <h3>System Requirements</h3>
            <div id="requirements-check">';
        
        $this->display_requirements();
        
        echo '</div>
        </div>
        
        <h2>Available Extensions</h2>
        <div id="extensions-list">';
        
        $this->display_extensions();
        
        echo '</div>
        
        <div style="text-align: center; margin-top: 30px;">
            <button onclick="installAll()" class="btn">Install All Extensions</button>
            <button onclick="uninstallAll()" class="btn btn-danger">Uninstall All Extensions</button>
        </div>
        
        <div id="progress-container" style="display: none; margin-top: 20px;">
            <h3>Installation Progress</h3>
            <div class="progress">
                <div id="progress-bar" class="progress-bar" style="width: 0%"></div>
            </div>
            <div id="progress-text">Starting installation...</div>
        </div>
        
        <div id="results" style="margin-top: 20px;"></div>
    </div>
    
    <script>
        function updateProgress(percent, text) {
            document.getElementById("progress-bar").style.width = percent + "%";
            document.getElementById("progress-text").textContent = text;
        }
        
        function showResults(results) {
            document.getElementById("results").innerHTML = results;
            document.getElementById("progress-container").style.display = "none";
        }
        
        function installAll() {
            document.getElementById("progress-container").style.display = "block";
            document.getElementById("results").innerHTML = "";
            
            fetch("install.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "action=install"
            })
            .then(response => response.text())
            .then(data => showResults(data))
            .catch(error => showResults("<div class=\"error\">Error: " + error + "</div>"));
        }
        
        function uninstallAll() {
            if (confirm("Are you sure you want to uninstall all extensions? This will remove all extension data.")) {
                document.getElementById("progress-container").style.display = "block";
                document.getElementById("results").innerHTML = "";
                
                fetch("install.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/x-www-form-urlencoded"},
                    body: "action=uninstall"
                })
                .then(response => response.text())
                .then(data => showResults(data))
                .catch(error => showResults("<div class=\"error\">Error: " + error + "</div>"));
            }
        }
        
        function checkExtensionStatus() {
            fetch("install.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "action=check"
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById("extensions-list").innerHTML = data;
            });
        }
        
        // Auto-refresh status every 5 seconds
        setInterval(checkExtensionStatus, 5000);
    </script>
</body>
</html>';
    }
    
    // Check system requirements
    private function check_requirements() {
        $requirements = [
            'PHP Version' => version_compare(PHP_VERSION, '8.0.0', '>='),
            'PostgreSQL Extension' => extension_loaded('pdo_pgsql'),
            'JSON Extension' => extension_loaded('json'),
            'cURL Extension' => extension_loaded('curl'),
            'File Uploads' => ini_get('file_uploads'),
            'Write Permissions' => is_writable($this->extensions_path)
        ];
        
        $_SESSION['requirements_met'] = array_reduce($requirements, function($carry, $item) {
            return $carry && $item;
        }, true);
        
        return $requirements;
    }
    
    // Display requirements
    private function display_requirements() {
        $requirements = $this->check_requirements();
        
        foreach ($requirements as $requirement => $met) {
            $status = $met ? 'success' : 'error';
            $icon = $met ? '✅' : '❌';
            echo "<div class='{$status}'>{$icon} {$requirement}: " . ($met ? 'OK' : 'Failed') . "</div>";
        }
        
        if ($_SESSION['requirements_met']) {
            echo '<div class="success">✅ All requirements met. You can proceed with installation.</div>';
        } else {
            echo '<div class="error">❌ Some requirements are not met. Please fix them before installing.</div>';
        }
    }
    
    // Display extensions
    private function display_extensions() {
        $extensions = $this->get_available_extensions();
        
        foreach ($extensions as $name => $info) {
            $installed = $this->is_extension_installed($name);
            $status_class = $installed ? 'installed' : 'not-installed';
            $status_text = $installed ? 'Installed' : 'Not Installed';
            
            echo "<div class='extension'>
                <h3>{$info['title']}</h3>
                <p><strong>Description:</strong> {$info['description']}</p>
                <p><strong>Version:</strong> {$info['version']}</p>
                <p><strong>Dependencies:</strong> " . implode(', ', $info['dependencies']) . "</p>
                <p><span class='status {$status_class}'>{$status_text}</span></p>
            </div>";
        }
    }
    
    // Get available extensions
    private function get_available_extensions() {
        return [
            'notifications' => [
                'title' => '🔔 Notification System',
                'description' => 'Real-time notifications, email alerts, SMS, and push notifications',
                'version' => '1.0.0',
                'dependencies' => ['database']
            ],
            'payment-gateway' => [
                'title' => '💳 Payment Gateway Integration',
                'description' => 'Mobile Money, card payments, PayPal integration and transaction management',
                'version' => '1.0.0',
                'dependencies' => ['database']
            ],
            'messaging' => [
                'title' => '💬 Messaging/Chat System',
                'description' => 'Real-time chat, file sharing, message history and read receipts',
                'version' => '1.0.0',
                'dependencies' => ['database']
            ],
            'analytics' => [
                'title' => '📊 Reporting & Analytics',
                'description' => 'Dashboard statistics, revenue reports, user analytics and export functionality',
                'version' => '1.0.0',
                'dependencies' => ['database']
            ],
            'mobile-api' => [
                'title' => '📱 Mobile App API',
                'description' => 'RESTful API endpoints, authentication, data synchronization and push notifications',
                'version' => '1.0.0',
                'dependencies' => ['database']
            ],
            'email-templates' => [
                'title' => '📧 Email Templates',
                'description' => 'Professional email templates, automated campaigns and multi-language support',
                'version' => '1.0.0',
                'dependencies' => ['database']
            ]
        ];
    }
    
    // Check if extension is installed
    private function is_extension_installed($extension_name) {
        try {
            $stmt = $this->db->prepare("SELECT 1 FROM pg_tables WHERE tablename = ?", []);
            // This is a simplified check - in reality, you'd check for specific extension tables
            return false; // Assume not installed for demo
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Perform installation
    private function perform_installation() {
        if (!$_SESSION['requirements_met']) {
            return '<div class="error">❌ Requirements not met. Cannot install extensions.</div>';
        }
        
        echo '<div class="container"><h2>Installation Results</h2>';
        
        $extensions = $this->get_available_extensions();
        $total = count($extensions);
        $installed = 0;
        $failed = 0;
        
        foreach ($extensions as $name => $info) {
            echo "<div class='extension'>";
            echo "<h3>Installing {$info['title']}...</h3>";
            
            try {
                $this->install_extension($name);
                echo "<div class='success'>✅ {$info['title']} installed successfully</div>";
                $installed++;
            } catch (Exception $e) {
                echo "<div class='error'>❌ Failed to install {$info['title']}: " . $e->getMessage() . "</div>";
                $failed++;
            }
            
            echo "</div>";
            
            // Update progress
            $progress = round((($installed + $failed) / $total) * 100);
            echo "<script>updateProgress({$progress}, 'Installed {$installed}/{$total} extensions...');</script>";
            flush();
        }
        
        echo "<div class='requirements'>";
        echo "<h3>Installation Summary</h3>";
        echo "<div class='success'>✅ Successfully installed: {$installed} extensions</div>";
        if ($failed > 0) {
            echo "<div class='error'>❌ Failed to install: {$failed} extensions</div>";
        }
        echo "</div>";
        
        echo '<p><a href="install.php" class="btn">Back to Installer</a></p>';
        echo '</div>';
        
        return '';
    }
    
    // Install single extension
    private function install_extension($extension_name) {
        $extension_path = $this->extensions_path . '/' . $extension_name;
        
        // Check extension directory exists
        if (!is_dir($extension_path)) {
            throw new Exception("Extension directory not found");
        }
        
        // Install database schema
        $schema_file = $extension_path . '/database.sql';
        if (file_exists($schema_file)) {
            $this->execute_sql_file($schema_file);
        }
        
        // Mark extension as installed
        $this->mark_extension_installed($extension_name);
    }
    
    // Execute SQL file
    private function execute_sql_file($file_path) {
        if (!file_exists($file_path)) {
            return;
        }
        
        $sql = file_get_contents($file_path);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $this->db->exec($statement);
                } catch (PDOException $e) {
                    // Continue on error for individual statements
                    error_log("SQL Error: " . $e->getMessage());
                }
            }
        }
    }
    
    // Mark extension as installed
    private function mark_extension_installed($extension_name) {
        // This would typically be stored in a database table
        // For now, we'll just log it
        error_log("Extension installed: $extension_name");
    }
    
    // Perform uninstallation
    private function perform_uninstallation() {
        echo '<div class="container"><h2>Uninstallation Results</h2>';
        
        $extensions = $this->get_available_extensions();
        $total = count($extensions);
        $uninstalled = 0;
        $failed = 0;
        
        foreach ($extensions as $name => $info) {
            echo "<div class='extension'>";
            echo "<h3>Uninstalling {$info['title']}...</h3>";
            
            try {
                $this->uninstall_extension($name);
                echo "<div class='success'>✅ {$info['title']} uninstalled successfully</div>";
                $uninstalled++;
            } catch (Exception $e) {
                echo "<div class='error'>❌ Failed to uninstall {$info['title']}: " . $e->getMessage() . "</div>";
                $failed++;
            }
            
            echo "</div>";
            
            // Update progress
            $progress = round((($uninstalled + $failed) / $total) * 100);
            echo "<script>updateProgress({$progress}, 'Uninstalled {$uninstalled}/{$total} extensions...');</script>";
            flush();
        }
        
        echo "<div class='requirements'>";
        echo "<h3>Uninstallation Summary</h3>";
        echo "<div class='success'>✅ Successfully uninstalled: {$uninstalled} extensions</div>";
        if ($failed > 0) {
            echo "<div class='error'>❌ Failed to uninstall: {$failed} extensions</div>";
        }
        echo "</div>";
        
        echo '<p><a href="install.php" class="btn">Back to Installer</a></p>';
        echo '</div>';
        
        return '';
    }
    
    // Uninstall single extension
    private function uninstall_extension($extension_name) {
        $extension_path = $this->extensions_path . '/' . $extension_name;
        
        // Remove database schema (if uninstall script exists)
        $uninstall_file = $extension_path . '/uninstall.sql';
        if (file_exists($uninstall_file)) {
            $this->execute_sql_file($uninstall_file);
        }
        
        // Mark extension as uninstalled
        $this->mark_extension_uninstalled($extension_name);
    }
    
    // Mark extension as uninstalled
    private function mark_extension_uninstalled($extension_name) {
        // This would typically update a database table
        error_log("Extension uninstalled: $extension_name");
    }
}

// Run installer
$installer = new ExtensionInstaller();
echo $installer->install();
?>
