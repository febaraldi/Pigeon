<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\Resolver\Resolver;
use Convenia\Pigeon\Tests\TestCase;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class ResolverTest extends TestCase
{
    protected $driver;

    protected $channel;

    protected function setUp()
    {
        parent::setUp();
        $this->channel = Mockery::mock(AMQPChannel::class);
        $this->driver = Mockery::mock('Convenia\Pigeon\Drivers\RabbitDriver');

        $this->driver->shouldReceive('getChannel')->andReturn($this->channel);
    }

    public function test_it_should_ack_message()
    {
        // setup
        $delivery_tag = str_random(5);
        $message = new AMQPMessage();

        $message->delivery_info['delivery_tag'] = $delivery_tag;
        $resolver = new Resolver($this->driver, $message);

        //assert
        $this->channel->shouldReceive('basic_ack')
            ->with($delivery_tag);

        // act
        $resolver->ack();
    }

    public function test_it_should_reject_message_without_requeue()
    {
        // setup
        $delivery_tag = str_random(5);
        $message = new AMQPMessage();
        $requeue = false;

        $message->delivery_info['delivery_tag'] = $delivery_tag;
        $resolver = new Resolver($this->driver, $message);

        //assert
        $this->channel->shouldReceive('basic_reject')
            ->with($delivery_tag, $requeue);

        // act
        $resolver->reject($requeue);
    }

    public function test_it_should_reject_message_with_requeue()
    {
        // setup
        $delivery_tag = str_random(5);
        $message = new AMQPMessage();
        $requeue = true;

        $message->delivery_info['delivery_tag'] = $delivery_tag;
        $resolver = new Resolver($this->driver, $message);

        //assert
        $this->channel->shouldReceive('basic_reject')
            ->with($delivery_tag, $requeue);

        // act
        $resolver->reject($requeue);
    }

    public function test_it_should_respond_a_message()
    {
        // setup
        $reply_to = 'some.reply.queue';
        $delivery_tag = str_random(5);
        $message = new AMQPMessage([], ['reply_to' => $reply_to]);

        $message->delivery_info['delivery_tag'] = $delivery_tag;
        $resolver = new Resolver($this->driver, $message);

        //assert
        $this->channel->shouldReceive('basic_publish')
            ->with(Mockery::type(AMQPMessage::class), '', $reply_to)
            ->once();
        $this->channel->shouldReceive('basic_ack')
            ->with($delivery_tag)
            ->once();

        // act
        $resolver->response(['foo' => 'fighters']);
    }
}