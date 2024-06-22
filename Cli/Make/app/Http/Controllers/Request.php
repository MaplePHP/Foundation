<?php

namespace Http\Controllers;

use MaplePHP\Foundation\Form\Validate;
use MaplePHP\Foundation\Http\Provider;
use MaplePHP\Http\Request;
use MaplePHP\Http\Response;


class ___Controller___ extends BaseController
{
    protected Provider $provider;
    protected $form;

    public function __construct(Provider $provider, ___RequestModel___ $form)
    {
        $this->provider = $provider;
        $this->form = $form;
    }

    /**
     * Display a start/listing of the resource.
     */
    public function index()
    {
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Validate $validate)
    {
    }

    /**
     * Display the specified resource.
     */
    public function show(Response $response)
    {
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Response $response)
    {
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Response $response, Validate $validate)
    {
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Response $response)
    {
    }
}
