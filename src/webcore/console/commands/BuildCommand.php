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
use JMS\Composer\Graph\PackageNode;
use NodejsPhpFallback\Less;

class BuildCommand extends Command {
	
	private $builtPackages = array();
	
	/**
	 * @var ConsoleLogger
	 */
	private $logger = null;
	
	protected function configure(){
		$this->setName("build");
		$this->setDefinition(array(
			new InputOption('minimize','min',InputOption::VALUE_NONE,'Minimizes the output files')	
		));
	}
	
	protected function execute(InputInterface $input,OutputInterface $output){
		
		$this->logger = new ConsoleLogger($output);
		
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
				$this->logger->warning("extra.web.build-dir is not a directory , so default build directory (dist) is used");
			}else{
				$distDir = realpath($composerJson["extra"]["web"]["build-dir"]);
			}
		}else{
			$this->logger->info("extra.web.build-dir is not set , so default build directory (dist) is used");
		}
		
		$packageAnalyser = new DependencyAnalyzer();
		$packageGraph = $packageAnalyser->analyze($packageHome);
		
		$rootPackage = $packageGraph->getRootPackage();
		
		$this->buildPackages($rootPackage, $distDir);
	}
	
	
	private function buildPackages(PackageNode $packageNode,$distDir){
		
		$packageName = $packageNode->getName();
		
		if(in_array($packageName, $this->builtPackages) || $packageNode->isPhpExtension() || !$packageNode->hasAttribute('dir')){
			return;
		}
		
		// build child packages
		$outEdges = $packageNode->getOutEdges();
		foreach($outEdges as $outEdge){
			$childPackage = $outEdge->getDestPackage();
			$this->buildPackages($childPackage, $distDir);
			
		}
		
		// Package directory
		$pkgDirectory = $packageNode->getAttribute("dir");
		$this->builtPackages[] = $packageName;
		if(!file_exists($pkgDirectory.'/composer.json')){
			$this->logger->warning("SKIPPING Build for $packageName : composer.json not found");
			return;
		}
		
		$webSrcDirectory = $pkgDirectory."/websrc";
		if(!is_dir($webSrcDirectory)){
			$this->logger->info("SKIPPING Build for $packageName : no 'websrc' directory found");
			return;
		}
		
		// read the composer.json
		$composerJsonData = json_decode(file_get_contents($pkgDirectory.'/composer.json'),true);
		
		if($composerJsonData == null){
			$this->logger->warning("SKIPPING Build for $packageName : Invalid composer.json");
			return;
		}
		
		$pkgBuildConfig = null;
		if(isset($composerJsonData["extra"]["web"])){
			$pkgBuildConfig = $composerJsonData["extra"]["web"];
		}
		
		$this->buildWebSource($webSrcDirectory, $pkgBuildConfig, $distDir, $packageName);
		
	}
	
	/**
	 * 
	 * @param string $webSrcDirectory
	 * @param array  $config
	 * @param string $distDir
	 * @param string $packageName
	 */
	private function buildWebSource($webSrcDirectory,$config,$distDir,$packageName){
		if(!isset($config)){
			$this->logger->warning("SKIPPING Build for $packageName : Invalid composer.json");
			return;
		}
		
		if(is_array($config["compilers"])){
			foreach ($config["compilers"] as $compilerName=>$compilerOptions){
				switch (strtolower($compilerName)){
					case "less":
						if(is_array($compilerOptions)){
							foreach ($compilerOptions as $destinationFile=>$sourceFiles){
								if(is_string($sourceFiles)){
									$sourceFiles = array($sourceFiles);
								}

								$destinationFile = $distDir.'/'.$destinationFile;
								
								if(file_exists($destinationFile)){
									$this->logger->warning("$packageName is overwriting existing file : $destinationFile");
								}
								$this->ensureFileDir($destinationFile);
								foreach ($sourceFiles as $sourceFile){
									$lessCompiler = new Less($webSrcDirectory.'/'.$sourceFile);
									file_put_contents($destinationFile, $lessCompiler->getResult(),FILE_APPEND);
								}
								
							}
						}
				}
			}
		}
	}
	
	private function ensureFileDir($path){
		if(!is_dir(dirname($path))){
			mkdir(dirname($path),null,true);
		}
	}
	
	
}