<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp;

use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Queue\Consumer;
use Interop\Queue\Exception\Exception;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use SimplyCodedSoftware\Messaging\Endpoint\MessageDrivenChannelAdapter\MessageDrivenChannelAdapter;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use Throwable;

/**
 * Class InboundEnqueueGateway
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InboundAmqpEnqueueGateway implements MessageDrivenChannelAdapter
{
    /**
     * @var AmqpConnectionFactory
     */
    private $amqpConnectionFactory;
    /**
     * @var InboundAmqpGateway
     */
    private $inboundAmqpGateway;
    /**
     * @var bool
     */
    private $declareOnStartup;
    /**
     * @var AmqpAdmin
     */
    private $amqpAdmin;
    /**
     * @var string
     */
    private $amqpQueueName;
    /**
     * @var int
     */
    private $receiveTimeoutInMilliseconds;
    /**
     * @var string
     */
    private $acknowledgeMode;

    /**
     * InboundAmqpEnqueueGateway constructor.
     * @param AmqpConnectionFactory $amqpConnectionFactory
     * @param InboundAmqpGateway $inboundAmqpGateway
     * @param AmqpAdmin $amqpAdmin
     * @param bool $declareOnStartup
     * @param string $amqpQueueName
     * @param int $receiveTimeoutInMilliseconds
     * @param string $acknowledgeMode
     */
    public function __construct(
        AmqpConnectionFactory $amqpConnectionFactory,
        InboundAmqpGateway $inboundAmqpGateway,
        AmqpAdmin $amqpAdmin,
        bool $declareOnStartup,
        string $amqpQueueName,
        int $receiveTimeoutInMilliseconds,
        string $acknowledgeMode
    )
    {
        $this->amqpConnectionFactory = $amqpConnectionFactory;
        $this->inboundAmqpGateway = $inboundAmqpGateway;
        $this->declareOnStartup = $declareOnStartup;
        $this->amqpAdmin = $amqpAdmin;
        $this->amqpQueueName = $amqpQueueName;
        $this->receiveTimeoutInMilliseconds = $receiveTimeoutInMilliseconds;
        $this->acknowledgeMode = $acknowledgeMode;
    }

    /**
     * @throws Exception
     * @throws SubscriptionConsumerNotSupportedException
     * @throws InvalidArgumentException
     */
    public function startMessageDrivenConsumer(): void
    {
        /** @var AmqpContext $context */
        $context = $this->amqpConnectionFactory->createContext();
        $this->amqpAdmin->declareQueueWithBindings($this->amqpQueueName, $context);

        $consumer = $context->createConsumer(new \Interop\Amqp\Impl\AmqpQueue($this->amqpQueueName));

        $subscriptionConsumer = $context->createSubscriptionConsumer();
        $subscriptionConsumer->subscribe($consumer, function (AmqpMessage $message, Consumer $consumer) {
            try {
                $this->inboundAmqpGateway->execute($message, $consumer);
                $consumer->acknowledge($message);
            } catch (Throwable $e) {
                $consumer->reject($message, true);
                throw $e;
            }
        });

        $subscriptionConsumer->consume($this->receiveTimeoutInMilliseconds);
    }
}