<?php

namespace Dwoodard\A2aLaravel\Services;

use Dwoodard\A2aLaravel\Skills\SkillInterface;

class SkillRegistry
{
    protected array $skills = [];

    // Auto-discovery: scan Skills directory for SkillInterface implementations
    protected function autoDiscoverSkills(): void
    {
        $skillsPath = __DIR__.'/../Skills';
        if (! is_dir($skillsPath)) {
            return;
        }
        foreach (scandir($skillsPath) as $file) {
            if (substr($file, -4) !== '.php' || $file === 'SkillInterface.php' || $file === 'ClosureSkill.php') {
                continue;
            }
            $class = 'Dwoodard\\A2aLaravel\\Skills\\'.substr($file, 0, -4);
            if (class_exists($class)) {
                $instance = app($class);
                if ($instance instanceof SkillInterface) {
                    $this->skills[$instance->id()] = $instance;
                }
            }
        }
    }

    public function __construct(array $skills = [])
    {
        // Accept config array or class references
        foreach ($skills as $id => $skill) {
            if (is_object($skill) && $skill instanceof SkillInterface) {
                $this->skills[$id] = $skill;
            } elseif (is_string($skill) && class_exists($skill)) {
                $instance = app($skill);
                if ($instance instanceof SkillInterface) {
                    $this->skills[$instance->id()] = $instance;
                }
            } elseif (is_array($skill) && isset($skill['handler'])) {
                // Closure skill
                $this->skills[$id] = new \Dwoodard\A2aLaravel\Skills\ClosureSkill($id, $skill);
            }
        }

        $this->autoDiscoverSkills();
    }

    public function getSkillById(string $id)
    {
        return $this->skills[$id] ?? null;
    }

    public function allSkills(): array
    {
        return $this->skills;
    }

    // TODO: Add auto-discovery and registration logic
}
