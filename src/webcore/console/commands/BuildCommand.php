<?php

namespace icircle\webcore\console\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use NodejsPhpFallback\Less;
use NodejsPhpFallback\NodejsPhpFallback;
use Composer\Autoload\ClassLoader;

class BuildCommand extends Command {
	
	protected function configure(){
		$this->setName("build");
		$this->setDefinition(array(
			new InputOption('minimize','min',InputOption::VALUE_NONE,'Minimizes the output files')	
		));
	}
	
	protected function execute(InputInterface $input,OutputInterface $output){
		
		$reflectionClasss = new \ReflectionClass('Composer\Autoload\ClassLoader');
		$classLoaderPath = $reflectionClasss->getFileName();
		$venderDir = $classLoaderPath;
		while (basename($venderDir) != "vendor"){
			$venderDir = dirname($venderDir);
		}
		
		$packageHome = dirname($venderDir);
		
		$distDir = $packageHome."/dist";
		
		echo $distDir;
		
		
		
	}
	
}