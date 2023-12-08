<?php

declare(strict_types=1);

namespace MaplePHP\Foundation\Form;

use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Form\Fields;
use MaplePHP\Foundation\Security\Csrf;
use MaplePHP\Foundation\Form\FormFields;

use BadMethodCallException;

class Builder
{
    public const FORM_NAME = null;

    protected $form;
    protected $csrf;
    
    /**
     * Form modal will combine all essentials libraries
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, FormFields $FormFields)
    {
        $this->form = new Fields($FormFields);
        $this->csrf = new Csrf($container->get("cookies")->inst());
    }

    /**
     * Get form instance
     * @return Fields
     */
    public function inst(): Fields
    {
        return $this->form;
    }

    /**
     * Will create an hidden CSRF token field
     * @return string
     */
    public function getTokenTag(): string
    {
        return $this->csrf->tokenTag();
    }

    /**
     * Will create an hidden CSRF token field
     * @return string
     */
    public function getToken(): string
    {
        return $this->csrf->token();
    }

    /**
     * Validate CSRF token
     * @param  string  $token
     * @return boolean
     */
    public function isValidToken(string $token): bool
    {
        return $this->csrf->isValid($token);
    }

    /**
     * Create/generate a new token and return it (new validation is required)
     * @return string
     */
    public function createToken(): string
    {
        return $this->csrf->createToken();
    }
    
    /**
     * Shortcut to all the class Fields methods
     * @param  string $fieldName
     * @param  array $args
     * @return Fields
     */
    public function __call(string $fieldName, array $args)
    {
        return call_user_func_array([$this->form, $fieldName], $args);
    }
}
