<?php

namespace EmzCli\Command\LDE;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use GitWrapper\GitWrapper;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class CreateLDECommand extends Command
{
    protected static $defaultName = 'lde:create';

    protected function configure()
    {
        $this->setDescription('Creates local development environment')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('projectname', 'p', InputOption::VALUE_REQUIRED)
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Creating ' . $input->getOption('projectname'));

        $gitWrapper = new GitWrapper();
        $gitWrapper->cloneRepository(
            'git://github.com/8mylez/local-development-environment.git',
            $input->getOption('projectname')
        );

        $currentPath = getcwd();
        $projectPath = $currentPath . '/' . $input->getOption('projectname');
        $dockerProjectPath = $projectPath . '/' . 'docker';

        $directoryFinder = new Finder();
        $directoryFinder->directories()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->in($projectPath)
            ->name('.git');

        $filesystem = new Filesystem();

        if ($directoryFinder->hasResults()) {
            try {
                $filesystem->remove($directoryFinder);
            } catch (IOExceptionInterface $exception) {
                $output->writeln('<error>' . $exception->getMessage() . '</error>');
            }

            $output->writeln('<info>Unnecessary directories deleted!</info>');
        }

        $renameFilesFinder = new Finder();
        $renameFilesFinder->files()
            ->in($dockerProjectPath)
            ->name('docker-compose.yml')
            ->name('bash-shop.sh')
            ->name('init.sh');

        $cleanProjectName = str_replace('.', '_', $input->getOption('projectname'));
        $shopName = 'shop_' . $cleanProjectName;
        $databaseName = 'db_' . $cleanProjectName;

        if ($renameFilesFinder->hasResults()) {
            foreach($renameFilesFinder as $renameFile) {
                $fileContent = $renameFile->getContents();

                $newFileContent = preg_replace([
                    'shopName' => '/shop_8mylez-demo-shop/',
                    'databaseName' => '/8mylezdemoshop/'
                ], [
                    'shopName' => $shopName,
                    'databaseName' => $databaseName
                ], $fileContent);

                $filesystem->dumpFile($renameFile->getRealPath(), $newFileContent);
            }

            $output->writeln('<info>Replaced files!</info>');
        }

        $output->writeln('<info>Created ' . $input->getOption('projectname') . '</info>');

        return 0;
    }
}