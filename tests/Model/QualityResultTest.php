<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Model\QualityResult;

/**
 * @internal
 */
#[CoversClass(QualityResult::class)]
final class QualityResultTest extends TestCase
{
    public function testConstructorWithDefaultValues(): void
    {
        $result = new QualityResult(passed: true);

        $this->assertTrue($result->isPassed());
        $this->assertEquals([], $result->getDetails());
        $this->assertEquals('', $result->getMessage());
    }

    public function testConstructorWithAllParameters(): void
    {
        $details = [
            'temperature' => 25.5,
            'humidity' => 60,
            'defects' => ['scratch', 'dent'],
        ];
        $message = 'Quality check completed with minor issues';

        $result = new QualityResult(
            passed: false,
            details: $details,
            message: $message
        );

        $this->assertFalse($result->isPassed());
        $this->assertEquals($details, $result->getDetails());
        $this->assertEquals($message, $result->getMessage());
    }

    public function testIsPassedReturnsBooleanValue(): void
    {
        $passedResult = new QualityResult(passed: true);
        $failedResult = new QualityResult(passed: false);

        $this->assertIsBool($passedResult->isPassed());
        $this->assertIsBool($failedResult->isPassed());
        $this->assertTrue($passedResult->isPassed());
        $this->assertFalse($failedResult->isPassed());
    }

    public function testGetDetailsReturnsArray(): void
    {
        $details = ['weight' => 100, 'color' => 'red', 'size' => 'large'];
        $result = new QualityResult(passed: true, details: $details);

        $this->assertIsArray($result->getDetails());
        $this->assertEquals($details, $result->getDetails());
    }

    public function testGetDetailsWithEmptyArray(): void
    {
        $result = new QualityResult(passed: true, details: []);

        $this->assertIsArray($result->getDetails());
        $this->assertEquals([], $result->getDetails());
        $this->assertEmpty($result->getDetails());
    }

    public function testGetDetailsWithNestedArrays(): void
    {
        $details = [
            'measurements' => [
                'length' => 10.5,
                'width' => 5.2,
                'height' => 3.1,
            ],
            'defects' => [
                'surface' => ['scratch', 'stain'],
                'structural' => ['crack'],
            ],
        ];
        $result = new QualityResult(passed: false, details: $details);

        $retrievedDetails = $result->getDetails();
        $this->assertEquals($details, $retrievedDetails);
        $this->assertIsArray($retrievedDetails);
        $this->assertArrayHasKey('measurements', $retrievedDetails);
        $this->assertArrayHasKey('defects', $retrievedDetails);
        $this->assertIsArray($retrievedDetails['measurements']);
        $this->assertArrayHasKey('length', $retrievedDetails['measurements']);
        $this->assertEquals(10.5, $retrievedDetails['measurements']['length']);
    }

    public function testGetMessageReturnsString(): void
    {
        $message = 'All quality checks passed successfully';
        $result = new QualityResult(passed: true, message: $message);

        $this->assertIsString($result->getMessage());
        $this->assertEquals($message, $result->getMessage());
    }

    public function testGetMessageWithEmptyString(): void
    {
        $result = new QualityResult(passed: true, message: '');

        $this->assertIsString($result->getMessage());
        $this->assertEquals('', $result->getMessage());
        $this->assertEmpty($result->getMessage());
    }

    public function testImmutabilityOfResult(): void
    {
        $originalDetails = ['test' => 'value'];
        $result = new QualityResult(passed: true, details: $originalDetails);

        // 尝试修改返回的数组不应影响原始对象
        $retrievedDetails = $result->getDetails();
        $retrievedDetails['new_key'] = 'new_value';

        $this->assertNotEquals($retrievedDetails, $result->getDetails());
        $this->assertEquals($originalDetails, $result->getDetails());
    }

    public function testQualityResultForPassedInspection(): void
    {
        $result = new QualityResult(
            passed: true,
            details: [
                'inspector' => 'John Doe',
                'timestamp' => '2024-01-01 10:00:00',
                'standards_met' => true,
            ],
            message: 'Product meets all quality standards'
        );

        $this->assertTrue($result->isPassed());
        $this->assertEquals('Product meets all quality standards', $result->getMessage());
        $this->assertEquals('John Doe', $result->getDetails()['inspector']);
    }

    public function testQualityResultForFailedInspection(): void
    {
        $result = new QualityResult(
            passed: false,
            details: [
                'inspector' => 'Jane Smith',
                'timestamp' => '2024-01-01 14:30:00',
                'failed_criteria' => ['weight', 'color_consistency'],
                'retry_required' => true,
            ],
            message: 'Product failed quality inspection - retry required'
        );

        $this->assertFalse($result->isPassed());
        $this->assertEquals('Product failed quality inspection - retry required', $result->getMessage());
        $this->assertEquals(['weight', 'color_consistency'], $result->getDetails()['failed_criteria']);
        $this->assertTrue($result->getDetails()['retry_required']);
    }
}
