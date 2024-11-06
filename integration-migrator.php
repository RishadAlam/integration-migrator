<?php

/**
 * Plugin Name: Integration Migrator
 * Plugin URI:  https://bitapps.pro/bit-integrations
 * Description: For Testing 
 * Version:     1.0.0
 * Author:      Bit Apps
 * Author URI:  https://bitapps.pro
 * Text Domain: integration-migrator
 * Requires PHP: 7.0
 * Requires at least: 5.1
 * Tested up to: 6.6.2
 * License:  GPLv2 or later
 */


// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class IntegrationMigrator
{
    private $integrations;

    public function __construct()
    {
        require_once("Controller.php");
        require_once("FileController.php");

        add_action('admin_menu', [$this, 'create_admin_page']);
        add_action('wp_ajax_get_tasks', [Controller::class, 'getTasks']);
        add_action('wp_ajax_get_tasks_details', [Controller::class, 'getTasksDetails']);
        add_action('wp_ajax_create_trigger', [FileController::class, 'createTrigger']);
    }

    public function create_admin_page()
    {
        add_menu_page(
            'Integration Migrator',
            'Integration Migrator',
            'manage_options',
            'integration-migrator',
            [$this, 'admin_page_html'],
            'dashicons-migrate',
            6
        );

        $this->integrations = apply_filters('sure_trigger_integrations', []);
    }

    public function admin_page_html()
    {
?>
        <div class="wrap">
            <h1>Sure Trigger Dropdowns</h1>
            <form id="trigger-dropdowns-form">
                <label for="integration">Select Integration:</label>
                <select id="integration" name="integration">
                    <option value="">Select...</option>
                    <?php foreach ($this->integrations as $key => $integration): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($integration->get_name() ?? $key); ?></option>
                    <?php endforeach; ?>
                </select>

                <br>
                <br>

                <label for="type">Select Type:</label>
                <select id="type" name="type" style="display:none;">
                    <option value="" selected>Select...</option>
                    <option value="triggers">Triggers</option>
                    <option value="actions">Actions</option>
                </select>

                <br>
                <br>

                <label for="task">Select Task:</label>
                <select id="task" name="task" style="display:none;">
                    <option value="">Select...</option>
                </select>

                <div id="result" style="margin-top: 20px;"></div>

                <button id="create-integrations" type="button">Create Integrations</button>
            </form>
        </div>


        <script src="<?php echo plugin_dir_url(__FILE__) ?>script.js"></script>
<?php
    }
}

new IntegrationMigrator();
