<?php

namespace FoPH\Builder\Commands;

use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class Build extends Command implements LoggerAwareInterface
{
    /**
     * @var string
     */
    protected static $defaultName = 'build';

    /**
     * @var LoggerInterface
     */
    private $logger;

    protected function configure()
    {
        $this->setDescription('This command builds file list based on the configured sources.')
            ->addArgument('path', InputArgument::OPTIONAL, 'path to duck duck go checkout');
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);

        $logger->debug('get path');
        $path = $input->getArgument('path');
        if (!is_dir($path)) {
            throw new InvalidArgumentException('path must be a local directory');
        }
        $path = rtrim($path,'/\\');

        $logger->debug('init buffer');
        $buffer = fopen('./output.txt', 'w+');

        $logger->debug('parse files');
        foreach ($this->getFiles($path, $logger) as $domainJson) {
            if(
                array_key_exists('subdomains',$domainJson) &&
                !empty($domainJson['subdomains'])
            ) {
                if(is_array($domainJson['subdomains'])) {
                    $logger->info('add blacklist entries for ' . count($domainJson['subdomains']) . ' subdomains',['domains' => $domainJson['subdomains']]);
                    foreach ($domainJson['subdomains'] as $subdomain) {
                        fputs($buffer,$subdomain . '.' . $domainJson['domain'] . PHP_EOL);
                    }
                } else {
                    $logger->error('malformed subdomain list.',['domainJson' => $domainJson]);
                }
            } else {
                $logger->info('no subdomains found, adding general blacklist');
                fputs($buffer,$domainJson['domain'] . PHP_EOL);
            }
        }
        fclose($buffer);

        return 0;
    }

    public function getFiles($path, LoggerInterface $logger)
    {
        $path = $path . '/domains';
        if(!is_dir($path)) {
            throw new InvalidArgumentException('could not find domain folder');
        }

        foreach (scandir($path) as $file) {
            if('json' === substr($file,-4)) {
                $logger->debug('get content for ' . $file, ['filepath' => $path . '/' . $file,]);
                $content = json_decode(file_get_contents($path . '/' . $file), true);
                if(
                    is_array($content) &&
                    array_key_exists('domain', $content)
                ) {
                    yield ($content);
                } else {
                    $logger->error('malformed domain json in file: ' . $file, ['filepath' => $path . '/' . $file, 'content' => $content]);
                }
            } else {
                $logger->debug('skip non json file: ' . $file);
            }
        };
    }
}