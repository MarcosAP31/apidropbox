<?php

namespace App\Services;

interface DropboxService
{
    public function uploadPDFDropbox($url, $fileName, $folderName);
    public function storeToken(array $data);
    public function getLatestToken();
}