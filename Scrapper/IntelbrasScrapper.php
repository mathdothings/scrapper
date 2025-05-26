<?php

namespace App\Scrapping;

require_once __DIR__ . '/../Request/Request.php';

use DOMXPath;
use Dom\HTMLDocument;
use App\Request\Request;

class IntelbrasScrapper
{
    /**
     * Amazon Brazil base search URL (search results page)
     * 
     * @var string
     */
    private string $BASE_SEARCH_URL = 'https://loja.intelbras.com.br';

    /**
     * Amazon Brazil base URL for all requests
     * 
     * @var string
     */
    private string $BASE_URL = 'https://loja.intelbras.com.br';

    /**
     * Maximum number of retry attempts when searching for a product
     * 
     * @var int
     */
    private const MAX_ATTEMPTS = 15;

    /**
     * Delay in seconds between retry attempts when searching for a product
     * 
     * @var int
     */
    private const RETRY_DELAY = 2;

    /**
     * CSS selector used to find product links in search results
     * 
     * The selector targets:
     * - Standard product anchor tags
     * - With specific Amazon styling classes
     * - That display product titles in search results
     * 
     * @var string
     */
    private string $ANCHOR_SELECTOR = 'vtex-product-summary-2-x-clearLink h-100 flex flex-column';
    private string $PRODUCT_TITLE = 'vtex-store-components-3-x-productBrand vtex-store-components-3-x-productBrand--detail-name';
    private string $TECHINICAL_DETAILS = 'vtex-store-components-3-x-specificationsTable';
    private string $TECHNICAL_EXTRA_DETAILS = '';
    private string $TECHNICAL_FEATURED_DETAILS = '';
    private string $PRODUCT_IMAGE = 'vtex-store-components-3-x-productImageTag vtex-store-components-3-x-productImageTag--main';
    private string $PRODUCT_IMAGE_FALLBACK = '';

    /**
     * Controls whether the application should output step-by-step process information
     * 
     * When enabled (true), the application will echo detailed progress messages during execution.
     * This is useful for debugging and monitoring long-running processes.
     * When disabled (false), the application will run silently without progress output.
     * 
     * @var bool
     * @example
     * // With ECHO_STEPS = true
     * > Processing item 1/100...
     * > Downloading data...
     * > Complete!
     */
    private const ECHO_STEPS = true;

    /**
     * Generates a randomized Amazon Brazil search URL for a given barcode.
     *
     * Creates a search URL with randomized parameters to help avoid detection as automated traffic.
     * The URL includes:
     * - The provided barcode as search term
     * - Randomized CRID (customer reference ID)
     * - Properly encoded search prefix
     * - Amazon Brazil specific parameters
     *
     * @param string $barcode The product barcode to search for (e.g., '7899298674719')
     * 
     * @return string The complete Amazon search URL with randomized parameters
     * 
     * // Returns: 'http://www.amazon.com.br/s?k=7899298674719&__mk_pt_BR=ÃMÃŽÕÑ&crid=1MG7FU2LX89J0&...'
     *
     * @see http_build_query() Used to create the query string
     * @see urlencode() Used for proper parameter encoding
     * @see str_shuffle() Used to randomize the CRID parameter
     */
    private function randomSearchURLByBarcode(string $barcode): string
    {
        // example: https://loja.intelbras.com.br/7899298680475?_q=7899298680475&map=ft
        return $this->BASE_SEARCH_URL
            . '/'
            . $barcode
            . '?_q='
            . $barcode
            . '&map=ft';
    }

    private function classHandler(string $classlist): string
    {
        return '.' . implode('.', explode(' ', $classlist));
    }

    /**
     * Finds a product URL on Amazon by its barcode with retry logic.
     *
     * This method attempts to locate a product page URL by:
     * 1. Generating a randomized search URL for the barcode
     * 2. Making multiple attempts (with configurable retries and delays)
     * 3. Parsing the search results page for product links
     * 4. Validating and returning the first valid product URL found
     *
     * @param string $barcode The product barcode to search for
     * 
     * @return string The full product URL if found, empty string otherwise
     * 
     * @throws \RuntimeException If the HTTP request fails after all retry attempts
     * 
     * @see randomSearchURLByBarcode() Used to generate the initial search URL
     * @see Request::attempt() Performs the HTTP request
     * @see HTMLDocument::createFromString() Parses the HTML response
     * 
     * @uses MAX_RETRIES Class constant for maximum retry attempts
     * @uses RETRY_DELAY Class constant for delay between retries (in seconds)
     * @uses BASE_URL Class constant for Amazon's base URL
     *
     * @example
     * $url = $finder->findProductURLByBarcode('7899298674719');
     * // Returns: 'https://www.amazon.com.br/dp/B08L5WR9W5' or empty string
     */
    public function findProductURLByBarcode(string $barcode): string
    {
        $target = $this->randomSearchURLByBarcode($barcode);
        $url = '';

        for ($i = 1; $i <= self::MAX_ATTEMPTS; $i++) {
            $this->echoHead($barcode, $i);

            $response = new Request()->attempt($target);

            if (!is_string($response)) {
                $this->echoResponseFail();
                sleep(self::RETRY_DELAY);
                continue;
            }

            $dom = HTMLDocument::createFromString($response);
            $anchor = $dom->querySelector($this->classHandler($this->ANCHOR_SELECTOR));

            if (!$anchor) {
                $this->echoNotFound($barcode);
                break;
            }

            $href = $anchor->getAttribute('href');

            if (!isset($href)) {
                $this->echoNotFound($barcode);
                break;
            }

            if (strpos($href, 'sspa/click?ie')) {
                $this->echoNotFound($barcode);
                break;
            }

            if (self::ECHO_STEPS) {
                echo sprintf('<a target="_blank" style="color: mediumspringgreen;" href="%s%s">%s%s</a>', $this->BASE_URL, $href, $this->BASE_URL, $href);
                echo '<br />';
            }

            $url = $this->BASE_URL . $href;
            return $url;
        }

        return $url;
    }

