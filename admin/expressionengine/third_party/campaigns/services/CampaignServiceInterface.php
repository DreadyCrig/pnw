<?php

interface CampaignServiceInterface
{
    public function testConnection();

    public function send($data);

    public function delete($id);
}