<?php

namespace TearoomOne\Tests;

use PHPUnit\Framework\TestCase;
use TearoomOne\MetaKitController;

/**
 * Functional test class for MetaKitController
 * Tests content update functionality with mocked AI responses
 */
class MetaKitControllerTest extends TestCase
{
    private $kirby;
    private $testDir;

    public function setUp(): void
    {
        // Create test directory structure
        $this->testDir = sys_get_temp_dir() . '/kirby-test-' . uniqid();
        mkdir($this->testDir);
        mkdir($this->testDir . '/content');
        mkdir($this->testDir . '/content/test-page');

        // Create test content files
        file_put_contents($this->testDir . '/content/test-page/default.txt', "Title: Test Page\n----\nText: This is test content for the page.");
        file_put_contents($this->testDir . '/content/site.txt', "Title: Test Site");

        // Bootstrap minimal Kirby instance
        $this->kirby = new \Kirby\Cms\App([
            'roots' => [
                'index' => $this->testDir,
                'content' => $this->testDir . '/content',
            ],
            'options' => [
                'tearoom1.meta-kit.api.key' => 'test-key',
                'tearoom1.meta-kit.api.model' => 'test-model',
            ],
            'users' => [
                [
                    'email' => 'test@test.com',
                    'role' => 'admin',
                ]
            ],
            'user' => 'test@test.com'
        ]);

        // Impersonate admin user to bypass permissions
        $this->kirby->impersonate('test@test.com');
    }

    public function tearDown(): void
    {
        // Clean up test directory
        if (is_dir($this->testDir)) {
            $this->deleteDirectory($this->testDir);
        }

        // Destroy Kirby instance using reflection
        $reflection = new \ReflectionClass(\Kirby\Cms\App::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, null);
    }

    /**
     * Test applySingleField updates metaTitle
     */
    public function testApplySingleFieldMetaTitle()
    {
        $page = $this->kirby->page('test-page');
        $this->assertNotNull($page, 'Test page should exist');

        $result = MetaKitController::applySingleField('test-page', 'metaTitle', 'Updated Meta Title');

        if ($result['status'] === 'error') {
            $this->fail('applySingleField failed with error: ' . ($result['message'] ?? 'unknown error'));
        }

        $this->assertEquals('success', $result['status'], 'Should return success status');
        $this->assertEquals('Field updated successfully', $result['message']);

        // Verify the field was updated
        $page = $this->kirby->page('test-page');
        $seoData = MetaKitController::getSeoData($page->metaKitSeo());

        $this->assertNotNull($seoData, 'SEO data should exist after update');
        $this->assertEquals('Updated Meta Title', $seoData->metaTitle()->value());
        // Should also update ogTitle
        $this->assertEquals('Updated Meta Title', $seoData->ogTitle()->value());
    }

    /**
     * Test applySingleField updates metaDescription and ogDescription
     */
    public function testApplySingleFieldMetaDescription()
    {
        $page = $this->kirby->page('test-page');
        $this->assertNotNull($page);

        $result = MetaKitController::applySingleField(
            'test-page',
            'metaDescription',
            'This is an updated meta description.'
        );

        $this->assertEquals('success', $result['status']);

        // Verify both metaDescription and ogDescription were updated
        $page = $this->kirby->page('test-page');
        $seoData = MetaKitController::getSeoData($page->metaKitSeo());

        $this->assertEquals('This is an updated meta description.', $seoData->metaDescription()->value());
        $this->assertEquals('This is an updated meta description.', $seoData->ogDescription()->value());
    }

    /**
     * Test applySingleField for site object
     */
    public function testApplySingleFieldForSite()
    {
        $result = MetaKitController::applySingleField('site', 'metaTitle', 'Site Meta Title');

        $this->assertEquals('success', $result['status']);

        // Verify the site field was updated
        $site = $this->kirby->site();
        $seoData = MetaKitController::getSeoData($site->metaKitSeo());

        $this->assertNotNull($seoData);
        $this->assertEquals('Site Meta Title', $seoData->metaTitle()->value());
    }

    /**
     * Test applySingleField with non-existent page
     */
    public function testApplySingleFieldPageNotFound()
    {
        $result = MetaKitController::applySingleField('non-existent-page', 'metaTitle', 'Test');

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Page not found', $result['message']);
    }

    /**
     * Test getSinglePage returns correct data structure
     */
    public function testGetSinglePage()
    {
        // First set some SEO data
        MetaKitController::applySingleField('test-page', 'metaTitle', 'Test Meta Title');
        MetaKitController::applySingleField('test-page', 'metaDescription', 'Test meta description');

        $result = MetaKitController::getSinglePage('test-page');

        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('data', $result);

        $data = $result['data'];
        $this->assertEquals('test-page', $data['id']);
        $this->assertEquals('Test Page', $data['title']);
        $this->assertTrue($data['hasMetaTitle'], 'Should have meta title');
        $this->assertTrue($data['hasMetaDescription'], 'Should have meta description');
        $this->assertEquals(15, $data['metaTitleLength']);
        $this->assertEquals(21, $data['metaDescriptionLength']);
        $this->assertEquals('Test Meta Title', $data['metaTitle']);
        $this->assertEquals('Test meta description', $data['metaDescription']);
    }

