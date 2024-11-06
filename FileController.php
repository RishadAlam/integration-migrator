<?php


class FileController
{
    private $folderName;
    private $folderPath;
    private $externalPath;
    private $zipFileName;
    private $zipFilePath;

    private static $integrationKey;
    private static $allIntegrations;
    private static $integrations;
    private static $integrationTasks;

    public function __construct($folderName = 'my_project', $externalPath = '/path/to/external/directory')
    {
        $this->folderName = $folderName;
        $this->folderPath = _DIR_ . '/' . $folderName;
        $this->externalPath = $externalPath;
        $this->zipFileName = $folderName . '.zip';
        $this->zipFilePath = $externalPath . '/' . $this->zipFileName;
    }

    public static function createTrigger()
    {
        static::$integrationKey = sanitize_text_field($_GET['integration']);
        $type = sanitize_text_field($_GET['type']);

        static::$allIntegrations = apply_filters('sure_trigger_integrations', []);
        static::$integrations = static::$allIntegrations[static::$integrationKey];

        if (empty(static::$integrations)) {
            wp_send_json_error('Integration not found!', 400);
        }

        $getType = 'get_' . $type;
        static::$integrationTasks = static::$integrations->$getType();

        if (empty(static::$integrationTasks)) {
            wp_send_json_error('Integration Task not found!', 400);
        }

        error_log(print_r(static::prepareTriggerRoute(), true));
        error_log(print_r(static::prepareTriggerHook(), true));
        error_log(print_r(static::prepareTriggerController(), true));
        wp_send_json_success([static::$integrations, static::$integrationTasks], 200);
    }

    private static function prepareTriggerRoute()
    {
        $fileContent = <<<'PHP'
                            <?php

                            if (!defined('ABSPATH')) {
                                exit;
                            }

                            use BitCode\FI\Core\Util\Route;
                            use BitCode\FI\Triggers\%trigger_name%\%controller%;


                            Route::post('%trigger_slug%/test', [%controllerClass%, 'getTestData']);
                            Route::post('%trigger_slug%/test/remove', [%controllerClass%, 'removeTestData']);

                            ?>
                        PHP;

        return static::replaceContentVariable($fileContent);
    }

    private static function prepareTriggerHook()
    {
        $hookContent = <<<'PHP'
                        Hooks::add('%hook%', [%controllerClass%, '%hook_execution_method%'], %priority%, %accepted_args%);
                        PHP;

        $fileContent = "<?php\n\n";
        $fileContent .= "if (!defined('ABSPATH')) {\n";
        $fileContent .= "    exit;\n";
        $fileContent .= "}\n\n";
        $fileContent .= "use BitCode\FI\Core\Util\Hooks;\n";
        $fileContent .= "use BitCode\FI\Triggers\%trigger_name%\%controller%;\n\n";


        foreach (static::$integrationTasks as $taskData) {
            $fileContent .= static::replaceHookVariable($hookContent, $taskData) . "\n";
        }

        return static::replaceContentVariable($fileContent);
    }

