<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\Tools\SchemaTool;

class DoctrineUpdateCommand extends \Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand {

  protected $ignoredEntities = array(
      'AppBundle\Entity\Statistic'
  );

  protected function executeSchemaCommand(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output, \Doctrine\ORM\Tools\SchemaTool $schemaTool, array $metadatas, \Symfony\Component\Console\Style\SymfonyStyle $ui)
  {
    $newMetadatas = array();
    foreach($metadatas as $metadata){
        if(!in_array($metadata->getName(), $this->ignoredEntities)){
            array_push($newMetadatas, $metadata);
        }
    }

    parent::executeSchemaCommand($input, $output, $schemaTool, $newMetadatas,$ui);
  }

}