<?php
/**
 * A phing task to deploy pear packages to a pear channel
 *
 * PHP version 5
 *
 * @category Phing
 * @package  Phing_IlibPearDeployerTask
 * @author   Lars Olesen <lars@legestue.net>
 * @license  LGPL
 * @version  @package-version@
 * @link     http://public.intraface.dk/index.php?package=Phing_IlibPearDeployerTask
 */
require_once 'phing/Task.php';

/**
 * A phing task to deploy pear packages to a pear channel
 *
 * <code>
 * <project name="Phing_IlibPearDeployerTask" basedir="." default="make">
 *   <taskdef classname="phing.tasks.ext.IlibPearDeployerTask" name="peardeploy" />
 *   <!-- .. -->
 *   <target name="deploy" depends="make">
 *     <echo msg="Deploying package" />
 *     <peardeploy uri="${pear.channel.uri}" username="${pear.channel.username}" password="${pear.channel.password}">
 *       <fileset dir="./">
 *         <include name="${pear.package}"/>
 *       </fileset>
 *     </peardeploy>
 *   </target>
 *   <!-- .. -->
 * </project>
 * </code>
 *
 * @category Phing
 * @package  Phing_IlibPearDeployerTask
 * @author   Lars Olesen <lars@legestue.net>
 * @license  LGPL
 * @version  @package-version@
 * @link     http://public.intraface.dk/index.php?package=Phing_IlibPearDeployerTask
 */
class IlibPearDeployerTask extends Task
{
    protected $file;    // the source file (from xml attribute)
    protected $filesets = array(); // all fileset objects assigned to this task
    protected $uri;
    protected $username;
    protected $password;

    /**
     * Sets uri to the channel server
     *
     * @param string $url The uri for the channel server
     *
     * @return void
     */
    public function setUri($url)
    {
        $this->uri = $url;
    }

    /**
     * Sets username to use for the channel server
     *
     * @param string $aValue The username
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
     * @param string $aValue The password
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
     * @param PhingFile $file A file
     *
     * @return void
     */
    public function setFile(PhingFile $file)
    {
        $this->file = $file;
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
     * @todo Might be clever to make sure that there are some files to deploy.
     * @todo Does not throw error if it cannot login.
     *
     * @return void
     */
    public function main()
    {
        if (!isset($this->file) and count($this->filesets) == 0) {
            throw new BuildException("Missing either a nested fileset or attribute 'file' set");
        }

        if ($this->file instanceof PhingFile) {
            $this->deploy($this->file->getPath());
        } else { // process filesets
            $project = $this->getProject();
            foreach ($this->filesets as $fs) {
                $ds    = $fs->getDirectoryScanner($project);
                $files = $ds->getIncludedFiles();
                $dir   = $fs->getDir($this->project)->getPath();
                foreach ($files as $file) {
                    $this->deploy($dir.DIRECTORY_SEPARATOR.$file);
                }
            }
        }
    }

    /**
     * Performs the deployment
     *
     * @param string $file File to deploy
     *
     * @return void
     */
    protected function deploy($file)
    {
        include_once 'Salty/PEAR/Server/RemoteReleaseDeployer.php';
        $d           = new Salty_PEAR_Server_RemoteReleaseDeployer();
        $d->adminuri = $this->uri;
        $d->username = $this->username;
        $d->password = $this->password;

        if ($d->deployRelease($file)) {
            $this->log('Release has been deployed on the pear channel.');
        } else {
            throw new BuildException('Unable to deploy release to pear server.');
        }
    }
}