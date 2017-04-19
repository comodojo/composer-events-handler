<?php namespace Comodojo\Composer;

use \Composer\Composer;
use \Composer\IO\IOInterface;
use \Composer\Plugin\PluginInterface;
use \Composer\Plugin\PackageInterface;
use \Comodojo\Exception\ComposerRetryException;
use \Comodojo\Exception\ComposerEventException;
use \Exception;

/**
 * Composer Events Plugin. This class implements the composer plugin
 * interface and gives the ability to handle events to the EventsListener class.
 *
 * @package     Comodojo Spare Parts
 * @author      Marco Castiello <marco.castiello@gmail.com>
 * @author      Marco Giovinazzi <marco.giovinazzi@comodojo.org>
 * @license     MIT
 *
 * LICENSE:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class Plugin extends Retry implements PluginInterface {

    /**
     * Reference to the Composer instance
     *
     * @var \Composer\Composer
     */
    protected $composer = null;

    /**
     * Class name
     *
     * @var string
     */
    protected $className = "";

    /**
     * Plugin activator, this method is called by the composer framework
     * therefore it must be implemented, but we just use it to load the loggers
     *
     */
    public function activate(Composer $composer, IOInterface $io) {

    	$this->composer = $composer;

        $extra = $composer->getPackage()->getExtra();

        if (isset($extra['composer-events-log'])) {

        	$this->logfile = $extra['composer-events-log'];

        }

        $this->initLogs();

    }

    /**
     * Generic event handler, it runs the execution of the method associated to the event
     *
     * @param  PackageInterface $package Event to handle
     * @param  string           $method  Method to run
     *
     */
    protected function onEventHandler(PackageInterface $package, $method) {

    	$extra = $package->getExtra();

		if (isset($extra['composer-events-handler'])) {

			$this->sendToLog(
				sprintf("\nRunning %s procedures for package '%s':\n\n", $method, $package->getName())
			);

			$this->runPackageInstallation($extra['composer-events-handler'], $method);

		}

    }

    /**
     * Execute all the installation procedures of a single package and handles the retry exception
     *
     * @param  array  $classes List of classes to run
     * @param  string $method  Method to run
     */
    protected function runPackageInstallation($classes, $method) {

		foreach($classes as $class) {

			if ($this->setProcessingClass($class)) {

				try {

					$this->installClass($method, $extra);

					$this->sendToLog("OK\n");

				} catch (ComposerRetryException $e) {

					$this->sendToLog("Retry\n");

					$this->sendToLog(
						$e->getMessage(),
						$this->log
					);

				} catch (ComposerEventException $e) {

					$this->logError();

					$this->sendToLog("Failed\n");

					$this->sendToLog(
						$e->getMessage(),
						$this->log
					);

				}

			}

		}

    }

    /**
     * Check if a class is a subclass of the Installer class and then execute it
     *
     * @param  string $method Method to run
     *
     * @throws \Comodojo\Exception\ComposerRetryException
     * @throws \Comodojo\Exception\ComposerEventException
     */
    private function installClass($method) {

		try {

			if (is_subclass_of($this->className, 'Comodojo\Composer\EventsHandler')) {

				$this->execute($this->className, $method);

			} else {

				throw new Exception($this->className . " is not an implementation of the 'EventsHandler' class");

			}

		} catch (ComposerRetryException $e) {

			if (!$this->appendRetry($method, $this->className)) {

				throw new ComposerEventException("Installation still not works after retry: " . $e->getMessage());

			}

			throw $e;

		} catch (Exception $e) {

			throw new ComposerEventException($e->getMessage());

		}

    }

    /**
     * Execute a method of a class
     *
     * @param  string $class   Name of the class to load
     * @param  string $method  Method to run
     */
    private function execute($class, $method) {

		$installer = new $class($this->composer);

		if (method_exists($installer, $method)) {

			call_user_func(array($installer, $method));

		}


    }

    /**
     * Set the Setup Class to process
     *
     * @param  string $className
     *
     * @return boolean $exists Whether the class exists or not
     */
    private function setProcessingClass($className) {

		$this->className = $className;

		$className = preg_replace('/\\\\/', "::", $className);

		$this->sendToLog(sprintf("   %-60s", $className));

    	if (class_exists($this->className)) {

			return true;

    	} else {

    		$this->logError();

    		$this->sendToLog("Not Found\n");

			$this->sendToLog(
				"The class do not exists",
				$this->log
			);

    		$this->className = "";

    		return false;

    	}

    }

}
