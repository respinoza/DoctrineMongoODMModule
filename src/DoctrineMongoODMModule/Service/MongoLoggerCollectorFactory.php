<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace DoctrineMongoODMModule\Service;

use DoctrineModule\Service\AbstractFactory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use DoctrineMongoODMModule\Collector\MongoLoggerCollector;

use DoctrineMongoODMModule\Logging\DebugStack;
use DoctrineMongoODMModule\Logging\LoggerChain;

/**
 * Mongo Logger Configuration ServiceManager factory
 *
 * @license MIT
 * @link    http://www.doctrine-project.org/
 */
class MongoLoggerCollectorFactory extends AbstractFactory
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $this($serviceLocator, __CLASS__);
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsClass()
    {
        return 'DoctrineMongoODMModule\Options\MongoLoggerCollector';
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var $mongoLoggerCollectOptions \DoctrineMongoODMModule\Options\MongoLoggerCollector */
        $mongoLoggerCollectOptions = $this->getOptions($container, 'mongo_logger_collector');

        if ($mongoLoggerCollectOptions->getMongoLogger()) {
            $debugStackLogger = $container->get($mongoLoggerCollectOptions->getMongoLogger());
        } else {
            $debugStackLogger = new DebugStack();
        }

        /** @var $mongoLoggerCollectOptions \Doctrine\ODM\MongoDB\Configuration */
        $configuration = $container->get($mongoLoggerCollectOptions->getConfiguration());

        if (null !== $configuration->getLoggerCallable()) {
            $logger = new LoggerChain();
            $logger->addLogger($debugStackLogger);
            $callable = $configuration->getLoggerCallable();
            $logger->addLogger($callable[0]);
            $configuration->setLoggerCallable(array($logger, 'log'));
        } else {
            $configuration->setLoggerCallable(array($debugStackLogger, 'log'));
        }

        return new MongoLoggerCollector($debugStackLogger, $mongoLoggerCollectOptions->getName());
    }
}
