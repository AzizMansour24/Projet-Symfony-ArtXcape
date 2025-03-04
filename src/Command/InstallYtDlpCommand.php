<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface; 
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
 
class InstallYtDlpCommand extends Command
{
    protected static $defaultName = 'app:install-yt-dlp';
    protected static $defaultDescription = 'Installe yt-dlp dans le dossier bin du projet';

    private $projectDir;

    public function __construct(string $projectDir)
    {
        parent::__construct();
        $this->projectDir = $projectDir;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();
        
        // Créer le dossier bin s'il n'existe pas
        $binDir = $this->projectDir . '/bin/tools';
        if (!$filesystem->exists($binDir)) {
            $filesystem->mkdir($binDir);
        }

        $ytDlpPath = $binDir . '/yt-dlp';
        if (PHP_OS_FAMILY === 'Windows') {
            $ytDlpPath .= '.exe';
            $downloadUrl = 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp.exe';
        } else {
            $downloadUrl = 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp';
        }

        $io->section('Téléchargement de yt-dlp');
        
        // Télécharger yt-dlp
        $process = new Process(['curl', '-L', $downloadUrl, '-o', $ytDlpPath]);
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error('Erreur lors du téléchargement de yt-dlp');
            return Command::FAILURE;
        }

        // Rendre le fichier exécutable (sauf sous Windows)
        if (PHP_OS_FAMILY !== 'Windows') {
            $filesystem->chmod($ytDlpPath, 0755);
        }

        $io->success('yt-dlp a été installé avec succès');
        return Command::SUCCESS;
    }
} 