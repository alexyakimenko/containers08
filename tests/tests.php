<?php

require_once __DIR__ . '/testframework.php';

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../modules/database.php';
require_once __DIR__ . '/../modules/page.php';

$testFramework = new TestFramework();

function testDbConnection() {
    global $config;

    try {
        $db = new Database($config["db"]["path"]);
        return assertExpression(true,
            "Database connection established successfully",
            "Failed to connect to database");
    } catch (Exception $e) {
        return assertExpression(false,
            "Unexpected exception",
            "Exception: " . $e->getMessage());
    }
}

function testDbCount() {
    global $config;

    try {
        $db = new Database($config["db"]["path"]);
        $count = $db->Count("page");

        return assertExpression($count >= 1,
            "Count method returned {$count} pages",
            "Count method failed or returned unexpected value: {$count}");
    } catch (Exception $e) {
        return assertExpression(false,
            "Unexpected exception",
            "Exception: " . $e->getMessage());
    }
}

function testDbCreate() {
    global $config;

    try {
        $db = new Database($config["db"]["path"]);

        $testData = [
            'title' => 'Test Page',
            'content' => 'This is a test page created by unit test',
        ];

        $id = $db->Create("page", $testData);

        return assertExpression($id > 0,
            "Create method succeeded with ID: {$id}",
            "Create method failed to return valid ID");
    } catch (Exception $e) {
        return assertExpression(false,
            "Unexpected exception",
            "Exception: " . $e->getMessage());
    }
}

function testDbRead() {
    global $config;

    try {
        $db = new Database($config["db"]["path"]);

        $testData = [
            'title' => 'Test Read Page',
            'content' => 'This page is for testing the Read method',
        ];

        $id = $db->Create("page", $testData);

        $readData = $db->Read("page", $id);

        $readSuccess = isset($readData['id']) &&
            $readData['title'] == $testData['title'] &&
            $readData['content'] == $testData['content'];

        return assertExpression($readSuccess,
            "Read method succeeded in retrieving created page",
            "Read method failed to retrieve correct data");
    } catch (Exception $e) {
        return assertExpression(false,
            "Unexpected exception",
            "Exception: " . $e->getMessage());
    }
}

function testDbUpdate() {
    global $config;

    try {
        $db = new Database($config["db"]["path"]);

        $testData = [
            'title' => 'Original Title',
            'content' => 'Original content',
        ];

        $id = $db->Create("page", $testData);

        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated content'
        ];

        $updateSuccess = $db->Update("page", $id, $updateData);

        $readData = $db->Read("page", $id);

        $verifySuccess = $updateSuccess &&
            isset($readData['id']) &&
            $readData['title'] == $updateData['title'] &&
            $readData['content'] == $updateData['content'];

        return assertExpression($verifySuccess,
            "Update method succeeded",
            "Update method failed or data wasn't updated correctly");
    } catch (Exception $e) {
        return assertExpression(false,
            "Unexpected exception",
            "Exception: " . $e->getMessage());
    }
}

function testDbDelete() {
    global $config;

    try {
        $db = new Database($config["db"]["path"]);

        $testData = [
            'title' => 'Page to Delete',
            'content' => 'This page will be deleted'
        ];

        $id = $db->Create("page", $testData);

        $deleteSuccess = $db->Delete("page", $id);

        $readData = $db->Read("page", $id);

        $verifySuccess = $deleteSuccess && $readData === null;

        return assertExpression($verifySuccess,
            "Delete method succeeded",
            "Delete method failed or data wasn't deleted correctly");
    } catch (Exception $e) {
        return assertExpression(false,
            "Unexpected exception",
            "Exception: " . $e->getMessage());
    }
}

