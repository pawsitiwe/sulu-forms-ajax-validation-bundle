<?php

namespace Pawsitiwe;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Reference;

class SuluFormsAjaxValidationBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        // Nur registrieren, wenn der Twig Loader existiert
        if ($container->hasDefinition('twig.loader.native_filesystem')) {
            $container->getDefinition('twig.loader.native_filesystem')
                ->addMethodCall('addPath', [__DIR__.'/Resources/views', 'SuluFormsAjaxValidation']);
        }
    }
}