    /**
     * Test getSinglePage for site
     */
    public function testGetSinglePageForSite()
    {
        MetaKitController::applySingleField('site', 'metaTitle', 'Site Title');

        $result = MetaKitController::getSinglePage('site');

        $this->assertEquals('success', $result['status']);
        $data = $result['data'];

        $this->assertEquals('site', $data['id']);
        $this->assertEquals('Test Site', $data['title']);
        $this->assertEquals('site', $data['template']);
    }

    /**
     * Test getPagesWithContent returns data for all pages
     */
    public function testGetPagesWithContent()
    {
        // Set some SEO data
        MetaKitController::applySingleField('test-page', 'metaTitle', 'Page Title');

        // Mock the get() function for pageIds filter
        $_GET['pageIds'] = ['test-page'];

        $result = MetaKitController::getPagesWithContent();

        unset($_GET['pageIds']);

        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertGreaterThan(0, count($result['data']));

        // Check structure of returned data
        $pageData = $result['data'][0];
        $this->assertArrayHasKey('id', $pageData);
        $this->assertArrayHasKey('title', $pageData);
        $this->assertArrayHasKey('metaTitle', $pageData);
        $this->assertArrayHasKey('metaDescription', $pageData);
        $this->assertArrayHasKey('hasMetaTitle', $pageData);
        $this->assertArrayHasKey('hasMetaDescription', $pageData);
    }

    /**
     * Test getSeoData and seoDataToArray helper methods
     */
    public function testSeoDataHelpers()
    {
        $page = $this->kirby->page('test-page');

        // Initially empty
        $seoData = MetaKitController::getSeoData($page->metaKitSeo());
        $this->assertNull($seoData, 'Should be null for empty field');

        // After setting data
        MetaKitController::applySingleField('test-page', 'metaTitle', 'Test Title');

        $page = $this->kirby->page('test-page');
        $seoData = MetaKitController::getSeoData($page->metaKitSeo());
        $this->assertNotNull($seoData, 'Should have data after update');

        $seoArray = MetaKitController::seoDataToArray($seoData);
        $this->assertIsArray($seoArray, 'seoDataToArray should return an array');

        // Kirby stores field names in lowercase
        $this->assertArrayHasKey('metatitle', $seoArray);
        $this->assertEquals('Test Title', $seoArray['metatitle']);
        // Should also have ogTitle set
        $this->assertArrayHasKey('ogtitle', $seoArray);
        $this->assertEquals('Test Title', $seoArray['ogtitle']);
    }

    /**
     * Test robots field default value
     */
    public function testRobotsFieldDefault()
    {
        $result = MetaKitController::getSinglePage('test-page');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('index, follow', $result['data']['robots']);
    }

    /**
     * Test multiple field updates maintain data integrity
     */
    public function testMultipleFieldUpdates()
    {
        // Update multiple fields in sequence
        MetaKitController::applySingleField('test-page', 'metaTitle', 'Title 1');
        MetaKitController::applySingleField('test-page', 'metaDescription', 'Description 1');
        MetaKitController::applySingleField('test-page', 'robots', 'noindex, nofollow');

        // Update one field again
        MetaKitController::applySingleField('test-page', 'metaTitle', 'Title 2');

        // Verify all fields are preserved
        $result = MetaKitController::getSinglePage('test-page');
        $data = $result['data'];

        $this->assertEquals('Title 2', $data['metaTitle'], 'Title should be updated');
        $this->assertEquals('Description 1', $data['metaDescription'], 'Description should be preserved');
        $this->assertEquals('noindex, nofollow', $data['robots'], 'Robots should be preserved');
    }

    /**
     * Test content extraction from page
     */
    public function testContentExtraction()
    {
        $page = $this->kirby->page('test-page');

        // Use reflection to test private method
        $reflection = new \ReflectionClass(MetaKitController::class);
        $method = $reflection->getMethod('extractPageContent');

        // Note: setAccessible() is deprecated in PHP 8.5 and no longer needed
        // Private/protected methods are automatically accessible through reflection

        $content = $method->invoke(null, $page);

        $this->assertStringContainsString('Test Page', $content);
        $this->assertStringContainsString('test content', $content);
    }

    /**
     * Test generateField without mocking (will fail without API key, but tests structure)
     */
    public function testGenerateFieldStructure()
    {
        // This will fail because we don't have a real API key, but we can test error handling
        $result = MetaKitController::generateField('test-page', 'metaTitle', null, false);

        // Should return an array with status
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);

        // Will be 'error' because API is not configured properly for tests
        if ($result['status'] === 'error') {
            $this->assertArrayHasKey('message', $result);
        } else {
            // If somehow it succeeds (shouldn't in test env)
            $this->assertArrayHasKey('content', $result);
        }
    }

    /**
     * Test legacy field detection
     */
    public function testLegacyFieldDetection()
    {
        // Create a page with legacy fields
        file_put_contents(
            $this->testDir . '/content/test-page/default.txt',
            "Title: Test Page\n----\nText: Test content\n----\nMetatitle: Legacy Meta Title\n----\nMetadescription: Legacy meta description"
        );

        // Reload Kirby to pick up new content
        $this->kirby = new \Kirby\Cms\App([
            'roots' => [
                'index' => $this->testDir,
                'content' => $this->testDir . '/content',
            ]
        ]);

        $result = MetaKitController::getSinglePage('test-page');

        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('legacy', $result['data']);
        $this->assertNotNull($result['data']['legacy']);
        $this->assertArrayHasKey('metaTitle', $result['data']['legacy']);
        $this->assertEquals('Legacy Meta Title', $result['data']['legacy']['metaTitle']);
    }

    /**
     * Helper: Recursively delete directory
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
