<?php

namespace Oksydan\Module\IsThemeCore\Hook;

use Context;
use Is_themecore;

abstract class AbstractHook
{
    const HOOK_LIST = [];

    protected $module;
    protected $context;

    public function __construct(Is_themecore $module)
    {
        $this->module = $module;
        $this->context = Context::getContext();
    }

    /**
     * @return array
     */
    public function getAvailableHooks()
    {
        return static::HOOK_LIST;
    }
}
