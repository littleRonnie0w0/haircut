<?php

namespace App\Services;

use App\Models\AiGeneration;
use App\Models\Servicio;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AiPromptService
{
    public function forGeneration(AiGeneration $generation): string
    {
        if ($generation->style_label && $generation->style_category) {
            return $this->forStyle($generation->style_label, $generation->style_category);
        }

        return $this->forPreset($generation->preset);
    }

    public function forService(Servicio $servicio): string
    {
        $servicio->loadMissing('categoria');

        return $this->forStyle($servicio->nombre, (string) $servicio->categoria?->slug);
    }

    public function forStyle(string $name, string $category): string
    {
        if (! in_array($category, ['corte', 'color'], true)) {
            throw new InvalidArgumentException('El servicio no está disponible para simulación.');
        }

        $serviceName = Str::lower(Str::ascii(trim($name)));
        $definitions = (array) config("ai.service_prompts.{$category}");
        $requestedChange = $definitions[$serviceName] ?? $this->fallbackFor($name, $category);

        $isHaircut = $category === 'corte'
            && ! in_array($serviceName, ['lavado', 'lavado y secado', 'lavado y brushing', 'peinado'], true);

        return $this->build($requestedChange, $isHaircut);
    }

    public function forPreset(string $preset): string
    {
        $definition = config("ai.presets.{$preset}");
        if (! is_array($definition) || empty($definition['prompt'])) {
            throw new InvalidArgumentException('Preset de simulación inválido.');
        }

        return $this->build($definition['prompt'], ($definition['category'] ?? '') === 'Corte');
    }

    private function build(string $requestedChange, bool $isHaircut): string
    {
        return implode("\n", [
            'Create one photorealistic salon consultation preview from the supplied portrait.',
            'Edit only the person\'s hair. Preserve identity, facial features, skin tone, expression, age, body, clothing, pose, background, framing, camera angle, and lighting.',
            'Do not beautify, reshape the face, alter makeup, add accessories, add text, or change anything outside the hair.',
            'Use the supplied portrait as the source of truth for the existing hair, not a generic hairstyle or an idealized head of hair.',
            'Preserve the original hair density, strand thickness, natural texture and curl pattern, hairline, receding temples, thinning areas, bald patches, and visible scalp. Never fill sparse or bald areas, lower the hairline, add extensions, or invent new hair.',
            $isHaircut
                ? 'A haircut may shorten or remove existing hair, but must never lengthen it or increase its density or coverage. Adapt the requested cut to the hair actually available; if it requires longer or fuller hair, show only the feasible change instead.'
                : 'Preserve the exact existing hair length, haircut silhouette, density and scalp coverage. Color services change only the color of existing strands; washing or blow-drying may tidy existing strands but must not change their natural type or invent volume or length.',
            'Keep realistic hair texture, roots, strand detail, shadows, and plausible salon results.',
            'Requested salon service: '.$requestedChange,
            'The preservation constraints above take priority over the requested style. On very short or sparse hair, use a subtle feasible result; if there is no visible hair to edit, leave the portrait unchanged. Before returning, verify that no hair was added, lengthened, thickened, or used to conceal visible scalp.',
            'Return only the edited portrait as an image.',
        ]);
    }

    private function fallbackFor(string $name, string $category): string
    {
        $safeName = preg_replace('/[^\pL\pN +\-]/u', '', $name) ?: 'servicio de peluquería';

        return $category === 'corte'
            ? "Create a realistic professional salon haircut corresponding to the catalog service '{$safeName}', preserving the current hair color."
            : "Create a realistic professional salon hair color corresponding to the catalog service '{$safeName}', preserving the current haircut.";
    }
}
