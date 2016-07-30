<?php

namespace icircle\webcore\console\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanCommand extends Command {
	
	protected function configure(){
		$this->setName("clean");
		$this->setDefinition(array(
			new InputArgument('directory',InputArgument::REQUIRED,'Directory to clean')	
		));
	}
	
	protected function execute(InputInterface $input,OutputInterface $output){
		$dir = $input->getArgument("directory");
		if(is_dir($dir)){
			$children = array_diff(scandir($dir),array(".",".."));
			foreach ($children as $child){
				$this->deleteFile($dir.'/'.$child);
			}
		}
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