<?php

namespace Aftab\LaravelCrud\Services;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use JsonException;

class CrudParser
{
    /**
     * @return array<string, mixed>
     */
    public function parseInput(string $input, ?string $format = null): array
    {
        $format = $format ? strtolower($format) : $this->detectFormatFromString($input);
        return $this->parseByFormat($input, $format);
    }

    /**
     * @return array<string, mixed>
     */
    public function parseFile(string $path): array
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException("Config file not found: {$path}");
        }
        $contents = file_get_contents($path);
        $format = $this->detectFormatFromPath($path) ?? $this->detectFormatFromString((string) $contents);
        return $this->parseByFormat((string) $contents, $format, $path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseByFormat(string $contents, string $format, ?string $path = null): array
    {
        if ($format === 'json') {
            try {
                return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $context = $path ? " in {$path}" : '';
                throw new \RuntimeException("JSON parse error{$context}: " . $e->getMessage());
            }
        }

        if (in_array($format, ['yaml', 'yml'], true)) {
            try {
                $parsed = Yaml::parse($contents);
            } catch (ParseException $e) {
                $context = $path ? " in {$path}" : '';
                $line = $e->getParsedLine();
                $prefix = $line ? " at line {$line}" : '';
                throw new \RuntimeException("YAML parse error{$context}{$prefix}: " . $e->getMessage());
            }
            return is_array($parsed) ? $parsed : [];
        }

        throw new \InvalidArgumentException("Unsupported format: {$format}");
    }

    protected function detectFormatFromPath(string $path): ?string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ['json', 'yaml', 'yml'], true) ? $ext : null;
    }

    protected function detectFormatFromString(string $contents): string
    {
        $trim = ltrim($contents);
        if ($trim === '') {
            return 'json';
        }
        if (str_starts_with($trim, '{') || str_starts_with($trim, '[')) {
            return 'json';
        }
        return 'yaml';
    }
}


