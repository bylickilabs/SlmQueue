<?php

namespace SlmQueueTest\Queue;

use DateTime;
use PHPUnit_Framework_TestCase as TestCase;
use SlmQueueTest\Asset\QueueAwareJob;
use SlmQueueTest\Asset\SimpleQueue;
use SlmQueueTest\Asset\SimpleJob;

class QueueTest extends TestCase
{
    protected $job;
    protected $jobName;
    protected $jobPluginManager;
    protected $queue;

    public function setUp()
    {
        $this->job     = new SimpleJob;
        $this->jobName = 'SlmQueueTest\Asset\SimpleJob';

        $this->jobPluginManager = $this->getMock('SlmQueue\Job\JobPluginManager');
        $this->queue = new SimpleQueue('queue', $this->jobPluginManager);
    }

    public function testCanPushThenPopJob()
    {
        $this->jobPluginManager->expects($this->once())
                               ->method('get')
                               ->with($this->jobName)
                               ->will($this->returnValue($this->job));

        $this->queue->push($this->job);
        $job = $this->queue->pop();

        $this->assertInstanceOf($this->jobName, $job);

        $expected = spl_object_hash($this->job);
        $actual   = spl_object_hash($job);
        $this->assertEquals($expected, $actual);
    }

    public function testCanPushThenPopWithJobContent()
    {
        $this->jobPluginManager->expects($this->once())
                               ->method('get')
                               ->with($this->jobName)
                               ->will($this->returnValue($this->job));

        $this->job->setContent('Foo');

        $this->queue->push($this->job);
        $job = $this->queue->pop();

        $this->assertEquals('Foo', $job->getContent());
    }

    public function testCanPushThenPopWithJobMetadata()
    {
        $this->jobPluginManager->expects($this->once())
                               ->method('get')
                               ->with($this->jobName)
                               ->will($this->returnValue($this->job));

        $this->job->setMetadata('Foo', 'Bar');

        $this->queue->push($this->job);
        $job = $this->queue->pop();

        $this->assertEquals(array('Foo' => 'Bar'), $job->getMetadata());
        $this->assertEquals('Bar', $job->getMetadata('Foo'));
    }

    public function testCorrectlySerializeJobContent()
    {
        $job = new SimpleJob();
        $job->setContent('Foo');

        $expected = '{"__name__":"SlmQueueTest\\\Asset\\\SimpleJob","content":"s:3:\"Foo\";","metadata":[]}';
        $actual   = $this->queue->serializeJob($job);

        $this->assertEquals($expected, $actual);
    }

    public function testCorrectlySerializeJobMetadata()
    {
        $job = new SimpleJob();
        $job->setMetadata('Foo', 'Bar');

        $expected = '{"__name__":"SlmQueueTest\\\Asset\\\SimpleJob","content":"N;","metadata":{"Foo":"Bar"}}';
        $actual   = $this->queue->serializeJob($job);

        $this->assertEquals($expected, $actual);
    }

    public function testCorrectlySerializeJobContentAndMetadata()
    {
        $job = new SimpleJob();
        $job->setContent('Foo');
        $job->setMetadata('Foo', 'Bar');

        $expected = '{"__name__":"SlmQueueTest\\\Asset\\\SimpleJob","content":"s:3:\"Foo\";","metadata":{"Foo":"Bar"}}';
        $actual   = $this->queue->serializeJob($job);

        $this->assertEquals($expected, $actual);
    }

    public function testCorrectlySerializeJobServiceName()
    {
        $job = new SimpleJob();
        $job->setMetadata('__name__', 'SimpleJob');

        $expected = '{"__name__":"SimpleJob","content":"N;","metadata":{"__name__":"SimpleJob"}}';
        $actual   = $this->queue->serializeJob($job);

        $this->assertEquals($expected, $actual);
    }

    public function testCanCreateJobWithFQCN()
    {
        $this->jobPluginManager->expects($this->once())
                               ->method('get')
                               ->with($this->jobName)
                               ->will($this->returnValue($this->job));

        $payload = '{"__name__":"SlmQueueTest\\\Asset\\\SimpleJob","content":"N;","metadata":[]}';
        $job     = $this->queue->unserializeJob($payload);

        $expected = spl_object_hash($this->job);
        $actual   = spl_object_hash($job);
        $this->assertEquals($expected, $actual);
    }

    public function testCanCreateJobWithStringName()
    {
        $this->jobPluginManager->expects($this->once())
                               ->method('get')
                               ->with('SimpleJob')
                               ->will($this->returnValue($this->job));

        $payload = '{"__name__":"SimpleJob","content":"N;","metadata":[]}';
        $job     = $this->queue->unserializeJob($payload);

        $expected = spl_object_hash($this->job);
        $actual   = spl_object_hash($job);
        $this->assertEquals($expected, $actual);
    }

    public function testCanCreateJobWithContent()
    {
        $this->jobPluginManager->expects($this->once())
                               ->method('get')
                               ->with($this->jobName)
                               ->will($this->returnValue($this->job));

        $payload = '{"__name__":"SlmQueueTest\\\Asset\\\SimpleJob","content":"s:3:\"Foo\";","metadata":[]}';
        $job     = $this->queue->unserializeJob($payload);

        $this->assertEquals('Foo', $job->getContent());
    }

    public function testCanCreateJobWithMetadata()
    {
        $this->jobPluginManager->expects($this->once())
                               ->method('get')
                               ->with($this->jobName)
                               ->will($this->returnValue($this->job));

        $payload = '{"__name__":"SlmQueueTest\\\Asset\\\SimpleJob","content":"N;","metadata":{"Foo":"Bar"}}';
        $job     = $this->queue->unserializeJob($payload);

        $this->assertEquals('Bar', $job->getMetadata('Foo'));
    }

    public function testCreateQueueAwareJob()
    {
        $job = new QueueAwareJob();
        $this->jobPluginManager->expects($this->once())
                               ->method('get')
                               ->with('QueueAwareJob')
                               ->will($this->returnValue($job));

        $payload = '{"__name__":"QueueAwareJob","content":"N;","metadata":{"__name__":"QueueAwareJob"}}';
        $this->queue->unserializeJob($payload);

        $this->assertSame($this->queue, $job->getQueue());
    }
}
