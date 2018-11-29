<?php

namespace Practice\ConsoleUtils\Console\Command;

use Magento\Backend\App\Area\FrontNameResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Phamda\Phamda as P;
use League\Csv\Reader;
use League\Csv\Statement;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\State;

/*
* Usage: bin/magento practice:product:inventory-loader <path to csv>
*/

class InventoryLoader extends Command
{
    const ARG_PATH_TO_CSV = 'csv';

    private $state;
    private $stockRegistry;

    public function __construct(
        State $state,
        StockRegistryInterface $stockRegistry
    ) {
        parent::__construct();

        $this->state = $state;
        $this->stockRegistry = $stockRegistry;
    }

    protected function configure()
    {
        $this->setName('practice:product:inventory-loader')
            ->setDescription("Load product inventory.")
            ->setDefinition([
                new InputArgument(
                    self::ARG_PATH_TO_CSV,
                    InputArgument::REQUIRED,
                    'Absolute path to CSV file.'
                )
            ]);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(FrontNameResolver::AREA_CODE);

        $reader = Reader::createFromPath($input->getArgument(self::ARG_PATH_TO_CSV));

        $reader->setHeaderOffset(0);
        $records = (new Statement)->process($reader);
        $headers = $records->getHeader();
        $csvAttributes = array_slice($headers, 1);

        if (!in_array('sku', $headers)) {
            $output->writeln("<error>SKU column not found</error>");

            return;
        }

        if (!in_array('quantity', $headers)) {
            $output->writeln("<error>Quantity column not found</error>");

            return;
        }

        $output->writeln('<info>Starting inventory loader script...</info>');

        $errors = [];
        $totalProductsProcessed = 0;
        $success_count = 0;
        foreach ($records->getRecords() as $record) {
            $totalProductsProcessed++;

            $sku = $record[$headers[0]];
            unset($record[$headers[0]]);

            if ($output->isVerbose()) {
                $output->write(sprintf("<info>%s - Processing product $sku...</info>", $totalProductsProcessed));
            }

            try {
                $stockItem = $this->stockRegistry->getStockItemBySku($sku);
                $stockItem->setQty($record['quantity']);
                $this->stockRegistry->updateStockItemBySku($sku, $stockItem);

                if ($output->isVerbose()) {
                    $output->writeln(' Done.');
                }

                $success_count++;
            } catch (\Exception $e) {
                if ($output->isVerbose()) {
                    $output->writeln("<error>{$e->getMessage()}</error>");
                }

                $errors[] = $sku . ': ' . $e->getMessage();
            }
        }

        $output->writeln('<info>'. $success_count .' products loaded.</info>');
        $output->writeln('<error>'. count($errors) .' errors.</error>');
    }
}
