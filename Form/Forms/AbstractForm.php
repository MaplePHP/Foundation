<?php

namespace MaplePHP\Foundation\Form\Forms;

use MaplePHP\Foundation\Form\Builder;
use MaplePHP\Foundation\Http\Provider;

abstract class AbstractForm
{
    protected Builder $form;
    protected Provider $provider;

    public function __construct(Provider $provider, Builder $form)
    {
        $this->form = $form;
        $this->provider = $provider;
        $this->createForm();
    }

    /**
     * Create form - Set up the form inside of this method
     * @return void
     */
    abstract protected function createForm(): void;

    /**
     * Direct access form instance
     * @return Builder
     */
    public function form(): Builder
    {
        return $this->form;
    }

    /**
     * Shortcut to all the class Models\Form\Form AND MaplePHP\Form\Fields methods!
     * If thrown Error, then it will be triggered from Models\Form\Form
     * @param string $method Method name
     * @param array $args arguments
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        return call_user_func_array([$this->form, $method], $args);
    }
}
