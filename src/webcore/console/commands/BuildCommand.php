<?php

namespace icircle\webcore\console\commands;

use Composer\Autoload\ClassLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Logger\ConsoleLogger;
use JMS\Composer\DependencyAnalyzer;
use JMS\Composer\Graph\DependencyGraph;
use JMS\Composer\Graph\PackageNode;
use JMS\Composer\Graph\DependencyEdge;

class BuildCommand extends Command {
	
	private $builtPackages = array();
	
	protected function configure(){
		$this->setName("build");
		$this->setDefinition(array(
			new InputOption('minimize','min',InputOption::VALUE_NONE,'Minimizes the output files')	
		));
	}
	
	protected function execute(InputInterface $input,OutputInterface $output){
		
		$logger = new ConsoleLogger($output);
		
		$reflectionClasss = new \ReflectionClass('Composer\Autoload\ClassLoader');
		$classLoaderPath = $reflectionClasss->getFileName();
		$venderDir = $classLoaderPath;
		while (basename($venderDir) != "vendor"){
			$venderDir = dirname($venderDir);
		}
		
		$packageHome = dirname($venderDir);
		if (chdir($packageHome) ==  false){
			throw new RuntimeException("Unable to change directly to : ".$packageHome);
		}
		
		if(!file_exists($packageHome."/composer.json")){
			throw new RuntimeException("composer.json Not Found");
		}
		$composerJson = json_decode(file_get_contents($packageHome."/composer.json"),true);
		if($composerJson == null){
			throw new RuntimeException("Error in reading composer.json");
		}
		
		$distDir = $packageHome."/dist";
		
		if(isset($composerJson["extra"]["web"]["build-dir"])){
			if(!is_dir($composerJson["extra"]["web"]["build-dir"])){
				$logger->warning("extra.web.build-dir is not a directory , so default build directory (dist) is used");
			}else{
				$distDir = realpath($composerJson["extra"]["web"]["build-dir"]);
			}
		}else{
			$logger->info("extra.web.build-dir is not set , so default build directory (dist) is used");
		}
		
		$packageAnalyser = new DependencyAnalyzer();
		$packageGraph = $packageAnalyser->analyze($packageHome);
		
		$rootPackage = $packageGraph->getRootPackage();
		
		$this->buildPackages($rootPackage, $distDir);
	}
	
	
	private function buildPackages(PackageNode $packageNode,$distDir){
		
		$packageName = $packageNode->getName();
		
		if(in_array($packageName, $this->builtPackages) || $packageNode->isPhpExtension()){
			return;
		}
		
		// build child packages
		$outEdges = $packageNode->getOutEdges();
		foreach($outEdges as $outEdge){
			$childPackage = $outEdge->getDestPackage();
			$this->buildPackages($childPackage, $distDir);
			
		}
		
		
		echo $packageName."\n";
		$this->builtPackages[] = $packageName;
		
		if($packageName == "php"){
			print_r($packageNode);
		}
	}
	
	
}