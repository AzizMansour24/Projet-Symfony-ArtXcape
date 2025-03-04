<?php

namespace App\Command;

use App\Entity\Model3D;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddTestModelCommand extends Command
{
    protected static $defaultName = 'app:add-test-model';
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get first event
        $event = $this->entityManager->getRepository(Event::class)->findOneBy([]);
        
        if (!$event) {
            $output->writeln('No events found');
            return Command::FAILURE;
        }

        $model = new Model3D();
        $model->setName('Test Model');
        $model->setDescription('This is a test 3D model');
        $model->setFileUrl('your_model.glb'); // Make sure this file exists in public/uploads/models/
        $model->setEvent($event);

        $this->entityManager->persist($model);
        $this->entityManager->flush();

        $output->writeln('Test model added successfully');
        return Command::SUCCESS;
    }
} 