function testDbExecute() {
    global $config;

    try {
        $db = new Database($config["db"]["path"]);

        $success = $db->Execute("
            CREATE TABLE IF NOT EXISTS test_table (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT
            )
        ");

        if ($success) {
            $insertSuccess = $db->Execute("INSERT INTO test_table (name) VALUES ('test')");
            $count = $db->Count("test_table");
            $success = $insertSuccess && $count > 0;
        }

        $db->Execute("DROP TABLE IF EXISTS test_table");

        return assertExpression($success,
            "Execute method succeeded",
            "Execute method failed");
    } catch (Exception $e) {
        return assertExpression(false,
            "Unexpected exception",
            "Exception: " . $e->getMessage());
    }
}

function testDbFetch() {
    global $config;

    try {
        $db = new Database($config["db"]["path"]);

        $db->Create("page", [
            'title' => 'Fetch Test Page 1',
            'content' => 'Content'
        ]);

        $db->Create("page", [
            'title' => 'Fetch Test Page 2',
            'content' => 'Content'
        ]);

        $results = $db->Fetch("SELECT * FROM page WHERE content = 'Content'");

        $success = is_array($results) && count($results) >= 2;

        return assertExpression($success,
            "Fetch method returned " . count($results) . " results",
            "Fetch method failed or returned unexpected results");
    } catch (Exception $e) {
        return assertExpression(false,
            "Unexpected exception",
            "Exception: " . $e->getMessage());
    }
}

function testPageConstructor() {
    try {
        $templatePath = __DIR__ . '/../templates/index.tpl';
        $page = new Page($templatePath);

        $success = file_exists($templatePath);

        return assertExpression($success,
            "Page constructor succeeded with valid template path",
            "Page constructor failed with valid template path");
    } catch (Exception $e) {
        return assertExpression(false,
            "Unexpected exception",
            "Exception: " . $e->getMessage());
    }
}

function testPageConstructorInvalidPath() {
    try {
        $templatePath = __DIR__ . '/non_existent_template.tpl';
        $page = new Page($templatePath);

        return assertExpression(false,
            "Unexpected success",
            "Page constructor did not validate template path");
    } catch (Exception $e) {
        // This is expected
        return assertExpression(true,
            "Page constructor correctly detected invalid template path",
            "Unexpected error: " . $e->getMessage());
    }
}

function testPageRender() {
    try {
        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir . '/test_template.tpl';
        file_put_contents($tempFile, 'Hello {{name}}! Your age is {{age}}.');

        $page = new Page($tempFile);

        $data = [
            'name' => 'John',
            'age' => 30
        ];

        $rendered = $page->Render($data);
        $expected = 'Hello John! Your age is 30.';

        $success = $rendered === $expected;

        unlink($tempFile);

        return assertExpression($success,
            "Render method correctly replaced placeholders",
            "Render method failed to replace placeholders properly");
    } catch (Exception $e) {
        return assertExpression(false,
            "Unexpected exception",
            "Exception: " . $e->getMessage());
    }
}

function testPageRenderEmptyData() {
    try {
        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir . '/test_template.tpl';
        $templateContent = 'Static content with {{placeholder}}';
        file_put_contents($tempFile, $templateContent);

        $page = new Page($tempFile);

        $rendered = $page->Render([]);

        $success = $rendered === $templateContent;

        unlink($tempFile);

        return assertExpression($success,
            "Render method correctly handled empty data",
            "Render method failed with empty data");
    } catch (Exception $e) {
        return assertExpression(false,
            "Unexpected exception",
            "Exception: " . $e->getMessage());
    }
}

function testPageRenderEscaping() {
    try {
        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir . '/test_template.tpl';
        file_put_contents($tempFile, 'Content: {{content}}');

        $page = new Page($tempFile);

        $data = [
            'content' => '<script>alert("XSS")</script>'
        ];

        $rendered = $page->Render($data);
        $expected = 'Content: &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;';

        $success = $rendered === $expected;

        unlink($tempFile);

        return assertExpression($success,
            "Render method correctly escaped HTML special characters",
            "Render method failed to escape HTML special characters");
    } catch (Exception $e) {
        return assertExpression(false,
            "Unexpected exception",
            "Exception: " . $e->getMessage());
    }
}

$testFramework->add('Database connection', 'testDbConnection');
$testFramework->add('Database count', 'testDbCount');
$testFramework->add('Database create', 'testDbCreate');
$testFramework->add('Database read', 'testDbRead');
$testFramework->add('Database update', 'testDbUpdate');
$testFramework->add('Database delete', 'testDbDelete');
$testFramework->add('Database execute', 'testDbExecute');
$testFramework->add('Database fetch', 'testDbFetch');
$testFramework->add('Page constructor', 'testPageConstructor');
$testFramework->add('Page constructor invalid path', 'testPageConstructorInvalidPath');
$testFramework->add('Page render', 'testPageRender');
$testFramework->add('Page render empty data', 'testPageRenderEmptyData');
$testFramework->add('Page render escaping', 'testPageRenderEscaping');

// Run tests
$testFramework->run();

echo "Tests passed: " . $testFramework->getResult() . "\n";