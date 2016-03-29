<?php

namespace lct\Contracts\Container;

use Closure;

interface Container
{
	// 判断所给的抽象类型有没有被绑定
	public function bound($abstract);

	// 给抽象类型取别名
	public function alias($abstract, $alias);

	// 给绑定的抽象类型分配tags
	public function tag($abstract, $tags);

	// 通过给定的tag分解所有的绑定
	public function tagged($tag);

	// 注册一个容器绑定
	public function bind($abstract, $concrete = null, $shared = null);

	// 如果没有注册才注册
	public function bindIf($abstract, $concrete = null, $shared = null);

	// 注册一个共享的绑定，也就是单例
	public function singleton($abstract, $concrete = null);

	// 在容器中扩展抽象类型
	public function extend($abstract, Closure $closure);

	// 在容器中将已经存在的实例注册成共享的
	public function instance($abstract, $instance);

	// 定义一个上下文绑定
	public function when($concrete);

	// 从容器中解析一个给定的类型
	public function make($abstract, array $parameters = []);

	// 调用一个给定的闭包或者类的方法，并且注入它的依赖
	public function call($callback, array $parameters = [], $defaultMethod = null);

	// 判断是否被解析
	public function resolved($abstract);

	// 注册一个新的解析时的回调
	public function resolving($abstract, Closure $callback = null);

	// 注册一个解析后的回调
	public function afterResolving($abstract, Closure $callback = null);

}