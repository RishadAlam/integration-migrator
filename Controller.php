<?php

class Controller
{
    public static function getTasks()
    {
        $integration_key = sanitize_text_field($_GET['integration']);
        $type = sanitize_text_field($_GET['type']);
        $allIntegrations = apply_filters('sure_trigger_integrations', []);

        if (!isset($allIntegrations[$integration_key])) {
            wp_send_json_error('Invalid integration');
            return;
        }

        $integration = $allIntegrations[$integration_key];
        $options = [];

        if ($type === 'triggers') {
            foreach ($integration->get_triggers() as $trigger) {
                $options[] = ['task' => $trigger['label'], 'hook' => $trigger['action']];
            }
        } elseif ($type === 'actions') {
            foreach ($integration->get_actions() as $action) {
                $options[] = ['task' => $action['label'], 'hook' => $action['action']];
            }
        }

        wp_send_json_success($options);
    }

    public static function getTasksDetails()
    {
        $integration_key = sanitize_text_field($_GET['integration']);
        $type = sanitize_text_field($_GET['type']);
        $task = sanitize_text_field($_GET['task']);
        $allIntegrations = apply_filters('sure_trigger_integrations', []);

        if (!isset($allIntegrations[$integration_key])) {
            wp_send_json_error('Invalid integration');
            return;
        }

        $integration = $allIntegrations[$integration_key];
        $options = [];
        error_log(print_r($integration, true));

        if ($type === 'triggers') {
            $triggers = $integration->get_triggers() ?? [];

            if (!empty($triggers) && isset($triggers[$task])) {
                $options = [
                    ['key' => 'Task', 'value' => $triggers[$task]['label'] ?? ''],
                    ['key' => 'Hook', 'value' => $triggers[$task]['action'] ?? ''],
                    ['key' => 'Priority', 'value' => $triggers[$task]['priority'] ?? ''],
                    ['key' => 'Accepted args', 'value' => $triggers[$task]['accepted_args'] ?? '']
                ];
            }
        } elseif ($type === 'actions') {
            $actions = $integration->get_actions() ?? [];

            if (!empty($actions) && isset($actions[$task])) {
                $options = [
                    ['key' => 'Task', 'value' => $actions[$task]['label'] ?? ''],
                    ['key' => 'Hook', 'value' => $actions[$task]['action'] ?? ''],
                ];
            }
        }

        wp_send_json_success($options);
    }
}
