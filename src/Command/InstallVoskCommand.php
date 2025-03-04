<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process; 
use Symfony\Component\Filesystem\Filesystem;
 
class InstallVoskCommand extends Command
{
    protected static $defaultName = 'app:install-vosk';
    protected static $defaultDescription = 'Installe Vosk et le modèle français';

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

        // Créer le dossier scripts s'il n'existe pas
        $scriptsDir = $this->projectDir . '/scripts';
        if (!$filesystem->exists($scriptsDir)) {
            $filesystem->mkdir($scriptsDir);
        }

        // Installer Vosk avec pip
        $io->section('Installation de Vosk');
        $process = new Process(['pip', 'install', 'vosk']);
        $process->setTimeout(300);
        $process->run(function ($type, $buffer) use ($io) {
            $io->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $io->error('Erreur lors de l\'installation de Vosk : ' . $process->getErrorOutput());
            return Command::FAILURE;
        }

        // Créer le répertoire pour le modèle
        $modelDir = $this->projectDir . '/model';
        if (!$filesystem->exists($modelDir)) {
            $filesystem->mkdir($modelDir);
        }

        // Télécharger le modèle
        $io->section('Téléchargement du modèle Vosk');
        $zipFile = $modelDir . '/vosk-model-fr-0.22.zip';
        
        $process = new Process([
            'curl', 
            '-L',
            'https://alphacephei.com/vosk/models/vosk-model-fr-0.22.zip',
            '-o',
            $zipFile
        ]);
        
        $process->setTimeout(3600);
        $process->run(function ($type, $buffer) use ($io) {
            $io->write('.');
        });

        if (!$process->isSuccessful()) {
            $io->error('Erreur lors du téléchargement du modèle : ' . $process->getErrorOutput());
            return Command::FAILURE;
        }

        // Dézipper le modèle
        $io->newLine(2);
        $io->section('Décompression du modèle');
        
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) === TRUE) {
            $zip->extractTo($modelDir);
            $zip->close();
            
            // Nettoyer
            $filesystem->remove($zipFile);
            
            $io->success('Installation terminée avec succès');
            return Command::SUCCESS;
        }

        $io->error('Erreur lors de la décompression du modèle');
        return Command::FAILURE;
    }
} 