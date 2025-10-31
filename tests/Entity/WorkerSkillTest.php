<?php

namespace Tourze\WarehouseOperationBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WarehouseOperationBundle\Entity\WorkerSkill;

/**
 * WorkerSkill Entity 单元测试
 * @internal
 */
#[CoversClass(WorkerSkill::class)]
class WorkerSkillTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new WorkerSkill();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        return [
            'workerId' => ['workerId', 123],
            'workerName' => ['workerName', 'Test Worker'],
            'skillCategory' => ['skillCategory', 'equipment'],
            'skillLevel' => ['skillLevel', 5],
            'skillScore' => ['skillScore', 85],
            'certifications' => ['certifications', ['test' => 'value']],
            'notes' => ['notes', 'Test notes'],
        ];
    }

    public function testWorkerSkillCreation(): void
    {
        $skill = new WorkerSkill();

        $this->assertNull($skill->getId());
        $this->assertSame(0, $skill->getWorkerId());
        $this->assertSame('', $skill->getWorkerName());
        $this->assertSame('', $skill->getSkillCategory());
        $this->assertSame(1, $skill->getSkillLevel());
        $this->assertSame(1, $skill->getSkillScore());
        $this->assertSame([], $skill->getCertifications());
        $this->assertNull($skill->getCertifiedAt());
        $this->assertNull($skill->getExpiresAt());
        $this->assertTrue($skill->isActive());
        $this->assertNull($skill->getNotes());
    }

    public function testSettersAndGetters(): void
    {
        $skill = new WorkerSkill();
        $certifications = [
            'forklift_license' => [
                'number' => 'FL001',
                'issued_by' => '安全监督局',
                'level' => 'A级',
            ],
            'safety_training' => [
                'completion_date' => '2024-01-15',
                'score' => 95,
            ],
        ];
        $certifiedAt = new \DateTimeImmutable('2024-01-15');
        $expiresAt = new \DateTimeImmutable('2025-01-15');

        $skill->setWorkerId(1001);
        $skill->setWorkerName('张三');
        $skill->setSkillCategory('equipment');
        $skill->setSkillLevel(4);
        $skill->setSkillScore(85);
        $skill->setCertifications($certifications);
        $skill->setCertifiedAt($certifiedAt);
        $skill->setExpiresAt($expiresAt);
        $skill->setIsActive(false);
        $skill->setNotes('优秀的设备操作员');

        $this->assertSame(1001, $skill->getWorkerId());
        $this->assertSame('张三', $skill->getWorkerName());
        $this->assertSame('equipment', $skill->getSkillCategory());
        $this->assertSame(4, $skill->getSkillLevel());
        $this->assertSame(85, $skill->getSkillScore());
        $this->assertSame($certifications, $skill->getCertifications());
        $this->assertSame($certifiedAt, $skill->getCertifiedAt());
        $this->assertSame($expiresAt, $skill->getExpiresAt());
        $this->assertFalse($skill->isActive());
        $this->assertSame('优秀的设备操作员', $skill->getNotes());
    }

    public function testFluentInterface(): void
    {
        $skill = new WorkerSkill();

        $skill->setWorkerId(2002);
        $skill->setWorkerName('李四');
        $skill->setSkillCategory('picking');
        $skill->setSkillLevel(5);
        $skill->setSkillScore(95);
        $skill->setCertifications([]);
        $skill->setIsActive(true);
        $skill->setNotes('测试备注');

        // 验证setter方法正确设置了值
        $this->assertSame(2002, $skill->getWorkerId());
        $this->assertSame('李四', $skill->getWorkerName());
        $this->assertSame('picking', $skill->getSkillCategory());
        $this->assertSame(5, $skill->getSkillLevel());
        $this->assertSame(95, $skill->getSkillScore());
        $this->assertSame([], $skill->getCertifications());
        $this->assertTrue($skill->isActive());
        $this->assertSame('测试备注', $skill->getNotes());
    }

    public function testToString(): void
    {
        $skill = new WorkerSkill();
        $skill->setWorkerName('王五');
        $skill->setSkillCategory('quality');

        // ID为null时的toString
        $expected = 'WorkerSkill # (王五 - quality)';
        $this->assertSame($expected, $skill->__toString());
    }

    public function testSkillCategories(): void
    {
        $skill = new WorkerSkill();

        // 测试所有支持的技能类别
        $validCategories = ['picking', 'packing', 'quality', 'counting', 'equipment', 'hazardous', 'cold_storage'];

        foreach ($validCategories as $category) {
            $skill->setSkillCategory($category);
            $this->assertSame($category, $skill->getSkillCategory());
        }
    }

    public function testSkillLevels(): void
    {
        $skill = new WorkerSkill();

        // 测试所有支持的技能等级 (数字等级 1-5)
        $validLevels = [1, 2, 3, 4, 5];

        foreach ($validLevels as $level) {
            $skill->setSkillLevel($level);
            $this->assertSame($level, $skill->getSkillLevel());
        }
    }

    public function testCertificationStructure(): void
    {
        $skill = new WorkerSkill();

        // 测试复杂的认证信息结构
        $certifications = [
            'forklift_license' => [
                'number' => 'FL12345',
                'issued_by' => '职业安全健康署',
                'level' => 'A级',
                'issued_date' => '2024-01-01',
                'valid_until' => '2025-01-01',
                'restrictions' => [],
            ],
            'hazmat_certification' => [
                'number' => 'HAZ789',
                'categories' => ['Class 3', 'Class 8'],
                'issued_date' => '2024-02-15',
                'training_hours' => 40,
            ],
            'quality_inspector' => [
                'level' => '二级质检员',
                'specializations' => ['食品检验', '包装材料检验'],
                'certification_body' => '质量技术监督局',
            ],
        ];

        $skill->setCertifications($certifications);

        $this->assertSame($certifications, $skill->getCertifications());
        $this->assertSame('FL12345', $skill->getCertifications()['forklift_license']['number']);
        $this->assertSame(['Class 3', 'Class 8'], $skill->getCertifications()['hazmat_certification']['categories']);
        $this->assertSame(40, $skill->getCertifications()['hazmat_certification']['training_hours']);
    }

    public function testDateHandling(): void
    {
        $skill = new WorkerSkill();

        $certifiedDate = new \DateTimeImmutable('2024-03-01 09:00:00');
        $expiryDate = new \DateTimeImmutable('2025-03-01 23:59:59');

        $skill->setCertifiedAt($certifiedDate);
        $skill->setExpiresAt($expiryDate);

        $this->assertSame($certifiedDate, $skill->getCertifiedAt());
        $this->assertSame($expiryDate, $skill->getExpiresAt());

        // 测试设置为null
        $skill->setCertifiedAt(null);
        $skill->setExpiresAt(null);

        $this->assertNull($skill->getCertifiedAt());
        $this->assertNull($skill->getExpiresAt());
    }

    public function testSkillScoreRange(): void
    {
        $skill = new WorkerSkill();

        // 测试边界值
        $skill->setSkillScore(1);
        $this->assertSame(1, $skill->getSkillScore());

        $skill->setSkillScore(100);
        $this->assertSame(100, $skill->getSkillScore());

        $skill->setSkillScore(50);
        $this->assertSame(50, $skill->getSkillScore());
    }

    public function testWorkerIdPositive(): void
    {
        $skill = new WorkerSkill();

        $skill->setWorkerId(12345);
        $this->assertSame(12345, $skill->getWorkerId());

        // 边界值测试
        $skill->setWorkerId(1);
        $this->assertSame(1, $skill->getWorkerId());
    }
}
