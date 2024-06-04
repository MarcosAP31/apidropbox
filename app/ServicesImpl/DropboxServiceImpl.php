<?php

namespace App\ServicesImpl;

use App\Services\DropboxService;
use App\Repositories\DropboxRepository;

class DropboxServiceImpl implements DropboxService
{
    protected $dropboxRepository;
    public function __construct(DropboxRepository $dropboxRepository)
    {
        $this->dropboxRepository = $dropboxRepository;
    }
    public function uploadPDFDropbox($url, $fileName, $folderName){
        $this->dropboxRepository->uploadPDFDropbox($url, $fileName, $folderName);
    }
    public function storeToken(array $data){
        $this->dropboxRepository->store($data);
    }
    public function getLatestToken(){
        $this->dropboxRepository->getLatest();
    }
    
}