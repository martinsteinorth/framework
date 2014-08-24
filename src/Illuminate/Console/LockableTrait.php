<?php namespace Illuminate\Console;

use Symfony\Component\Console\Output\OutputInterface;

trait LockableTrait {

	/**
	 * The output interface implementation.
	 *
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	private $_output = null;

	/**
	 * The absolute Pathname to the lockfile
	 *
	 * @var String
	 */
	private $_lockfilePathname = null;

	/**
	 * Performs the given callback inside a locked state, so no parallel executions
	 * with the same name are allowed.
	 *
	 * @param $name
	 * @param $callback
	 * @param OutputInterface $output
	 * @return mixed
	 * @throws \Exception
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
	 * Checks if a lock file exists and sets a new one if not existing
	 *
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
	 * @return void
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
	 * @param $name
	 * @return string
	 */
	private function getLockfilePathname($name)
	{
		$lockname = preg_replace('/[^A-Za-z0-9]/', '', $name);
		return storage_path() . '/' . $lockname . '.lock';
	}
}
