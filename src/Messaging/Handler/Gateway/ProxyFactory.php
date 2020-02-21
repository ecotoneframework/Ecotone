<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Handler\Gateway;

use Closure;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\MessagingException;
use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ProxyManager\Factory\RemoteObjectFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use ProxyManager\Signature\ClassSignatureGenerator;
use ProxyManager\Signature\SignatureGenerator;
use ProxyManager\Version;
use stdClass;

/**
 * Class LazyProxyConfiguration
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ProxyFactory implements \Serializable
{
    const REFERENCE_NAME = "gatewayProxyConfiguration";

    /**
     * @var Configuration
     */
    private $configuration;
    /**
     * @var bool
     */
    private $isLocked = false;
    /**
     * @var string|null
     */
    private $cacheDirectoryPath;

    /**
     * ProxyConfiguration constructor.
     * @param Configuration $configuration
     * @param string|null $cacheDirectoryPath
     */
    private function __construct(Configuration $configuration, ?string $cacheDirectoryPath)
    {
        $this->configuration = $configuration;
        $this->cacheDirectoryPath = $cacheDirectoryPath;
    }

    /**
     * @param string $cacheDirectoryPath
     * @return ProxyFactory
     */
    public static function createWithCache(string $cacheDirectoryPath) : self
    {
        $configuration = new Configuration();
        $configuration->setProxiesTargetDir($cacheDirectoryPath);
        $fileLocator = new FileLocator($configuration->getProxiesTargetDir());
        $configuration->setGeneratorStrategy(new FileWriterGeneratorStrategy($fileLocator));
        $configuration->setClassSignatureGenerator(new ClassSignatureGenerator(new SignatureGenerator()));

        return new self($configuration, $cacheDirectoryPath);
    }

    /**
     * @return ProxyFactory
     */
    public static function createNoCache() : self
    {
        return new self(new Configuration(), null);
    }

    /**
     * @return Configuration
     */
    public function getConfiguration() : Configuration
    {
        return $this->configuration;
    }

    /**
     * @param string[] $classes
     */
    public function warmUpCacheFor(array $classes): void
    {
        if (!$classes) {
            return;
        }

        foreach ($classes as $className) {
            $factory = new LazyLoadingValueHolderFactory($this->configuration);
            $factory->createProxy(
                $className,
                function (& $wrappedObject, $proxy, $method, $parameters, & $initializer) {
                    $wrappedObject = new stdClass();

                    return true;
                }
            );

            $factory = new RemoteObjectFactory(new class () implements AdapterInterface
            {
                /**
                 * @inheritDoc
                 */
                public function call(string $wrappedClass, string $method, array $params = [])
                {
                    return 0;
                }
            }, $this->configuration);

            $factory->createProxy($className);
        }
        $this->lockConfiguration();;
    }

    /**
     * @param string $interfaceName
     * @param Closure $buildCallback
     * @return object
     * @throws MessagingException
     */
    public function createProxyClass(string $interfaceName, Closure $buildCallback): object
    {
        if ($this->isLocked && !$this->hasCachedVersion($interfaceName)) {
            throw ConfigurationException::create("There is problem with configuration. Proxy class for {$interfaceName} was not pregenerated. Can't use lazy loading configuration.");
        }

        $factory = new LazyLoadingValueHolderFactory($this->configuration);
        return $factory->createProxy(
            $interfaceName,
            function (& $wrappedObject, $proxy, $method, $parameters, & $initializer) use ($interfaceName, $buildCallback) {
                $factory = new RemoteObjectFactory(new class ($buildCallback) implements AdapterInterface
                {
                    /**
                     * @var Closure
                     */
                    private $buildCallback;

                    /**
                     *  constructor.
                     *
                     * @param Closure $buildCallback
                     */
                    public function __construct(Closure $buildCallback)
                    {
                        $this->buildCallback = $buildCallback;
                    }

                    /**
                     * @inheritDoc
                     */
                    public function call(string $wrappedClass, string $method, array $params = [])
                    {
                        $buildCallback = $this->buildCallback;
                        $gateway = $buildCallback();

                        return $gateway->execute($params);
                    }
                }, $this->configuration);

                $wrappedObject = $factory->createProxy($interfaceName);

                return true;
            }
        );
    }

    /**
     * @param string $interfaceName
     * @return bool
     */
    public function hasCachedVersion(string $interfaceName): bool
    {
        $proxyClassName =
            $this->configuration
            ->getClassNameInflector()
            ->getProxyClassName($interfaceName, [
                'className' => $interfaceName,
                'factory' => RemoteObjectFactory::class,
                'proxyManagerVersion' => Version::getVersion(),
                'proxyOptions' => []
            ]);

        if (!class_exists($proxyClassName)) {
            $proxyClassName =
                $this->configuration
                    ->getClassNameInflector()
                    ->getProxyClassName($interfaceName, [
                        'className' => $interfaceName,
                        'factory' => LazyLoadingValueHolderFactory::class,
                        'proxyManagerVersion' => Version::getVersion(),
                        'proxyOptions' => []
                    ]);
        }

        return class_exists($proxyClassName);
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return serialize(["path" => $this->cacheDirectoryPath, "isLocked" => $this->isLocked]);
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $serializedProxy = unserialize($serialized);
        $path  = $serializedProxy['path'];
        if (is_null($path)) {
            $cache = self::createNoCache();
        }else {
            $cache = self::createWithCache($path);
            if ($serializedProxy['isLocked']) {
                $cache->lockConfiguration();
            }
        }

        $this->configuration = $cache->configuration;
        $this->isLocked = $cache->isLocked;
        $this->cacheDirectoryPath = $cache->cacheDirectoryPath;
    }

    private function lockConfiguration() : void
    {
        $this->isLocked = true;
        spl_autoload_register($this->configuration->getProxyAutoloader());
    }
}