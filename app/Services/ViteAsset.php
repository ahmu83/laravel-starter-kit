<?php

namespace App\Services;

class ViteAsset
{
    private $manifest;

    private $manifestPath;

    private $isDev = false;

    private $laravel_config = [];

    public function __construct()
    {
        $this->laravel_config = wpll_laravel_config();
        $this->manifestPath = $this->laravel_config['manifest_path'];

        // Check if we're in development mode (hot file exists)
        $hotFilePath = $this->laravel_config['hot_file_path'];
        $this->isDev = file_exists($hotFilePath);
    }

    /**
     * Render Vite assets for the given entry points
     *
     * @param  array|string  $entries  Entry point paths (e.g., 'resources/assets/pages/dashboard/styles.scss')
     * @return string HTML link/script tags
     */
    public function render($entries = [])
    {
        if (! is_array($entries)) {
            $entries = [$entries];
        }

        if ($this->isDev) {
            return $this->renderDevAssets($entries);
        }

        return $this->renderProdAssets($entries);
    }

    /**
     * Render assets in production (using manifest.json)
     *
     * @param  array  $entries  Entry point paths from manifest
     * @return string HTML link/script tags
     */
    private function renderProdAssets($entries)
    {
        $manifest = $this->getManifest();
        $html = '';

        foreach ($entries as $entry) {
            if (! isset($manifest[$entry])) {
                continue;
            }

            $entryData = $manifest[$entry];
            $file = $this->laravel_config['build_url'].'/'.$entryData['file'];
            $ext = pathinfo($entryData['file'], PATHINFO_EXTENSION);

            // Handle CSS files
            if ($ext === 'css') {
                $html .= '<link rel="stylesheet" href="'.$file.'">'.PHP_EOL;
            }

            // Handle JS files
            if ($ext === 'js') {
                $html .= '<script type="module" src="'.$file.'"></script>'.PHP_EOL;
            }

            // Load CSS imports from manifest (Vite includes these)
            if (isset($entryData['css'])) {
                foreach ($entryData['css'] as $cssFile) {
                    $cssUrl = $this->laravel_config['build_url'].'/'.$cssFile;
                    $html .= '<link rel="stylesheet" href="'.$cssUrl.'">'.PHP_EOL;
                }
            }
        }

        return $html;
    }

    /**
     * Render assets in development mode
     * Uses Vite dev server with hot module replacement
     *
     * @param  array  $entries  Entry point paths
     * @return string HTML script tags
     */
    private function renderDevAssets($entries)
    {
        $hotFile = $this->laravel_config['hot_file_path'];

        if (! file_exists($hotFile)) {
            return '';
        }

        $hotUrl = trim(file_get_contents($hotFile));
        $html = '';

        // Load Vite client first
        $html .= '<script type="module" src="'.$hotUrl.'/@vite/client"></script>'.PHP_EOL;

        // Load CSS entries first (before JS)
        foreach ($entries as $entry) {
            if (strpos($entry, '.scss') !== false || strpos($entry, '.css') !== false) {
                $html .= '<link rel="stylesheet" href="'.$hotUrl.'/'.$entry.'">'.PHP_EOL;
            }
        }

        // Then load JS entries
        foreach ($entries as $entry) {
            if (strpos($entry, '.js') !== false) {
                $html .= '<script type="module" src="'.$hotUrl.'/'.$entry.'"></script>'.PHP_EOL;
            }
        }

        return $html;
    }

    /**
     * Get the manifest file contents
     *
     * @return array Parsed manifest.json
     */
    private function getManifest()
    {
        if ($this->manifest === null) {
            if (! file_exists($this->manifestPath)) {
                throw new \Exception("Vite manifest not found at: {$this->manifestPath}");
            }

            $this->manifest = json_decode(file_get_contents($this->manifestPath), true);
        }

        return $this->manifest;
    }

    /**
     * Get a single asset URL from manifest
     *
     * @param  string  $asset  Entry point path
     * @return string Full URL to the asset
     */
    public function asset($asset)
    {
        $manifest = $this->getManifest();

        if (! isset($manifest[$asset])) {
            throw new \Exception("Asset '{$asset}' not found in manifest");
        }

        return $this->laravel_config['build_url'].'/'.$manifest[$asset]['file'];
    }
}
