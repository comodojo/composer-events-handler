<?php namespace Comodojo\Composer;

/**
 * The composer framework doesn't import the autoload during execution,
 * so, in order to work with installed classes, we must include it ourselves
 */
require __DIR__ . "/../../../autoload.php";

use \Composer\EventDispatcher\EventSubscriberInterface;
use \Composer\Script\ScriptEvents;
use \Composer\Script\PackageEvent;
use \Composer\Script\CommandEvent;

/**
 * Composer Events Listener. This class is a composer plugins
 * that execute installation procedures after "composer" execution.
 *
 * In order to make it works, you must create a class that
 * extends the \Comodojo\Composer\EventsHandler class implementing
 * the "finalize" method, then you have to add the tag "composer-events-handler"
 * in the "extra" section of your "composer.json" listing all the
 * class that must be executed.
 *
 * Example:
 *
 * {
 *     [...]
 *     "extra": {
 *         "composer-events-handler": [
 *             "MyApp\\Setup\\SetupProcedureClass1",
 *             "MyApp\\Setup\\SetupProcedureClass2",
 *             "MyApp\\Setup\\SetupProcedureClass3"
 *         ]
 *     }
 * }
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

class EventsListener extends Plugin implements EventSubscriberInterface {

    /**
     * Static method to retrieve the list of events to handle
     *
     * @return  array   $eventList  An array containing the list of events and their listeners
     */
    public static function getSubscribedEvents() {

        return array(
            ScriptEvents::POST_INSTALL_CMD          => 'onPostInstallCMD',
            ScriptEvents::POST_UPDATE_CMD           => 'onPostInstallCMD',
            ScriptEvents::POST_CREATE_PROJECT_CMD   => 'onPostInstallCMD',
            ScriptEvents::POST_ROOT_PACKAGE_INSTALL => 'onPostPackageInstall',
            ScriptEvents::POST_PACKAGE_INSTALL      => 'onPostPackageInstall',
            ScriptEvents::POST_PACKAGE_UPDATE       => 'onPostPackageUpdate',
            ScriptEvents::PRE_PACKAGE_UNINSTALL     => 'onPrePackageUninstall'
        );

    }

    /**
     * Listener to the POST_INSTALL_CMD, POST_UPDATE_CMD and POST_CREATE_PROJECT_CMD events
     * It contains the code that launch the execution of the finalize precedures
     *
     * @param  \Composer\Script\CommandEvent $event Event to handle
     *
     */
    public function onPostInstallCMD(CommandEvent $event) {

    	$packages = $event->getComposer()
    		->getRepositoryManager()
    		->getLocalRepository()
    		->getPackages();

    	foreach ($packages as $package) {

    		$this->onEventHandler($package, "finalize");

    	}

    	$this->doRetry();

    }

    /**
     * Listener to the POST_PACKAGE_INSTALL events
     * It contains the code that launch the execution of the installation precedures
     *
     * @param  \Composer\Script\PackageEvent $event Event to handle
     *
     */
    public function onPostPackageInstall(PackageEvent $event) {

    	$package = $event->getOperation()->getPackage();

    	$this->onEventHandler($package, "install");

    }

    /**
     * Listener to the POST_PACKAGE_UPDATE events
     * It contains the code that launch the execution of the update precedures
     *
     * @param  \Composer\Script\PackageEvent $event Event to handle
     *
     */
    public function onPostPackageUpdate(PackageEvent $event) {

    	$package = $event->getOperation()->getPackage();

    	$this->onEventHandler($package, "update");

    }

    /**
     * Listener to the PRE_PACKAGE_UNINSTALL events
     * It contains the code that launch the execution of the update precedures
     *
     * @param  \Composer\Script\PackageEvent $event Event to handle
     *
     */
    public function onPrePackageUninstall(PackageEvent $event) {

    	$package = $event->getOperation()->getPackage();

    	$this->onEventHandler($package, "uninstall");

    }

}
