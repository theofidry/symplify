<?php

declare(strict_types=1);

namespace Symplify\EasyCodingStandard\Console\Command;

use Nette\Utils\Json;
use PHP_CodeSniffer\Sniffs\Sniff;
use PhpCsFixer\Fixer\FixerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symplify\EasyCodingStandard\Console\Output\ConsoleOutputFormatter;
use Symplify\EasyCodingStandard\Console\Reporter\CheckerListReporter;
use Symplify\EasyCodingStandard\FixerRunner\Application\FixerFileProcessor;
use Symplify\EasyCodingStandard\Guard\LoadedCheckersGuard;
use Symplify\EasyCodingStandard\SniffRunner\Application\SniffFileProcessor;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\PackageBuilder\Console\Command\AbstractSymplifyCommand;

final class ListCheckersCommand extends AbstractSymplifyCommand
{
    public function __construct(
        private SniffFileProcessor $sniffFileProcessor,
        private FixerFileProcessor $fixerFileProcessor,
        private CheckerListReporter $checkerListReporter,
        private LoadedCheckersGuard $loadedCheckersGuard
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('list-checkers');
        $this->setDescription('Shows loaded checkers');

        $this->addOption(
            Option::OUTPUT_FORMAT,
            null,
            InputOption::VALUE_REQUIRED,
            'Select output format',
            ConsoleOutputFormatter::NAME
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! $this->loadedCheckersGuard->areSomeCheckersRegistered()) {
            return self::SUCCESS;
        }

        $outputFormat = $input->getOption(Option::OUTPUT_FORMAT);

        if ($outputFormat === 'json') {
            $data = [
                'sniffs' => $this->getSniffClasses(),
                'fixers' => $this->getFixerClasses(),
            ];

            echo Json::encode($data, Json::PRETTY) . PHP_EOL;

            return Command::SUCCESS;
        }

        $this->checkerListReporter->report($this->getSniffClasses(), 'PHP_CodeSniffer');
        $this->checkerListReporter->report($this->getFixerClasses(), 'PHP-CS-Fixer');

        return self::SUCCESS;
    }

    /**
     * @return array<class-string<FixerInterface>>
     */
    private function getFixerClasses(): array
    {
        $fixers = $this->fixerFileProcessor->getCheckers();
        return $this->getObjectClasses($fixers);
    }

    /**
     * @return array<class-string<Sniff>>
     */
    private function getSniffClasses(): array
    {
        $sniffs = $this->sniffFileProcessor->getCheckers();
        return $this->getObjectClasses($sniffs);
    }

    /**
     * @template TObject as Sniff|FixerInterface
     * @param TObject[] $checkers
     * @return array<class-string<TObject>>
     */
    private function getObjectClasses(array $checkers): array
    {
        $objectClasses = array_map(static fn (object $fixer): string => $fixer::class, $checkers);
        sort($objectClasses);

        return $objectClasses;
    }
}
