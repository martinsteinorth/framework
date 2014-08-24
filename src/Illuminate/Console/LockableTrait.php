<?php namespace Illuminate\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

trait LockableTrait {

	/**
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	private $_output = null;

	/**
	 * @var String
	 */
	private $_lockfilePathname = null;

	/**
	 * @param $name
	 * @param $callback
	 * @param OutputInterface $output
	 * @return mixed
	 */
	public function performLocked($name, $callback, OutputInterface $output = null)
	{
		$this->_output = $output;
		$this->_lockfilePathname = $this->getLockfilePathname($name);
		$returnValue = 1;

		$proceed = $this->assertLockState();

		if (!$proceed) return $returnValue;

		try
		{
			if (is_callable($callback))
			{
				$returnValue = call_user_func($callback);
			}
		}
		catch (\Exception $e)
		{
			$this->clearLockState();
			throw $e;
		}

		$this->clearLockState();

		return $returnValue;
	}

	/**
	 * Check if a lock file exists and sets a new one if not existing
	 *
	 * @param  string    $warning
	 * @param  \Closure  $callback
	 * @return bool
	 */
	private function assertLockState()
	{
		if (file_exists($this->_lockfilePathname))
		{
			if ($this->_output instanceof OutputInterface)
			{
				$this->_output->writeln('<info>Command is already running and has not finished yet.</info>');
			}

			return false;
		}
		else
		{
			touch($this->_lockfilePathname);
		}

		return true;
	}

	/**
	 * Removes the lockfile if set
	 *
	 * @param Command $command
	 */
	private function clearLockState()
	{
		if (file_exists($this->_lockfilePathname))
		{
			unlink($this->_lockfilePathname);
		}
	}

	/**
	 * Returns the absolute pathname to the lockfile for the current command
	 *
	 * @param Command $command
	 * @return string
	 */
	private function getLockfilePathname($name)
	{
		// TODO: Sanitize name and remove common special characters
		return storage_path() . '/' . strtolower(str_replace(':', '', $name)) . '.lock';
	}
}
