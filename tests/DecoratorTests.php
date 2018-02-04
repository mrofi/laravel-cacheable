<?php

namespace Suitmedia\Cacheable\Tests;

use Suitmedia\Cacheable\Tests\Models\Video;
use Suitmedia\Cacheable\Tests\Repositories\VideoRepository;
use Suitmedia\Cacheable\CacheableDecorator;
use Suitmedia\Cacheable\CacheableService;
use Suitmedia\Cacheable\Exceptions\MethodNotFoundException;

class DecoratorTests extends TestCase
{
    /**
     * Cacheable Decorator
     *
     * @var \Suitmedia\Cacheable\CacheableDecorator
     */
    protected $decorator;

    /**
     * Mocked repository
     *
     * @var \Suitmedia\Cacheable\Contracts\CacheableRepository
     */
    protected $mockedRepository;

    /**
     * Cacheable Eloquent Model
     *
     * @var \Suitmedia\Cacheable\Contracts\CacheableModel
     */
    protected $model;

    /**
     * Cacheable Repository
     *
     * @var \Suitmedia\Cacheable\Contracts\CacheableRepository
     */
    protected $repository;

    /**
     * Setup test requirements
     */
    public function setUp()
    {
        parent::setUp();

        $this->model = new Video;
        $this->repository = new VideoRepository($this->model);
        $this->decorator = new CacheableDecorator(app(CacheableService::class), $this->repository);

        $this->mockedRepository = \Mockery::mock(VideoRepository::class);
    }

    /** @test */
    public function generate_the_custom_tags()
    {
        $tags = $this->invokeMethod(
            $this->decorator,
            'generateCustomTags',
            [['Video', 'Article'], $this->user]
        );

        $expected = [
            'User:1' => true,
            'Video:User:1' => true,
            'Article:User:1' => true
        ];

        $this->assertEquals($expected, $tags);
    }

    /** @test */
    public function generate_all_of_the_cache_tags_without_custom_tags()
    {
        $tags = $this->invokeMethod(
            $this->decorator,
            'generateTags',
            [null]
        );
        $this->assertEquals(['Video', 'VideoAlbum'], $tags);
    }

    /** @test */
    public function generate_all_of_the_cache_tags_with_custom_tags()
    {
        $actual = $this->invokeMethod(
            $this->decorator,
            'generateTags',
            [['param1', $this->user, $this->otherUser]]
        );

        $expected = [
            'Video',
            'VideoAlbum',
            'User:1',
            'Video:User:1',
            'VideoAlbum:User:1',
            'User:2',
            'Video:User:2',
            'VideoAlbum:User:2'
        ];

        $this->assertEquals($expected, $actual);
    }

    /** @test */
    public function recognize_any_method_that_should_be_cached()
    {
        $cacheable = $this->invokeMethod(
            $this->decorator,
            'methodIsCacheable',
            ['findByName']
        );
        $this->assertEquals(true, $cacheable);
    }

    /** @test */
    public function recognize_any_method_that_should_not_be_cached()
    {
        $cacheable = $this->invokeMethod(
            $this->decorator,
            'methodIsCacheable',
            ['update']
        );
        $this->assertEquals(false, $cacheable);
    }

    /** @test */
    public function return_exception_while_trying_to_access_undefined_method()
    {
        $this->prepareException(MethodNotFoundException::class);

        $this->decorator->getPhoneNumber();
    }

    /** @test */
    public function execute_the_method_directly_if_it_is_not_cacheable()
    {
        $this->mockedRepository->shouldReceive('update')
            ->times(2)
            ->andReturn('Video Updated');
        $this->mockedRepository->shouldReceive('cacheExcept')
            ->times(2)
            ->andReturn(['create', 'update', 'delete']);

        $decorator = new CacheableDecorator(app(CacheableService::class), $this->mockedRepository);

        $decorator->update(new Video);
        $result = $decorator->update(new Video);

        $this->assertEquals('Video Updated', $result);
    }

    /** @test */
    public function execute_the_method_once_if_it_is_cacheable()
    {
        $this->mockedRepository->shouldReceive('getAllVideos')
            ->times(1)
            ->andReturn('All Videos');
        $this->mockedRepository->shouldReceive('cacheTags')
            ->times(2)
            ->andReturn('Video');
        $this->mockedRepository->shouldReceive('cacheDuration')
            ->times(2)
            ->andReturn(120);
        $this->mockedRepository->shouldReceive('cacheExcept')
            ->times(2)
            ->andReturn(['create', 'update', 'delete']);
        $this->mockedRepository->shouldReceive('cacheKey')
            ->times(2)
            ->andReturn('cache-key-123');

        $decorator = new CacheableDecorator(app(CacheableService::class), $this->mockedRepository);

        $decorator->getAllVideos();

        $result = $decorator->getAllVideos();

        $this->assertEquals('All Videos', $result);
    }
}
