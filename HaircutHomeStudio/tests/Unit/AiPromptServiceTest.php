<?php

namespace Tests\Unit;

use App\Models\AiGeneration;
use App\Services\AiPromptService;
use Tests\TestCase;

class AiPromptServiceTest extends TestCase
{
    public function test_all_catalog_prompts_preserve_existing_hair_and_scalp(): void
    {
        $prompts = app(AiPromptService::class);
        foreach (config('ai.service_prompts') as $category => $services) {
            foreach (array_keys($services) as $name) {
                $prompt = $prompts->forStyle($name, $category);
                $this->assertPreservationRules($prompt);
                if ($category === 'color' || in_array($name, ['lavado y secado', 'lavado y brushing', 'peinado'], true)) {
                    $this->assertStringContainsString('Preserve the exact existing hair length', $prompt);
                    $this->assertStringNotContainsString('A haircut may shorten', $prompt);
                } else {
                    $this->assertStringContainsString('A haircut may shorten or remove existing hair', $prompt);
                    $this->assertStringContainsString('must never lengthen it', $prompt);
                }
            }
        }
    }

    public function test_presets_and_custom_services_cannot_override_preservation_rules(): void
    {
        $prompts = app(AiPromptService::class);
        foreach (config('ai.presets') as $preset => $definition) {
            $prompt = $prompts->forPreset($preset);
            $this->assertPreservationRules($prompt);
            $this->assertStringContainsString(
                $definition['category'] === 'Corte' ? 'A haircut may shorten' : 'Preserve the exact existing hair length',
                $prompt
            );
        }
        $this->assertPreservationRules($prompts->forStyle('Color personalizado', 'color'));
        $this->assertPreservationRules($prompts->forStyle('Corte personalizado', 'corte'));
    }

    public function test_generation_uses_the_same_constraints_as_the_selected_service(): void
    {
        $generation = new AiGeneration(['style_label' => 'Colores fantasía', 'style_category' => 'color']);
        $prompts = app(AiPromptService::class);
        $this->assertSame($prompts->forStyle('Colores fantasía', 'color'), $prompts->forGeneration($generation));
        $this->assertPreservationRules($prompts->forGeneration($generation));
    }

    private function assertPreservationRules(string $prompt): void
    {
        foreach (['hair density', 'natural texture and curl pattern', 'hairline', 'bald patches', 'visible scalp', 'Never fill sparse or bald areas', 'invent new hair', 'take priority', 'leave the portrait unchanged'] as $rule) {
            $this->assertStringContainsString($rule, $prompt);
        }
    }
}
