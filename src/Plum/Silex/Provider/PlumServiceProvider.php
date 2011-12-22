<?php

/*
 * This file is part of the Plum package.
 *
 * (c) 2010-2011 Julien Brochet <mewt@madalynn.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plum\Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

use Plum\Plum;
use Plum\Server\Server;

class PlumServiceProvider implements ServiceProviderInterface
{
    protected $deployers = array('Plum\Deployer\RsyncDeployer');

    public function register(Application $app)
    {
        $deployers = isset($app['plum.deployers']) ? array_merge($this->deployers, $app['plum.deployers']) : $this->deployers;
        $servers   = isset($app['plum.servers']) ? $app['plum.servers'] : array();

        $app['plum'] = $app->share(function() use ($deployers, $servers) {
            $plum = new Plum();

            // Deployers
            foreach($deployers as $deployer) {
                $plum->registerDeployer(new $deployer());
            }

            // Servers
            foreach ($servers as $name => $array) {
                $server = new Server($array['host'], $array['user'], $array['dir'], $array['port']);
                $plum->addServer($name, $server);
            }

            return $plum;
        });

        $app['plum.deploy'] = $app->protect(function($server, $deployer, $options = array()) use ($app, $servers) {
            // Check for options
            $tmp = isset($servers[$server]) ? $servers[$server] : array();
            $tmp = isset($tmp['options']) ? $tmp['options'] : array();

            // Merge
            $options = array_merge($tmp, $options);

            return $app['plum']->deploy($server, $deployer, $options);
        });

        if (isset($app['plum.class_path'])) {
            $app['autoloader']->registerNamespace('Plum', $app['plum.class_path']);
        }
    }
}

?>
