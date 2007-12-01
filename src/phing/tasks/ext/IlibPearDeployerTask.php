<?php
/**
 * A task to deploy pear packages
 *
 * @author   Lars Olesen <lars@legestue.net>
 * @package phing.tasks.ext
 */
require_once 'phing/Task.php';

/**
 * A task to deploy pear packages
 *
 * @author   Lars Olesen <lars@legestue.net>
 * @package phing.tasks.ext
 */
class IlibPearDeployTask extends Task
{
    protected $file;    // the source file (from xml attribute)
    protected $filesets = array(); // all fileset objects assigned to this task

    protected $errorProperty;
    protected $haltOnFailure = false;
    protected $hasErrors = false;
    private $badFiles = array();

    /**
     * The haltonfailure property
     *
     * @param boolean $aValue
     *
     * @return void
     */
    public function setHaltOnFailure($aValue)
    {
        $this->haltOnFailure = $aValue;
    }

    /**
     * Sets uri to the channel server
     *
     * @param string $aValue
     *
     * @return void
     */
    public function setUri($aValue)
    {
        $this->uri = $aValue;
    }

    /**
     * Sets username to use for the channel server
     *
     * @param string $aValue
     *
     * @return void
     */
    public function setUsername($aValue)
    {
        $this->username = $aValue;
    }

    /**
     * Sets password for the channel server
     *
     * @param string $aValue
     *
     * @return void
     */
    public function setPassword($aValue)
    {
        $this->password = $aValue;
    }

    /**
     * File to be performed syntax check on
     *
     * @param PhingFile $file
     *
     * @return void
     */
    public function setFile(PhingFile $file)
    {
        $this->file = $file;
    }

    /**
     * Set an property name in which to put any errors.
     *
     * @param string $propname
     *
     * @return void
     */
    public function setErrorproperty($propname)
    {
        $this->errorProperty = $propname;
    }

    /**
     * Nested creator, creates a FileSet for this task
     *
     * @return FileSet The created fileset object
     */
    function createFileSet()
    {
        $num = array_push($this->filesets, new FileSet());
        return $this->filesets[$num-1];
    }

    /**
     * Execute lint check against PhingFile or a FileSet
     *
     * @return void
     */
    public function main()
    {
        if(!isset($this->file) and count($this->filesets) == 0) {
            throw new BuildException("Missing either a nested fileset or attribute 'file' set");
        }

        if($this->file instanceof PhingFile) {
            $this->phpcs($this->file->getPath());
        } else { // process filesets
            $project = $this->getProject();
            foreach($this->filesets as $fs) {
                $ds = $fs->getDirectoryScanner($project);
                $files = $ds->getIncludedFiles();
                $dir = $fs->getDir($this->project)->getPath();
                foreach($files as $file) {
                    $this->deploy($dir.DIRECTORY_SEPARATOR.$file);
                }
            }
        }

        if ($this->haltOnFailure && $this->hasErrors) throw new BuildException('Syntax error(s) in PHP files: '.implode(', ',$this->badFiles));
    }

    /**
     * Performs the deployment
     *
     * @param string $file
     *
     * @return void
     */
    protected function deploy($file)
    {
        require_once 'Salty/PEAR/Server/RemoteReleaseDeployer.php';
        $d = new Salty_PEAR_Server_RemoteReleaseDeployer();
        $d->adminuri = $this->uri;
        $d->username = $this->username;
        $d->password = $this->password;

        if ($d->deployRelease($file)) {
            //echo 'Success!';
        } else {
            throw new BuildException("Unable to deploy release to pear server.", new Exception($e->getMessage()));
        }
    }
}