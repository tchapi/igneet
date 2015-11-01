<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),

            /* Third Party */
            new Knp\Bundle\MarkdownBundle\KnpMarkdownBundle(), /* Provides Markdown conversion */
            new Bazinga\Bundle\JsTranslationBundle\BazingaJsTranslationBundle(), /* Provides translation for JS files */
            new Fp\OpenIdBundle\FpOpenIdBundle(), /* To use Open Id for login */
            new RobertoTru\ToInlineStyleEmailBundle\RobertoTruToInlineStyleEmailBundle(), /* For inlining styles in mails */
            new FOS\ElasticaBundle\FOSElasticaBundle(), /* Elastic Search, for search, you know */

            new meta\GeneralBundle\metaGeneralBundle(),
            new meta\UserBundle\metaUserBundle(),
            new meta\ProjectBundle\metaProjectBundle(),
            new meta\IdeaBundle\metaIdeaBundle(),
            new meta\StaticBundle\metaStaticBundle(),
            new meta\AdminBundle\metaAdminBundle(),
            new meta\SubscriptionBundle\metaSubscriptionBundle(),
            
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
