<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  InfinitySoft 2011
 * @author     Tristan Lins <tristan.lins@infinitysoft.de>
 * @package    Compression API
 * @license    LGPL
 * @filesource
 */

/**
 * Class LessCss
 *
 * wrapper class for the less css compiler (http://lesscss.org)
 * @copyright  InfinitySoft 2011
 * @author     Tristan Lins <tristan.lins@infinitysoft.de>
 * @package    Compression API
 */
class LessCss extends AbstractMinimizer implements CssMinimizer
{
	/**
	 * load the jsmin class
	 */
	public function  __construct()
	{
		parent::__construct();
		$this->configure(array
		(
			'lessc' => '/var/lib/gems/1.8/bin/lessc'
		));
	}

	
	/**
	 * Create a temporary file and return a contao relative path.
	 * 
	 * @return string
	 */
	private function createTempFile()
	{
		return substr(tempnam(TL_ROOT . '/system/html', 'LessCss_'), strlen(TL_ROOT)+1);
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see Minimizer::minimize($strSource, $strTarget)
	 */
	public function minimize($strSource, $strTarget)
	{
		$strCmd  = escapeshellcmd($this->arrConfig['lessc']);
		$strCmd .= ' ' . escapeshellarg(TL_ROOT . '/' . $strSource);
		$strcmd .= ' ' . escapeshellarg(TL_ROOT . '/' . $strTarget);
		
		// execute lessc
		$procLessC = proc_open(
			$strCmd,
			array(
				0 => array("pipe", "r"),
				1 => array("pipe", "w"),
				2 => array("pipe", "w")
			),
			$arrPipes);
		if ($procLessC === false)
		{
			$this->log(sprintf("lessc could not be started!<br/>\ncmd: %s", $strCmd), 'LessCss::minimize', TL_ERROR);
			return false;
		}
		// close stdin
		fclose($arrPipes[0]);
		// close stdout
		fclose($arrPipes[1]);
		// read and close stderr
		$strErr = stream_get_contents($arrPipes[2]);
		fclose($arrPipes[2]);
		// wait until yui-compressor terminates
		$intCode = proc_close($procLessC);
		if ($intCode != 0)
		{
			$this->log(sprintf("Execution of lessc failed!<br/>\ncmd: %s<br/>\nstderr: %s", $strCmd, $strErr), 'LessCss::minimize', TL_ERROR);
			return false;
		}
		return true;
	}


	/**
	 * (non-PHPdoc)
	 * @see Minimizer::minimizeFromFile($strFile)
	 */
	public function minimizeFromFile($strFile)
	{
		// create temporary output file
		$strTemp = $this->createTempFile();
		$objFile = new File($strTemp);
		// minimize
		if (!$this->minimize($strFile, $strTemp))
		{
			$objFile->delete();
			return false;
		}
		// read temporary file
		$strCode = $objFile->getContent();
		$objFile->close();
		// delete temporary file
		$objFile->delete();
		// return code
		return $strCode;
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see Minimizer::minimizeToFile($strFile, $strCode)
	 */
	public function minimizeToFile($strFile, $strCode)
	{
		// create temporary output file
		$strTemp = $this->createTempFile();
		$objFile = new File($strTemp);
		$objFile->write($strCode);
		if (!$this->minimize($strTemp, $strFile))
		{
			$objFile->delete();
			return false;
		}
		$objFile->delete();
		return true;
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see Minimizer::minimizeCode($strCode)
	 */
	public function minimizeCode($strCode)
	{
		// create temporary input file
		$strTemp = $this->createTempFile();
		// write source
		$objFile = new File($strTemp);
		$objFile->write($strCode);
		$objFile->close();
		// minimize
		$strCode = $this->minimizeFile(substr($strTemp, strlen(TL_ROOT)+1));
		// delete temporary file
		$objFile->delete();
		// return code
		return $strCode;
	}
}
?>