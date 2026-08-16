<?php

namespace App\Game\Automation\BatchCrafting\Registries;

use App\Game\Automation\BatchCrafting\Attributes\HandlesBatchCraftingMode;
use App\Game\Automation\BatchCrafting\Attributes\HandlesBatchCraftingType;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingHandler;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingOrchestrator;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use BackedEnum;
use InvalidArgumentException;
use ReflectionClass;

class BatchCraftingAttributeRegistry
{
    /** @var array<string, class-string<BatchCraftingOrchestrator>> */
    private readonly array $orchestrators;

    /** @var array<string, class-string<BatchCraftingHandler>> */
    private readonly array $handlers;

    /**
     * @param  array<int, class-string<BatchCraftingOrchestrator>>  $orchestratorCandidates  The candidate orchestrator classes to register.
     * @param  array<int, class-string<BatchCraftingHandler>>  $handlerCandidates  The candidate handler classes to register.
     */
    public function __construct(array $orchestratorCandidates, array $handlerCandidates)
    {
        $this->orchestrators = $this->buildOrchestratorMap($orchestratorCandidates);
        $this->handlers = $this->buildHandlerMap($handlerCandidates);
    }

    /**
     * Resolve the registered orchestrator class for the given Batch Crafting type.
     *
     * @param  BatchCraftingType  $type  The Batch Crafting type to resolve.
     * @return string The registered orchestrator class name.
     */
    public function orchestratorFor(BatchCraftingType $type): string
    {
        $key = $type->value;

        if (! isset($this->orchestrators[$key])) {
            throw new InvalidArgumentException('No BatchCraftingOrchestrator is registered for batch crafting type ['.$key.'].');
        }

        return $this->orchestrators[$key];
    }

    /**
     * Resolve the registered handler class for the given Batch Crafting type and mode.
     *
     * @param  BatchCraftingType  $type  The Batch Crafting type to resolve.
     * @param  BackedEnum  $mode  The craft mode to resolve.
     * @return string The registered handler class name.
     */
    public function handlerFor(BatchCraftingType $type, BackedEnum $mode): string
    {
        $key = $this->handlerKey($type, $mode);

        if (! isset($this->handlers[$key])) {
            throw new InvalidArgumentException('No BatchCraftingHandler is registered for ['.$key.'].');
        }

        return $this->handlers[$key];
    }

    /**
     * Build the batch-crafting-type-to-orchestrator-class map from the candidate orchestrator classes.
     *
     * @param  array<int, class-string<BatchCraftingOrchestrator>>  $candidates
     * @return array<string, class-string<BatchCraftingOrchestrator>>
     */
    private function buildOrchestratorMap(array $candidates): array
    {
        $map = [];

        foreach ($candidates as $class) {
            $reflection = new ReflectionClass($class);
            $attributes = $reflection->getAttributes(HandlesBatchCraftingType::class);

            if (empty($attributes)) {
                throw new InvalidArgumentException($class.' is missing a HandlesBatchCraftingType attribute.');
            }

            if (! $reflection->implementsInterface(BatchCraftingOrchestrator::class)) {
                throw new InvalidArgumentException($class.' must implement '.BatchCraftingOrchestrator::class.'.');
            }

            /** @var HandlesBatchCraftingType $metadata */
            $metadata = $attributes[0]->newInstance();
            $key = $metadata->type->value;

            if (isset($map[$key])) {
                throw new InvalidArgumentException('A BatchCraftingOrchestrator is already registered for batch crafting type ['.$key.'].');
            }

            $map[$key] = $class;
        }

        return $map;
    }

    /**
     * Build the handler-key-to-handler-class map from the candidate handler classes.
     *
     * @param  array<int, class-string<BatchCraftingHandler>>  $candidates
     * @return array<string, class-string<BatchCraftingHandler>>
     */
    private function buildHandlerMap(array $candidates): array
    {
        $map = [];

        foreach ($candidates as $class) {
            $reflection = new ReflectionClass($class);
            $attributes = $reflection->getAttributes(HandlesBatchCraftingMode::class);

            if (empty($attributes)) {
                throw new InvalidArgumentException($class.' is missing a HandlesBatchCraftingMode attribute.');
            }

            if (! $reflection->implementsInterface(BatchCraftingHandler::class)) {
                throw new InvalidArgumentException($class.' must implement '.BatchCraftingHandler::class.'.');
            }

            /** @var HandlesBatchCraftingMode $metadata */
            $metadata = $attributes[0]->newInstance();
            $key = $this->handlerKey($metadata->type, $metadata->mode);

            if (isset($map[$key])) {
                throw new InvalidArgumentException('A BatchCraftingHandler is already registered for ['.$key.'].');
            }

            $map[$key] = $class;
        }

        return $map;
    }

    /**
     * Build the composite map key identifying a handler for a batch crafting type and mode.
     *
     * @param  BatchCraftingType  $type  The Batch Crafting type component of the key.
     * @param  BackedEnum  $mode  The craft mode component of the key.
     * @return string The composite handler map key.
     */
    private function handlerKey(BatchCraftingType $type, BackedEnum $mode): string
    {
        return $type->value.':'.$mode::class.':'.$mode->value;
    }
}
