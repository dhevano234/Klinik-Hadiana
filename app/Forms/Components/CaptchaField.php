<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class CaptchaField extends Field
{
    protected string $view = 'forms.components.captcha-field';

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (CaptchaField $component, $state) {
            // Clear any existing captcha state when component loads
            session()->forget('captcha_text');
        });

        // Don't include in form data automatically
        $this->dehydrated(false);
        
        // Set default rules
        $this->rule('required');
    }

    public static function make(string $name): static
    {
        $static = parent::make($name);
        return $static;
    }

    // Add methods that might be called by Filament
    public function getId(): string
    {
        return $this->getStatePath();
    }

    public function getStatePath(bool $isAbsolute = false): string
    {
        return parent::getStatePath($isAbsolute);
    }

    public function getLabel(): ?string
    {
        return $this->evaluate($this->label) ?? (string) str($this->getName())
            ->afterLast('.')
            ->kebab()
            ->replace(['-', '_'], ' ')
            ->title();
    }

    public function isRequired(): bool
    {
        return $this->evaluate($this->isRequired);
    }
}