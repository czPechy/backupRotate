<?php
require_once __DIR__ . '/vendor/autoload.php';

$output = new \Symfony\Component\Console\Output\ConsoleOutput();
$params = new \Symfony\Component\Console\Input\ArgvInput();

if($params->hasParameterOption('--help') || $params->hasParameterOption('-h')) {
    $output->write('', true);
    $output->write('Backup rotation tool for VestaCP', true);
    $output->write('', true);
    $output->write('-d  define storage path of your backups', true);
    $output->write('    requred: true', true);
    $output->write('    default: null', true);
    $output->write('', true);
    $output->write('-k  define number of backups what do you want keep', true);
    $output->write('    requred: false', true);
    $output->write('    default: 3', true);
    exit();
}

$storage = $params->getParameterOption('-d');
if(!$storage) {
    $output->write('Specify backup storage path with -d parameter', true);
    exit(-1);
}
if(!is_dir($storage)) {
    $output->write('Storage path defined with -d parameter is not dir', true);
    exit(-1);
}

define('BACKUP_STORAGE', $storage);
define('BACKUP_MASK_FINDER', '*.tar');
define('BACKUP_MASK', '~(?<user>[a-zA-Z]+)\.(?<date>\d+-\d+-\d+_\d+-\d+-\d+)\.tar~');
define('BACKUP_KEEP', (int) $params->getParameterOption('-k', 3));

$foundBackups = [];
$deleteFiles = [];

/** @var SplFileInfo[] $files */
$files = \Nette\Utils\Finder::find(BACKUP_MASK_FINDER )->from( BACKUP_STORAGE );
foreach($files as $file) {
    if(preg_match(BACKUP_MASK, $file->getBasename(),$result)) {
        if(!isset($foundBackups[$result['user']])) {
            $foundBackups[$result['user']] = [];
        }
        $foundBackups[$result['user']][$result['date']] = $file;
    }
}

foreach($foundBackups as $user => $backups) {
    ksort($backups);
    while(\count($backups) > BACKUP_KEEP) {
        $deleteFiles[] = array_shift($backups);
    }
}

/** @var SplFileInfo[] $deleteFiles */
foreach($deleteFiles as $file) {
    $filePath = (string) $file;
    if(!@unlink($filePath)) {
        $output->write('Cannot delete file \'' . $filePath . '\'', true);
        exit(-1);
    }
}