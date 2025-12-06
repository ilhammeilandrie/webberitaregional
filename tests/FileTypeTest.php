<?php

use PHPUnit\Framework\TestCase;

class FileTypeTest extends TestCase
{
    /**
     * Helper: ambil API key dari config.local.php atau ENV.
     */
    private function loadApiKey(): ?string
    {
        // Coba dari config.local.php
        $configFile = __DIR__ . '/../config.local.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            if (is_array($config) && !empty($config['NEWS_API_KEY'])) {
                return $config['NEWS_API_KEY'];
            }
        }

        // Coba dari environment (GitHub Secret)
        $envKey = getenv('NEWS_API_KEY');
        if (!empty($envKey)) {
            return $envKey;
        }

        return null;
    }

    /**
     * 1. Pastikan index.php ada
     */
    public function testIndexPhpExists()
    {
        $this->assertFileExists(__DIR__ . '/../index.php');
    }

    /**
     * 2. Pastikan index.php tidak kosong
     */
    public function testIndexPhpNotEmpty()
    {
        $content = file_get_contents(__DIR__ . '/../index.php');
        $this->assertNotEmpty($content, "index.php tidak boleh kosong.");
    }

    /**
     * 3. Pastikan API key dikonfigurasi (di config.local.php atau ENV)
     */
    public function testApiKeyIsConfigured()
    {
        $apiKey = $this->loadApiKey();
        $this->assertNotEmpty(
            $apiKey,
            "API key belum dikonfigurasi. Tambahkan ke config.local.php atau ENV NEWS_API_KEY."
        );
    }

    /**
     * 4. API harus mengembalikan JSON valid
     */
    public function testApiReturnsValidJson()
    {
        $apiKey = $this->loadApiKey();
        if (!$apiKey) {
            $this->markTestSkipped('API key belum dikonfigurasi, lewati test API.');
        }

        $url = "https://newsdata.io/api/1/news?apikey={$apiKey}&country=id&q=Jakarta";

        $response = @file_get_contents($url);
        $this->assertNotFalse($response, "Gagal memanggil API NewsData.io.");

        $json = json_decode($response, true);
        $this->assertIsArray($json, "Response API bukan JSON.");
    }

    /**
     * 5. Pastikan field 'results' ada dalam response API
     */
    public function testApiHasResultsField()
    {
        $apiKey = $this->loadApiKey();
        if (!$apiKey) {
            $this->markTestSkipped('API key belum dikonfigurasi, lewati test API.');
        }

        $url = "https://newsdata.io/api/1/news?apikey={$apiKey}&country=id&q=Surabaya";

        $response = @file_get_contents($url);
        $this->assertNotFalse($response, "Gagal memanggil API NewsData.io.");

        $json = json_decode($response, true);
        $this->assertArrayHasKey('results', $json, "Field 'results' tidak ditemukan di response.");
        $this->assertIsArray($json['results']);
    }
}
