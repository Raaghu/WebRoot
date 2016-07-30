<?php

namespace icircle\webcore\console\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class BuildCommand extends Command {
	
	protected function configure(){
		$this->setName("build");
		$this->setDefinition(array(
			new InputOption('minimize','min',InputOption::VALUE_NONE,'Minimizes the output files')	
		));
	}
	
	protected function execute(InputInterface $input,OutputInterface $output){
		
	}
	
	private function deleteFile($path){
		if(is_dir($path)){
			$children = array_diff(scandir($path),array(".",".."));
			foreach ($children as $child){
				$this->deleteFile($path.'/'.$child);
			}
			rmdir($path);
		}else{
			unlink($path);
		}
	}
	
}