    private static function prepareTriggerController()
    {
        // $fileContent = "<?php\n\n";
        // $fileContent .= "namespace BitCode\FI\Triggers\%trigger_name%;\n\n";
        // $fileContent .= "use BitCode\FI\Flow\Flow;\n";
        // $fileContent .= "use BitCode\FI\Triggers\TriggerController;\n\n";
        // $fileContent .= "final class ElementorController\n";

        $fileContent = <<<'PHP'
                        <?php

                        namespace BitCode\FI\Triggers\%trigger_name%;

                        use BitCode\FI\Flow\Flow;
                        use BitCode\FI\Triggers\TriggerController;

                        final class %controller%
                        {
                            public static function info()
                            {
                                return [
                                    'name'                => '%trigger_label%',
                                    'title'               => __('%trigger_description%', 'bit-integrations'),
                                    'type'                => 'custom_form_submission',
                                    'is_active'           => $this->is_plugin_installed(),
                                    'documentation_url'   => '#',
                                    'tutorial_url'        => '#',
                                    'triggered_entity_id' => '%triggered_entity_id%', // Form submission hook act as triggered_entity_id
                                    'multi_form'          => %triggered_entity_ids%, // Form submission hook act as triggered_entity_id
                                    'fetch'               => [
                                        'action' => '%trigger_slug%/test',
                                        'method' => 'post',
                                    ],
                                    'fetch_remove' => [
                                        'action' => '%trigger_slug%/test/remove',
                                        'method' => 'post',
                                    ],
                                    'isPro' => true
                                ];
                            }

                            public function getTestData()
                            {
                                return TriggerController::getTestData('%trigger_slug%');
                            }

                            public function removeTestData($data)
                            {
                                return TriggerController::removeTestData($data, '%trigger_slug%');
                            }

                            private static function parseFlowDetails($flowDetails)
                            {
                                return \is_string($flowDetails) ? json_decode($flowDetails) : $flowDetails;
                            }

                            private static function isPrimaryKeysMatch($recordData, $flowDetails)
                            {
                                foreach ($flowDetails->primaryKey as $primaryKey) {
                                    if ($primaryKey->value != Helper::extractValueFromPath($recordData, $primaryKey->key, '%trigger_name%')) {
                                        return false;
                                    }
                                }

                                return true;
                            }

                            private static function setTestData($optionKey, $formData, $id)
                            {
                                if (get_option($optionKey) !== false) {
                                    update_option($optionKey, [
                                        'formData'   => $formData,
                                        'primaryKey' => [(object) ['key' => 'id', 'value' => $id]] // id must set manually
                                    ]);
                                }
                            }

                        PHP;

        $fileContent .= "\n";

        $HookExecuteContent = <<<'PHP'
                        public static function %hook_execution_method%(%exec_params%)
                        {
                            // formData need to be prepare manually
                            $formData = static::%trigger_listener%(%exec_params%);

                            static::setTestData('btcbi_%trigger_slug%_test', $formData, 'id'); // id must set manually
                            $flows = Flow::exists('%trigger_name%', '%hook%');

                            if (!$flows) {
                                return;
                            }

                            foreach ($flows as $flow) {
                                $flowDetails = static::parseFlowDetails($flow->flow_details);

                                if (!isset($flowDetails->primaryKey)) {
                                    continue;
                                }

                                if (static::isPrimaryKeysMatch($formData, $flowDetails)) {
                                    $data = array_column($formData, 'value', 'name');
                                    Flow::execute('%trigger_name%', $flow->triggered_entity_id, $data, [$flow]);
                                }
                            }

                            return ['type' => 'success'];
                        }
                        PHP;

        foreach (static::$integrationTasks as $taskData) {
            $fileContent .= static::replaceHookExecVariable($HookExecuteContent, $taskData) . "\n";
        }

        if (count(static::$integrationTasks) > 0) {
            $triggerEntityIds = static::getTriggerEntityIds();
            $fileContent = str_replace('%triggered_entity_ids%', '[' . implode(',', $triggerEntityIds) . ']', $fileContent);
            $fileContent = str_replace('%triggered_entity_id%', '', $fileContent);
        } else {
            $fileContent = str_replace('%triggered_entity_id%', array_pop(static::getTriggerEntityIds()), $fileContent);
            $fileContent = str_replace('%triggered_entity_ids%', '[]', $fileContent);
        }

        $fileContent .= "\n}\n";

        return static::replaceContentVariable($fileContent);
    }

    private static function getTriggerEntityIds()
    {
        return array_map(function ($task) {
            return $task['action'];
        }, static::$integrationTasks);
    }

    private static function replaceHookExecVariable($HookExecuteContent, $taskData)
    {
        $label = preg_replace('/[^a-zA-Z0-9_ -]/s', '', ucwords($taskData['label']));

        $HookExecuteContent = str_replace('%hook_execution_method%', 'handle' . str_replace(' ', '', $label), $HookExecuteContent);
        $HookExecuteContent = str_replace('%hook%', $taskData['action'], $HookExecuteContent);
        $HookExecuteContent = str_replace('%trigger_listener%', static::convertToSnakeCase($taskData['label'] . '_' . $taskData['function'][1]), $HookExecuteContent);

        // TODO Reflection class
        // $HookExecuteContent = str_replace('%exec_params%', $taskData['priority'], $HookExecuteContent);

        return $HookExecuteContent;
    }

    private static function replaceHookVariable($hookContent, $taskData)
    {
        $label = preg_replace('/[^a-zA-Z0-9_ -]/s', '', ucwords($taskData['label']));

        $hookContent = str_replace('%hook%', $taskData['action'], $hookContent);
        $hookContent = str_replace('%hook_execution_method%', 'handle' . str_replace(' ', '', $label), $hookContent);
        $hookContent = str_replace('%priority%', $taskData['priority'], $hookContent);
        $hookContent = str_replace('%accepted_args%', $taskData['accepted_args'], $hookContent);

        return $hookContent;
    }

    private static function replaceContentVariable($fileContent)
    {
        $controllerName = ucfirst(static::$integrationKey . 'Controller');
        $name = static::$integrationKey;

        $fileContent = str_replace('%trigger_label%', static::$integrations->get_name(), $fileContent);
        $fileContent = str_replace('%trigger_description%', static::$integrations->get_description(), $fileContent);
        $fileContent = str_replace('%trigger_name%', $name, $fileContent);
        $fileContent = str_replace('%controller%', $controllerName, $fileContent);
        $fileContent = str_replace('%trigger_slug%', static::convertToSnakeCase(static::$integrationKey), $fileContent);
        $fileContent = str_replace('%controllerClass%', $controllerName . '::class', $fileContent);

        return $fileContent;
    }

    private static function convertToSnakeCase($input)
    {
        $output = preg_replace('/([a-z])([A-Z])/', '$1_$2', $input);

        return strtolower(str_replace(' ', '_', $output));
    }

    // Method to create the project folder
    public function createFolder()
    {
        if (!file_exists($this->folderPath)) {
            mkdir($this->folderPath, 0777, true);
            echo "Folder created: {$this->folderPath}\n";
        } else {
            echo "Folder already exists: {$this->folderPath}\n";
        }
    }

    // Method to create multiple files with sample code content
    public function createFiles()
    {
        $filesData = [
            'controllers/HomeController.php' => "<?php\n\nclass HomeController {\n    public function index() {\n        echo 'Welcome to the home page!';\n    }\n}\n",
            'routes/web.php' => "<?php\n\n\$router->get('/', 'HomeController@index');\n",
            'models/User.php' => "<?php\n\nclass User {\n    protected \$table = 'users';\n\n    public function getAllUsers() {\n        // Query to get all users\n    }\n}\n",
        ];

        foreach ($filesData as $fileName => $content) {
            $filePath = $this->folderPath . '/' . $fileName;
            $directoryPath = dirname($filePath);
            if (!file_exists($directoryPath)) {
                mkdir($directoryPath, 0777, true);
            }
            file_put_contents($filePath, $content);
            echo "File created with sample code: {$filePath}\n";
        }
    }

    // Method to move the folder to an external path
    public function moveFolder()
    {
        $newFolderPath = $this->externalPath . '/' . $this->folderName;
        if (rename($this->folderPath, $newFolderPath)) {
            $this->folderPath = $newFolderPath;
            echo "Folder moved to {$newFolderPath}\n";
        } else {
            echo "Failed to move folder\n";
        }
    }

    // Method to zip the folder
    public function zipFolder()
    {
        $zip = new ZipArchive();
        if ($zip->open($this->zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->folderPath),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($this->folderPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
            echo "Folder zipped successfully to {$this->zipFilePath}\n";
        } else {
            echo "Failed to create zip file\n";
        }
    }

    // Method to download the zipped folder
    public function downloadZip()
    {
        if (file_exists($this->zipFilePath)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($this->zipFilePath) . '"');
            header('Content-Length: ' . filesize($this->zipFilePath));
            readfile($this->zipFilePath);
            exit;
        } else {
            echo "Zip file not found\n";
        }
    }
}

// Usage example
// $fileController = new FileController();
// $fileController->createFolder();
// $fileController->createFiles();  // Creates sample files with code for controller, routes, and model
// $fileController->moveFolder();
// $fileController->zipFolder();
// $fileController->downloadZip();
