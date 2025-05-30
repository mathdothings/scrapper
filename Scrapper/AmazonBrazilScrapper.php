<?php

namespace App\Scrapping;

require_once __DIR__ . '/../Request/Request.php';

use App\Request\Request;
use Dom\HTMLDocument;

class AmazonBrazilScrapper
{
    /**
     * Amazon Brazil base search URL (search results page)
     * 
     * @var string
     */
    private const BASE_SEARCH_URL = 'http://www.amazon.com.br/s';

    /**
     * Amazon Brazil base URL for all requests
     * 
     * @var string
     */
    private const BASE_URL = 'http://www.amazon.com.br';

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
    private const ANCHOR_SELECTOR = 'a.a-link-normal.s-line-clamp-4.s-link-style.a-text-normal';

    private const PRODUCT_TITLE = 'productTitle';
    private const TECHINICAL_DETAILS = 'productDetails_techSpec_section_1';
    private const TECHNICAL_EXTRA_DETAILS = 'productDetails_detailBullets_sections1';
    private const TECHNICAL_FEATURED_DETAILS = 'detailBullets_feature_div';
    private const PRODUCT_IMAGE = 'landingImage';
    private const PRODUCT_IMAGE_FALLBACK = '.a-dynamic-image.a-stretch-vertical';

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
        $params = [
            'k' => '7899298674719',
            '__mk_pt_BR' => 'ÃMÃŽÕÑ',
            'crid' => '1MG7FU2LX89J0',
            'sprefix' => '7899298674719,aps,283',
            'ref' => 'nb_sb_noss',
        ];
        $random_crid = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10);
        $params['crid'] = $random_crid;
        $params['k'] = $barcode;
        $parts = explode(',', $params['sprefix']);
        $params['sprefix'] = urlencode($barcode . ',' . $parts[1] . $parts[2]);

        $query_string = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return self::BASE_SEARCH_URL . '?' . $query_string;
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
            $anchor = $dom->querySelector(self::ANCHOR_SELECTOR);

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
                echo sprintf('<a target="_blank" style="color: mediumspringgreen;" href="%s%s">%s%s</a>', self::BASE_URL, $href, self::BASE_URL, $href);
                echo '<br />';
            }

            $url = self::BASE_URL . $href;
            return $url;
        }

        return $url;
    }

    public function findProductContentByBarcodeAndURL(string $barcode, string $url): array
    {
        $arr = [];
        for ($i = 1; $i <= self::MAX_ATTEMPTS; $i++) {
            $this->echoHead($barcode, $i);

            $response = new Request()->attempt($url);

            if (!is_string($response)) {
                $this->echoResponseFail();
                sleep(self::RETRY_DELAY);
                continue;
            }

            $dom = HTMLDocument::createFromString($response);

            $productTitle = $dom->getElementById(self::PRODUCT_TITLE);
            if ($productTitle) {
                $arr['nome_produto'] = trim($productTitle->textContent);
            }

            $techSpecTds = $this->stripeTableContent($dom, self::TECHINICAL_DETAILS);
            if ($techSpecTds) {
                $arr['info_produto'] = $techSpecTds;
            }

            $techSpecExtraTds = $this->stripeTableContent($dom, self::TECHNICAL_EXTRA_DETAILS);
            if ($techSpecExtraTds) {
                $arr['info_extra_produto'] = $techSpecExtraTds;
            }

            if (empty($techSpecExtraTds)) {
                $techSpecExtraTds = $this->stripeTableContent($dom, self::TECHNICAL_FEATURED_DETAILS);
            }

            $img = $dom->getElementById(self::PRODUCT_IMAGE) ?? $dom->querySelector(self::PRODUCT_IMAGE_FALLBACK);
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

    private function stripeTableContent(HTMLDocument $dom, string $tableId): array
    {
        $table = $dom->getElementById($tableId);
        $content = [];

        if ($table) {
            $trs = $table->getElementsByTagName('tr');

            foreach ($trs as $tr) {
                $ths = $tr->getElementsByTagName('th');
                $tds = $tr->getElementsByTagName('td');

                if ($ths->length > 0 && $tds->length > 0) {
                    $th = $ths->item(0);
                    $td = $tds->item(0);

                    $key = $this->cleanSpecialChars(trim($th->textContent));
                    $value = $this->cleanSpecialChars(trim($td->textContent));

                    if (!empty($key)) {
                        $content[$key] = $value;
                    }
                }
            }
        }

        return $content;
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
