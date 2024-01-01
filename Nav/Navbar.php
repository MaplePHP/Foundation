<?php

namespace MaplePHP\Foundation\Nav;

use MaplePHP\DTO\Traverse;
use MaplePHP\Nest\Builder;
use MaplePHP\Foundation\Http\Provider;
use InvalidArgumentException;

class Navbar
{
    private $builder;
    private $items = array();
    private $envItems = array();
    private $protocol;
    private $provider;
    private $config = [
        "maxLevel" => 0,
        "nestingSlug" => false,
        "where" => []
    ];

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;

        if(isset($_ENV['NAVIGATION_CONFIG']) && is_array($_ENV['NAVIGATION_CONFIG'])) {
            $this->config = $_ENV['NAVIGATION_CONFIG'];
        }

        // Fallback on possible single config nav
        if (isset($_ENV['NAVIGATION_MAIN'])) {
            $this->envItems = ["main" => $_ENV['NAVIGATION_MAIN']];
        }

        // Multiple config nav
        if(isset($_ENV['NAVIGATION_DATA']) && is_array($_ENV['NAVIGATION_DATA'])) {
            $this->envItems = $_ENV['NAVIGATION_DATA'];
        }
    }

    /**
     * Overwrite configs
     * @param string $key   Config key
     * @param mixed  $value Config value
     */
    public function setConfig(string $key, mixed $value) {
        if(empty($this->config[key])) {
            $configs = array_keys($this->config);
            throw new InvalidArgumentException("Config {$key} does not exists, choose from (".implode(", ", $configs).").", 1);
        }
        $this->config[$key] = $value;
    }

    /**
     * Add item to nav
     * @param array $arr
     * @return self
     */
    public function add(string $navName, array $arr): self
    {
        return $this->addItem($navName, $arr);
    }

    /**
     * Add navigation item to a menu
     * @param string $navName   Select which navgation you want to add item to ("main" is defualt!)
     * @param array  $arr       Navigation item data
     */
    public function addItem(string $navName, array $arr): self
    {
        if (empty($arr['name'])) {
            throw new InvalidArgumentException("Error Navbar::addItem array item name is missing!", 1);
        }
        if (!isset($arr['slug'])) {
            throw new InvalidArgumentException("Error Navbar::addItem array item slug is missing!", 1);
        }
        $this->items[$navName][] = $arr;
        return $this;
    }

    /**
     * Create array items
     * @return array
     */
    private function items(): array
    {
        $items = array();
        $arr = array_merge($this->envItems, $this->items);
        
        foreach ($arr as $menuID => $data) {
            foreach ($data as $key => $item) {
                //$pos = ($item['position'] ?? 0);
                $key = (int)$key;
                $id = (isset($item['id'])) ? (int)$item['id'] : ($key + 1);
                if($id <= 0) {
                    throw new \Exception("The navigation item \"id\" has to be a integer and more than \"0\".", 1);
                }
                $items[$menuID][($item['parent'] ?? 0)][$id] = Traverse::value($item);
            }

        }
        return $items;
    }

    /**
     * Build nav template
     * @return void
     */
    protected function template(): void
    {
        $this->builder->setClass("items gap-x-10")->html(
            "nav",
            "ul",
            "li",
            function ($obj, $li, $active, $level, $id, $parent) {
                $hasChild = ($obj->hasChild ? " has-child" : "");
                $topItem = ($parent === 0) ? " top-item" : "";
                $li->attr("class", "item{$hasChild}{$topItem}{$active}");

                // Create link
                $li->create("a", $obj->name)
                ->attr("href", $this->provider->url()->getRoot($obj->uri))
                ->attr("class", "item item-{$id}{$active}");
            }
        );
    }

    /**
     * Get nav builder
     * @return Builder
     */
    public function get(): Builder
    {
        if (is_null($this->builder)) {
            // Build the navigation structure
            $this->builder = new Builder($this->items());

            // Configs
            $this->builder->setLevel((int)($this->config['maxLevel'] ?? 0));
            $this->builder->nestingSlug(!empty($this->config['nestingSlug']));
            $this->builder->setWhere((isset($this->config['where']) && is_array($this->config['where']) ? $this->config['where'] : []));

            $this->builder->setMultiple(true)->build(function ($obj) {
                return $obj->slug;
            });

            // The navigation template
            $this->template();

            // Protocol will validate request (works great with dynmic routes) and will
            // pass on active nav item to the template.
            $this->protocol = $this->validate($this->provider->url()->getVars());

            // Pass the protocol to the ServiceProvider and container
            // You can use it on the controller to validate request in conjunction with a dynamic
            // PATTERN in the router
            // E.g. ($this->protocol->status() === 200 || $this->protocol->status() === 404 ||
            // $this->protocol->status() === 301)
            $this->provider->set("protocol", $this->protocol);
        }
        return $this->builder;
    }

    /**
     * Validate navigation item request
     * @param  array  $vars
     * @return object
     */
    public function validate(array $vars): object
    {
        $protocol = $this->builder->protocol();
        $protocol->load($vars);
        return $protocol;
    }
}
