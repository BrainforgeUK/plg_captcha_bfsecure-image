<?php
/**
 * @package   CAPTCHA plugin uses Securimage
 * @author    https://www.brainforge.co.uk
 * @version   0.0.1
 * @author    https://www.brainforge.co.uk
 * @copyright (C) 2024 Jonathan Brain. All rights reserved.
 * @license   GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Extension\Bfsecurimage;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   4.3.0
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container)
            {
	            $dispatcher = $container->get(DispatcherInterface::class);
                $plugin = new Bfsecurimage(
	                $dispatcher,
                    (array) PluginHelper::getPlugin('captcha', 'bfsecurimage')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
