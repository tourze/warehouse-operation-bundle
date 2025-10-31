<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service;

/**
 * 工作流编排服务接口
 *
 * 负责仓库作业工作流的编排和协调，整合任务调度、质检控制、
 * 盘点管理和路径优化等各个业务模块，提供统一的工作流管理。
 */
interface WorkflowOrchestrationServiceInterface
{
    /**
     * 启动工作流程
     *
     * @param string $workflowType 工作流类型
     * @param array<string, mixed> $parameters 启动参数
     * @return array<string, mixed> 启动结果
     */
    public function startWorkflow(string $workflowType, array $parameters): array;

    /**
     * 监控工作流执行状态
     *
     * @param string $workflowId 工作流ID
     * @return array<string, mixed> 执行状态
     */
    public function getWorkflowStatus(string $workflowId): array;

    /**
     * 处理工作流异常
     *
     * @param string $workflowId 工作流ID
     * @param array<string, mixed> $exceptionData 异常数据
     * @return array<string, mixed> 处理结果
     */
    public function handleWorkflowException(string $workflowId, array $exceptionData): array;
}
