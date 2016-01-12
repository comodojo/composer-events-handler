# composer-events-handler

This package contains a [Composer Plugin](https://getcomposer.org/doc/articles/plugins.md) that allows you to create installation procedures that will be executed during the `` composer install `` or `` composer update ``.

## Why do you need this plugin?

If you're an avid Composer user, you'll probably know that you can add post installation scripts in your project `` composer.json ``, but you can't do the same with every child package because... reasons.

Well, to be fair, this is not natively implemented in Composer because of the security problems it can leads to, but there are times, when you work in a controlled environment and you're trying to organize the development process between various workgroups, that you'll find the need to be able to initialize your packages right after the installation.

You may need to initialize the database, creating tables, setting up variables or creating folders under your installation path. Whatever you're up to, this sort of things should be done before the code is executed for the first time. This is why you need this plugin.

## Installation

Install [composer](https://getcomposer.org/), then:

`` composer require comodojo/composer-events-handler 0.0.1 ``

## How to make it work

This package is implemented as a [Composer Plugin](https://getcomposer.org/doc/articles/plugins.md). This means you can't manually execute this code. It will be loaded by the composer itself after every installation or update.

After you've installed this plugin in your main project, in order to make it work, you need to follow this two steps in your child package:

### Step 1: Create a Setup Class

You need to create a class in your package that extends the abstract class `` \Comodojo\Composer\EventsHandler `` implementing one or more of the following methods:

```php 
install()   // Called after the installation of the package
update()    // Called after the package has been updated
uninstall() // Called before the package is uninstalled (you can use it to clean up)
finalize()  // Always called after composer has finished doing its job
```

For example:

```php

namespace MyProject\MyApp;

class MyAppSetup extends \Comodojo\Composer\EventsHandler {

    public function finalize() {
        
		// Do awesome stuff

    }

}

```

### Step 2: Add the install information to your `` composer.json ``

When your Setup Class is ready, you need to reference it into the `` composer.json `` of your package. Under the `` extra `` field you need to create an array called `` composer-events-handler `` which lists all the Setup Classes you created (yep, you can create as much as you like)

```javascript

{
    "extra": {
        "composer-events-handler": [
            "MyProject\\MyApp\\MyAppSetup"
        ]
    }
}

```

The procedures will be executed in the same order they are listed into the `` composer-events-handler `` array.

## How to handle dependencies between packages

When you work on a big project with a lot of packages, it can happens that your Setup Class depends on informations or files created by another package. Sadly, the Composer framework doesn't allow us to know in which order the various classes will be executed.
Basically, you can control the order of execution within a package, but you don't know which package will be installed first. This is because updated or new packages catch the update event before the other packages.

The best practice should be to not create dependencies between packages at all but, let's be honest, this is quite impossible. The handling of this issue is quite completely demanded to your coding skills, but in order to help you, we introduced the method `` retry() ``.
You can put all your code that depends on other packages within one or more methods of your Setup Class and then call them through the `` retry() `` method.

```php

namespace MyProject\MyApp;

class MyAppSetup extends \Comodojo\Composer\EventsHandler {

    public function install() {
        
		// Do awesome stuff
		
		$this->retry("doStuff");

    }
    
    public function doStuff() {
    	
    	// load an object that may be installed by another package
    	try {
    	
    		$obj = new MyOtherPackageObject();
    		
    		$obj->doSomethingAwesome();
    		
    	} catch (\Exception $e) {
    	
    		throw $e;
    	
    	}
    	
    }

}

```

If the execution of those methods fails, the whole package will be added to a queue of failed executions. When all the installations are completed, all the failed setups will be executed again.
Of course, the execution can fail again. The reasons can be as follows:

- The execution failed because of coding errors (check the log file for a list of error messages)
- The package depends on another package whose installation also failed

If the latter is the case, you can try running the `` composer update `` again.

## The log file

In order to keep things clean, this plugin prints just few information on the screen. It tells you which package is installing and if the execution has been completed or it's failed.
If you want to know what's been wrong with your installation, all the exception messages are stored in a log file. By default this log is set as `` ./composer-events.log ``, but you're able to configure it as you like it by editing the `` composer.json `` of your root package (the project) as follows:

```javascript

{
    "extra": {
        "composer-events-log": "./path-to-my-file.log"
    }
}

```

## Contributing

Contributions are welcome and will be fully credited. Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

`` comodojo/composer-events-handler `` is released under the MIT License (MIT). Please see [License File](LICENSE) for more information.
