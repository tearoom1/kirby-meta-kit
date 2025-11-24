# Meta Kit Tests

This directory contains functional PHPUnit tests for the Meta Kit plugin.

## Installation

First, install the test dependencies:

```bash
cd site/plugins/meta-kit
composer install
```

## Running Tests

Run all tests:

```bash
composer test
```

Or run PHPUnit directly:

```bash
vendor/bin/phpunit
```

Run specific test:

```bash
vendor/bin/phpunit --filter testApplySingleFieldMetaTitle
```

Run with readable output:

```bash
vendor/bin/phpunit --testdox
```

Run with verbose output:

```bash
vendor/bin/phpunit --verbose
```

## Test Status

✅ **All 13 tests passing** with 61 assertions

Successfully tested on PHP 8.5.0 with Kirby CMS.

## Test Coverage

The test suite covers:

- **applySingleField**: Tests updating individual SEO fields (metaTitle, metaDescription, robots)
- **getSinglePage**: Tests retrieving page SEO data
- **getPagesWithContent**: Tests retrieving multiple pages with SEO data
- **Content Updates**: Verifies that updates persist correctly
- **Data Integrity**: Tests that multiple field updates maintain all data
- **Site vs Page**: Tests both page and site-level SEO fields
- **Legacy Fields**: Tests detection of legacy meta fields
- **Error Handling**: Tests error responses for non-existent pages

## What's NOT Tested

The AI generation methods (`generateField`, `generateDescription`) are not fully tested because they require:
- A real OpenRouter API key
- HTTP client mocking

These methods are tested for structure and error handling only. The update logic is thoroughly tested through `applySingleField`.

## Test Structure

- **setUp()**: Creates a temporary Kirby instance with test content
- **tearDown()**: Cleans up temporary files and resets Kirby instance
- **Helper methods**: Create test pages, sites, and content files

## Continuous Integration

These tests can be integrated into CI/CD pipelines:

```yaml
# Example GitHub Actions workflow
- name: Install dependencies
  run: composer install

- name: Run tests
  run: composer test
```

## Writing New Tests

When adding new controller methods, follow this pattern:

1. Test success case
2. Test error cases (page not found, invalid input, etc.)
3. Test data persistence
4. Test data structure of returned values
5. Test edge cases (empty values, special characters, etc.)

Example:

```php
public function testNewMethod()
{
    // Setup
    $result = MetaKitController::newMethod('test-page', 'param');

    // Assert
    $this->assertEquals('success', $result['status']);
    $this->assertArrayHasKey('data', $result);

    // Verify persistence
    $page = $this->kirby->page('test-page');
    $this->assertEquals('expected', $page->field()->value());
}
```
