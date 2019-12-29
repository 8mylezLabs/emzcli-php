<?php

namespace EmzCli\Command\Plugin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;
use ZipArchive;

class PreparePluginCommand extends Command
{
    protected static $defaultName = 'plugin:prepare';

    protected function configure()
    {
        $this->setDescription('Prepares the plugin for upload in the shopware backend.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesystem = new Filesystem();

        $currentPath = getcwd();
        $pluginName = basename($currentPath);

        $pathArray = explode('/', $currentPath);
        unset($pathArray[count($pathArray)-1]);
        $currentPathParent = implode('/', $pathArray);

        $tmpPluginPath = $currentPathParent . '/' . $pluginName . '_EMZTMP';

        if ($filesystem->exists($tmpPluginPath)) {
            $filesystem->remove($tmpPluginPath);
        }

        $filesystem->mirror($currentPath, $tmpPluginPath);

        $fileFinder = new Finder();
        $fileFinder->files()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->in($tmpPluginPath)
            ->name('.DS_Store')
            ->name('.php_cs.dist')
            ->name('.psh.yml.dist')
            ->name('.eslintignore')
            ->name('.sw-zip-blacklist')
            ->name('.gitignore');

        if ($fileFinder->hasResults()) {
            foreach($fileFinder as $file) {
                try {
                    $filesystem->remove($file->getRealPath());
                } catch (IOExceptionInterface $exception) {
                    $output->writeln('<error>' . $exception->getMessage() . '</error>');
                }
            }

            $output->writeln('<info>Unnecessary files deleted!</info>');
        }

        $directoryFinder = new Finder();
        $directoryFinder->directories()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->in($tmpPluginPath)
            ->name('.githooks')
            ->name('.git')
            ->name('.idea');

        if ($directoryFinder->hasResults()) {
            try {
                $filesystem->remove($directoryFinder);
            } catch (IOExceptionInterface $exception) {
                $output->writeln('<error>' . $exception->getMessage() . '</error>');
            }

            $output->writeln('<info>Unnecessary directories deleted!</info>');
        }

        $pluginArchive = new ZipArchive();

        $pluginArchive->open($currentPathParent . '/' . $pluginName . '.zip', ZIPARCHIVE::CREATE);

        $zipFinder = new Finder();
        $zipFinder->in($tmpPluginPath)
            ->files()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false);

        if ($zipFinder->hasResults()) {
            foreach($zipFinder as $zipObject) {
                if (!empty($zipObject->getRealPath() && !empty($zipObject->getFilename()))) {
                    $relativePathSlash = !empty($zipObject->getRelativePath()) ? '/' . $zipObject->getRelativePath() : '';
                    $pluginArchive->addFile($zipObject->getRealPath(), $pluginName . $relativePathSlash . '/' . $zipObject->getFilename());
                }
            }
        }

        $output->writeln('<info>Zipped plugin!</info>');

        $pluginArchive->close();

        if ($filesystem->exists($tmpPluginPath)) {
            $filesystem->remove($tmpPluginPath);
        }

        return 0;
    }
}
