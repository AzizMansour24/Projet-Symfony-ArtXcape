<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class InstallFFmpegCommand extends Command
{
    protected static $defaultName = 'app:install-ffmpeg';
    protected static $defaultDescription = 'Installe FFmpeg dans le dossier bin du projet';

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
        
        $binDir = $this->projectDir . '/bin/tools';
        if (!$filesystem->exists($binDir)) {
            $filesystem->mkdir($binDir);
        }

        // Utiliser un lien plus fiable
        $ffmpegUrl = 'https://www.gyan.dev/ffmpeg/builds/ffmpeg-release-essentials.zip';
        $zipFile = $binDir . '/ffmpeg.zip';
        
        $io->section('Téléchargement de FFmpeg');
        
        // Utiliser cURL directement avec PHP
        $io->text('Téléchargement en cours...');
        
        $ch = curl_init($ffmpegUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
        
        $content = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $io->error('Erreur lors du téléchargement: ' . curl_error($ch));
            curl_close($ch);
            return Command::FAILURE;
        }
        
        curl_close($ch);
        
        if (!file_put_contents($zipFile, $content)) {
            $io->error('Erreur lors de l\'écriture du fichier zip');
            return Command::FAILURE;
        }

        $io->text('Extraction des fichiers...');

        // Extraire le fichier ZIP
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) === TRUE) {
            $zip->extractTo($binDir);
            $zip->close();

            // Les fichiers seront dans un sous-dossier comme ffmpeg-6.1-essentials_build/bin/
            // Trouver le dossier exact
            $ffmpegDirs = glob($binDir . '/ffmpeg-*-essentials_build/bin');
            if (!empty($ffmpegDirs)) {
                $ffmpegDir = $ffmpegDirs[0];
                
                $io->text('Copie des fichiers FFmpeg...');
                $filesystem->copy($ffmpegDir . '/ffmpeg.exe', $binDir . '/ffmpeg.exe', true);
                $filesystem->copy($ffmpegDir . '/ffprobe.exe', $binDir . '/ffprobe.exe', true);
                
                // Nettoyer
                $io->text('Nettoyage des fichiers temporaires...');
                $filesystem->remove($zipFile);
                $filesystem->remove(dirname($ffmpegDir));

                $io->success('FFmpeg a été installé avec succès');
                return Command::SUCCESS;
            }
        }

        $io->error('Erreur lors de l\'extraction de FFmpeg');
        return Command::FAILURE;
    }
} 