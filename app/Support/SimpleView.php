<?php

namespace App\Support;

use Illuminate\Contracts\View\View as ViewContract;

class SimpleView implements ViewContract
{
    protected string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function name()
    {
        return 'simple';
    }

    public function with($key, $value = null)
    {
        return $this;
    }

    public function getData()
    {
        return [];
    }

    public function render(callable $callback = null)
    {
        return $this->toHtml();
    }

    public function toHtml()
    {
        return $this->content;
    }
}
