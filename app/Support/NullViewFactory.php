<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Contracts\View\View as ViewContract;

class NullViewFactory implements ViewFactoryContract
{
    public function exists($view)
    {
        return false;
    }

    public function file($path, $data = [], $mergeData = []): ViewContract
    {
        return new SimpleView(''); 
    }

    public function make($view, $data = [], $mergeData = []): ViewContract
    {
        return new SimpleView(''); 
    }

    public function share($key, $value = null)
    {
        return null;
    }

    public function composer($views, $callback)
    {
        return [];
    }

    public function creator($views, $callback)
    {
        return [];
    }

    public function addNamespace($namespace, $hints)
    {
        return $this;
    }

    public function replaceNamespace($namespace, $hints)
    {
        return $this;
    }
}
