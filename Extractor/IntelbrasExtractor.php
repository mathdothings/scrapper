<?php

namespace App\Processing;

use RuntimeException;
use Exception;

class IntelbrasExtractor
{
    private array $dataset = [];

    public function export(string $filepath, string $filename): bool
    {
        $data = $this->process($filepath);
        $products = $this->extract($data);

        return $this->exportToCSV($products, $filename);
    }

    public function process(string $filepath): array
    {
        if (!file_exists($filepath)) {
            throw new RuntimeException("File not found: {$filepath}");
        }

        require_once $filepath;
        $data = $links;

        if (!is_array($data)) {
            throw new RuntimeException("Invalid data format in file: {$filepath}");
        }

        foreach ($data as $key => $value) {
            $this->dataset[$key] = $this->normalize($value, $key);
        }

        return $data;
    }

    private function normalize(array $data): array
    {
        $data['nome_produto'] = $data['nome_produto'] ?? null;
        $data['imagem_produto'] = $data['imagem_produto'] ?? null;
        $data['info_produto'] = $data['info_produto'] ?? null;
        $data['info_extra_produto'] = $data['info_extra_produto'] ?? null;

        return $data;
    }

    public function saveToJson(string $outputFile): void
    {
        file_put_contents(
            $outputFile,
            json_encode($this->dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    public function extract(array $products): array
    {
        $extractedData = [];

        foreach ($products as $productCode => $product) {
            $extractedData[] = [
                'codigo_de_barras' => $productCode . ';',
                'nome_produto' => str_replace(PHP_EOL, ' ', $product['nome_produto']) . ';' ?? null,
                'imagem' => $product['imagem_produto'] . ';' ?? null,
                'peso_kg' => $this->extractAndConvertWeight($product) ?? null,
                'peso_unidade' => $this->extractAndConvertWeightUnit($product) ?? null,
                'dimensoes' => $this->extractFormattedDimensions($product) ?? null,
                'dimensoes_unidade' => $this->extractFormattedDimensionsUnit($product) ?? null
            ];
        }

        return $extractedData;
    }

    private function extractFormattedDimensions(array $product): ?string
    {
        $dimensions = $product['info_produto'] ?? [];

        if (empty($dimensions)) {
            return ';';
        }
        $result = [];

        foreach ($dimensions as $key => $value) {
            if (stripos(trim($key), 'peso') !== false) {
                continue;
            }

            if (preg_match_all('/(\d+[\.,]?\d*)/', (string)$value, $matches)) {
                $result = array_merge($result, str_replace(',', '.', $matches[0]));
            }
        }

        rsort($result);

        return implode(';', $result) . (empty($result) ? '' : ';');
    }

    private function extractFormattedDimensionsUnit(array $product): ?string
    {
        $dimensions = $product['info_produto'] ?? [];

        if (empty($dimensions)) {
            return null;
        }

        $units = [];
        $unitPattern = '/(?:cm|mm|m|in|ft|"|\')/i'; // Common unit patterns

        foreach ($dimensions as $key => $value) {
            if (stripos(trim($key), 'peso') !== false) {
                continue;
            }

            if (preg_match($unitPattern, (string)$value, $matches)) {
                $units[] = strtolower($matches[0]);
            }
        }

        if (empty($units)) {
            return null;
        }

        $uniqueUnits = array_unique($units);
        sort($uniqueUnits);

        return implode(';', $uniqueUnits) . ';';
    }

    private function extractAndConvertWeight(array $product): ?string
    {
        $weight = $product['info_produto']['Peso do produto'] ?? null;

        if (empty($weight)) {
            return ';';
        }

        if (preg_match('/(\d+[\.,]\d+)|(\d+)/', $weight, $matches)) {
            $number = $matches[0];
            $number = str_replace(',', '.', $number);
            return (float)$number . ';';
        }

        return ';';
    }

    private function extractAndConvertWeightUnit(array $product): ?string
    {
        $weightString = $product['info_produto']['Peso do produto'] ?? null;
        if (empty($weightString)) {
            return ';';
        }

        preg_match('/(\d+\.?\,?\d*)\s*([a-zA-Z]+)/', $weightString, $matches);

        if (count($matches) < 3) {
            return ';';
        }

        $unit = strtolower(trim($matches[2]));

        return $unit . ';';
    }

    /**
     * Exports product data to a CSV file
     * 
     * @param array $products Array of products in your format
     * @param string $filename Output file path
     * @return bool True on success, false on failure
     */
    public function exportToCSV(array $products, string $filename): bool
    {
        try {
            $file = fopen($filename, 'w');

            if ($file === false) {
                throw new RuntimeException("Failed to open file: {$filename}");
            }

            fwrite($file, implode(';', [
                'codigo_de_barras',
                'nome_produto',
                'imagem',
                'comprimento',
                'largura',
                'altura',
                'dimensoes_unidade',
                'peso',
                'peso_unidade'
            ]) . PHP_EOL);

            foreach ($products as $product) {
                $line = implode('', [
                    $product['codigo_de_barras'] ?? '',
                    $product['nome_produto'] ?? '',
                    $product['imagem'] ?? '',
                    $product['dimensoes'] ?? '',
                    $product['dimensoes_unidade'] ?? '',
                    $product['peso_kg'] ?? '',
                    $product['peso_unidade'] ?? '',
                ]) . PHP_EOL;

                fwrite($file, $line);
            }

            fclose($file);
            return true;
        } catch (Exception $e) {
            error_log("Export failed: " . $e->getMessage());

            if (isset($file) && is_resource($file)) {
                fclose($file);
            }

            return false;
        }
    }
}
