<?php

namespace lct\Container\Container;

use lct\Contracts\Container\Container as ContainerContract;

class Container implements ContainerContract {

	//全局容器实例
	protected static $instance;

	// 被解析类型数组
	protected $resolved = [];

	// 容器绑定数组
	protected $bindings = [];

	// 容器共享实例
	protected $instances = [];

	// 注册类型别名
	protected $aliases = [];

	// service 的扩展闭包
	protected $extenders = [];

	// 所有注册的标签
	protected $tags = [];

	// 当前创建的混合物的栈
	protected $buildStack = [];

	// 上下文的绑定地图
	protected $contextual = [];

	protected $reboundCallbacks = [];

	protected $globalResolvingCallbacks = [];

	protected $globalAfterResolvingCallbacks = [];

	protected $resolvingCallbacks = [];

	protected $afterResolvingCallbacks = [];

	// 定义一个上下文绑定
	public function when($concrete)
	{
		$concrete = $this->normalize($concrete);

		return new ContextualBindingBuilder($this, $concrete);
	}

	// 是否一绑定
	public function bound($abstract)
	{
		$abstract = $this->normalize($abstract);

		return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]) || $this->
	}

	// 判断类有没有被解析
	public function resolved($abstract)
	{
		$abstract = $this->normalize($abstract);

		if ($this->isAlias($abstract)) {
			$abstract = $this->getAlias($abstract);
		}

		return isset($this->resolved[$abstract]) || isset($this->instances[$abstract]);
	}

	// 判断名称是否为别名
	public function isAlias($name)
	{
		return isset($this->aliases[$this->normalize($name)]);
	}

	public function bind($abstract, $concrete = null, $shared = false)
	{
		$abstract = $this->normalize($abstract);

		$concrete = $this->normalize($concrete);

		if (is_array($abstract)) {
			list($abstract, $alias) = $this->extractAlias($abstract);

			$this->alias($abstract, $alias);
		}

		$this->dropStaleInstances($abstract);

		if (is_null($concrete)) {
			$concrete = $abstract;
		}

		if (! $concrete instanceof Closure) {
			$concrete = $this->getClosure($abstract, $concrete);
		}

		$this->bindings[$abstract] = compact('concrete', 'shared');

		if ($this->resolved($abstract)) {
			$this->rebound($abstract);
		}
	}

	// 绑定类的时候使用闭包
	protected function getClosure($abstract, $concrete)
	{
		return function ($c, $parameters = []) use ($abstract, $concrete) {
			$method = ($abstract == $concrete) ? 'build' : 'make';

			return $c->method($concrete, $parameters);
		}
	}

	// 删除过期的示例和别名
	protected function dropStaleInstances($abstract)
	{
		unset($this->instances[$abstract], $this->aliases[$abstract]);
	}

	// 从定义中提取类型和别名
	public function extractAlias(array $definition)
	{
		return [key($definition), current($definition)];
	}

	// 去掉开头的反斜杠来规范类名
	protected function normalize($service)
	{
		return is_string($service) ? ltrim($service, '\\') : $service;
	}

	public function alias($abstract, $alias)
	{
		$this->aliases[$alias] = $this->normalize($abstract);
	}

	// 添加上下文绑定到容器
	public function addContextualBinding($concrete, $abstract, $implementation)
	{
		$this->contextual[$this->normalize($concrete)][$this->normalize($abstract)] = $this->normalize($implementation);
	}

	// 如果没有注册的话注册一个绑定
	public function bindIf($abstract, $concrete = null, $shared = false)
	{
		if (! $this->bound($abstract)) {
			$this->bind($abstract, $concrete, $shared);
		}
	}

	// 注册一个全局单例
	public function singleton($abstract, $concrete = null)
	{
		$this->bind($abstract, $concrete, true);
	}

	// 封装一个实现共享的闭包函数
	public function share(Closure $closure)
	{
		return function($container) use ($closure) {
			static $object;

			if (is_null($object)) {
				$object = $closure($container);
			}

			return $object;
		}
	}

	// 在容器中扩展抽象类
	public function extend($abstract, Closure $closure)
	{
		$abstract = $this->normalize($abstract);

		if (isset($this->instances[$abstract])) {
			$this->instances[$abstract] = $closure($this->instances[$abstract], $this);

			$this->rebound($abstract);
		} else {
			$this->extenders[$abstract][] = $closure;
		}
	}

	// 注册一个已存在实例并且在容器中共享该实例
	public function instance($abstract, $instance)
	{
		$abstract = $this->normalize($abstract);

		if (is_array($abstract)) {
			list($abstract, $alias) = $this->extractAlias($abstract);

			$this->alias($abstract, $alias);
		}

		unset($this->aliases[$abstract]);

		$bound = $this->bound($abstract);

		$this->instances[$abstract] = $instance;

		if ($bound) {
			$this->rebound($abstract);
		}
	}

	public function tag($abstracts, $tags)
	{
		$tags = is_array($tags) ? $tags : array_slice(func_get_args(), 1);
		
		foreach ($tags as $tag) {
			if (! isset($this->tags[$tag])) {
				$this->tags[$tag] = [];
			}

			foreach ((array) $abstracts as $abstract) {
				$this->tags[$tag][] = $this->normalize($abstract);
			}
		}
	}

}