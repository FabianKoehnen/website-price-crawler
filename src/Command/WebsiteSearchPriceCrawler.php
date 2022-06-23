#!/usr/local/bin/php
<?php

declare(strict_types=1);


namespace App\Command;


use App\Reader\CsvReader;
use App\Writer\CsvWriter;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

#[AsCommand(name: "Website Search Price Crawler")]
class WebsiteSearchPriceCrawler extends Command
{
    public const ARGUMENT_URL_PATTERN = 'url-pattern';

    public const ARGUMENT_PRICE_XPATH = 'price-xpath';
    public const ARGUMENT_SKU_XPATH = 'sku-xpath';

    public const ARGUMENT_INPUT_FILE = 'input-file';
    public const ARGUMENT_OUTPUT_FILE = 'output-file';

    public const OPTION_SLEEP_SECONDS = 'sleep-seconds';
    public const OPTION_SKU_COLUMN_NAME = 'sku-column-name';

    public const VERSION = '1.1.0';

    protected function configure()
    {
        $this->addArgument(self::ARGUMENT_URL_PATTERN, InputArgument::REQUIRED, 'pattern for the search page, use "%s" for the location of the sku ')

            ->addArgument(self::ARGUMENT_PRICE_XPATH, InputArgument::REQUIRED, 'xpath to the html element including the price')
            ->addArgument(self::ARGUMENT_SKU_XPATH, InputArgument::REQUIRED, 'xpath to the html element including the sku, for validation purposes')

            ->addArgument(self::ARGUMENT_INPUT_FILE, InputArgument::REQUIRED, 'path to the csv with skus')
            ->addArgument(self::ARGUMENT_OUTPUT_FILE, InputArgument::REQUIRED, 'path to the csv with skus')


            ->addOption(self::OPTION_SLEEP_SECONDS, 's', InputOption::VALUE_OPTIONAL, 'sleep seconds between requests', '1')
            ->addOption(self::OPTION_SKU_COLUMN_NAME, null, InputOption::VALUE_OPTIONAL, 'name of the column of the skus', 'sku')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getArgument(self::ARGUMENT_SKU_XPATH)) {
            $output->writeln('<info>Validating SKU xpath not active</info>');
        }

        $skus = $this->readSkus(
                $input->getArgument(self::ARGUMENT_INPUT_FILE),
                $input->getOption(self::OPTION_SKU_COLUMN_NAME)
        );

        $products = [];
        foreach ($skus as $sku) {
            $uri = sprintf($input->getArgument(self::ARGUMENT_URL_PATTERN), $sku);
            $pageHtml = $this->getPageHtml($uri);

            $skuFromPage = $this->getHtmlElementByXpath($pageHtml, $input->getArgument(self::ARGUMENT_SKU_XPATH));
            if ($sku !== $skuFromPage) {
                $output->writeln(sprintf('<info>Sku "%s" does not match "%s"</info>', $sku, $skuFromPage));
                $products[] = [
                    'sku' => $sku,
                    'price' => '-',
                ];
                continue;
            }
            $output->writeln(sprintf('<info>Sku "%s" matches "%s"</info>', $sku, $skuFromPage));

            $price = $this->getHtmlElementByXpath($pageHtml, $input->getArgument(self::ARGUMENT_PRICE_XPATH));

            $output->writeln(sprintf('<info>Price: %s</info>', $price));

            $products[] = [
                'sku' => $sku,
                'price' => $price
            ];

            /** @noinspection DisconnectedForeachInstructionInspection */
            usleep($input->getOption(self::OPTION_SLEEP_SECONDS) * 1000000);
        }

        $this->writeProducts($products, $input->getArgument(self::ARGUMENT_OUTPUT_FILE));

        return Command::SUCCESS;
    }

    protected function readSkus(string $filepath, string $skuFieldKey): array
    {
        $csvReader = new CsvReader($filepath);

        $skus = [];
        foreach ($csvReader as $data) {
            $skus[] = $data[$skuFieldKey];
        }

        return $skus;
    }

    protected function getPageHtml(string $uri):string
    {
        $client = new Client();
        $request = new Request('GET',$uri);

        return $client->send($request)->getBody()->getContents();
    }

    protected function getHtmlElementByXpath(string $html, string $xpath): ?string
    {
        $crawler = new Crawler($html);

        try {
            $element = $crawler->filterXPath($xpath)->text();
        } catch (Exception $e) {
            return null;
        }

        return $element;
    }

    protected function writeProducts(array $products, string $filepath): void
    {
        $writer = new CsvWriter($filepath);
        $writer->write($products);
    }
}
