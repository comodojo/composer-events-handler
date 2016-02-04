<?php namespace Comodojo\Composer;

/**
 * Composer Events retry handler.
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

abstract class Retry extends Logger {

    /**
     * Lists of methods to recall
     *
     * @var string
     */
    private $recall = array();

    /**
     * Enqueue a specific method of a class to be re-executed
     *
     * @param  string $method  Method to run
     * @param  string $class   Name of the class to retry
     */
    protected function appendRetry($method, $class) {

    	if (!isset($this->recall[$method])) {

    		$this->recall[$method] = array();

    	}

    	if (in_array($className, $this->recall[$method])) {

    		return false;

    	} else {

    		array_push($this->recall[$method], $className);

    		return true;

    	}

    }

    /**
     * Re-execute enqueued procedures
     */
    protected function doRetry() {

    	foreach ($this->recall as $method => $classes) {
	    	if (count($classes) > 0) {

				$this->sendToLog("\nRunning method '" . $method . "' of previously failed procedures:\n\n");

				$this->runPackageInstallation($classes, $method);

	    	}
    	}

		$this->sendToLog("\n");

    }

    /**
     * Execute all the installation procedures of a single package and handles the retry exception
     *
     * @param  array  $classes List of classes to run
     * @param  string $method  Method to run
     */
    abstract protected function runPackageInstallation($classes, $method);

}