    public function findProductContentByBarcodeAndURL(string $barcode, string $url): array
    {
        $arr = [];
        for ($i = 1; $i <= self::MAX_ATTEMPTS; $i++) {
            $this->echoHead($barcode, $i);

            // $url = $this->findProductURLByBarcode($barcode);
            $response = new Request()->attempt($url);

            if (!is_string($response)) {
                $this->echoResponseFail();
                sleep(self::RETRY_DELAY);
                continue;
            }

            $dom = HTMLDocument::createFromString($response);

            $productTitle = $dom->querySelector($this->classHandler($this->PRODUCT_TITLE));
            if ($productTitle) {
                $arr['nome_produto'] = trim($productTitle->textContent);
            }

            $techSpecTds = $this->stripeTableContent($dom, $this->classHandler($this->TECHINICAL_DETAILS));

            if (!$techSpecTds) {
                continue;
            }

            if ($techSpecTds) {
                $arr['info_produto'] = $techSpecTds;
            }

            $img = $dom->querySelector($this->classHandler($this->PRODUCT_IMAGE)) ?? $dom->querySelector($this->classHandler($this->PRODUCT_IMAGE_FALLBACK));
            if ($img) {
                $src = $img->getAttribute('src');
                $arr['imagem_produto'] = $src;
            }

            if (empty($arr)) {
                $this->echoNotFound($barcode);
                break;
            }

            $this->echoFound($barcode);

            return $arr;
        }

        return $arr;
    }

    private function stripeTableContent(HTMLDocument $dom): array
    {
        $content = [];
        $cells = $dom->getElementsByTagName('td');
        $currentKey = null;

        foreach ($cells as $cell) {
            $text = trim($this->cleanSpecialChars($cell->textContent));

            if (empty($text)) {
                continue;
            }

            if (strpos($text, '>')) {
                continue;
            }

            if ($currentKey === null) {
                $currentKey = $text;
            } else {
                $content[$currentKey] = $text;
                $currentKey = null;
            }
        }

        $filteredContent = [
            'Comprimento do produto' => $content['Comprimento do produto'] ?? null,
            'Largura do produto' => $content['Largura do produto'] ?? null,
            'Altura do produto' => $content['Altura do produto'] ?? null,
            'Peso do produto' => $content['Peso do produto'] ?? null
        ];

        return array_filter($filteredContent);
    }

    private function cleanSpecialChars(string $text): string
    {
        $text = preg_replace('/[\x00-\x1F\x7F\xA0\x{200E}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $text);
        return trim($text);
    }

    /**
     * Outputs a page access failure message when ECHO_STEPS is enabled
     * 
     * Displays a standardized error message indicating the page couldn't be accessed,
     * and that the system will retry. Includes HTML line breaks for web output.
     * 
     * @return void
     * @see ECHO_STEPS Constant that controls debug output
     */
    private function echoResponseFail(): void
    {
        if (self::ECHO_STEPS) {
            echo '> 🐶 Não foi possível acessar a página! Tentando novamente...';
            echo '<br />';
        }
    }

    /**
     * Outputs a generic operation failure message when ECHO_STEPS is enabled
     * 
     * Displays a standardized error message indicating an operation failed,
     * and that the system will retry. Includes HTML line breaks for web output.
     * 
     * @return void
     * @see ECHO_STEPS Constant that controls debug output
     */
    private function echoFail(): void
    {
        if (self::ECHO_STEPS) {
            echo '> ⚠️ Dados do produto não encontrados! Tentando novamente...';
            echo '<br />';
        }
    }

    /**
     * Outputs a product not found message when ECHO_STEPS is enabled
     * 
     * Displays a formatted message indicating the specified barcode wasn't found.
     * Includes HTML line breaks before and after for proper spacing in web output.
     * 
     * @param string $barcode The product barcode that wasn't found
     * @return void
     * @see ECHO_STEPS Constant that controls debug output
     */
    private function echoNotFound($barcode): void
    {
        if (self::ECHO_STEPS) {
            echo '<br />';
            echo "> ⛔ O produto $barcode não foi encontrado.";
            echo '<br />';
        }
    }

    /**
     * Outputs a product found message when ECHO_STEPS is enabled
     * 
     * Displays a formatted message indicating the specified barcode was found.
     * Includes HTML line breaks before and after for proper spacing in web output.
     * 
     * @param string $barcode The product barcode that was found
     * @return void
     * @see ECHO_STEPS Constant that controls debug output
     */
    private function echoFound($barcode): void
    {
        if (self::ECHO_STEPS) {
            echo "> ✅ O produto $barcode foi encontrado.";
            echo '<br />';
        }
    }

    /**
     * Outputs a process header message when ECHO_STEPS is enabled
     * 
     * Displays a formatted header showing the current barcode being processed
     * and the attempt number. Includes HTML line breaks for web output.
     * 
     * @param string $barcode The product barcode being processed
     * @param int $i The current attempt number
     * @return void
     * @see ECHO_STEPS Constant that controls debug output
     */
    private function echoHead($barcode, $i): void
    {
        if (self::ECHO_STEPS) {
            echo '<br />';
            echo '> ℹ️ Código de barras: ' . $barcode . ' - Tentativa ' . $i;
            echo '<br />';
        }
    }
}
