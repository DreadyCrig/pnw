<?php

interface EmailServiceInterface
{
    public function create($data);

    public function test();

    public function schedule();
}