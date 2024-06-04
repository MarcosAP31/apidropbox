<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DropboxService;
use Spatie\Dropbox\Client;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Log;

class DropboxController extends Controller
{
    protected $dropboxService;
    protected $accessToken;

    public function __construct(DropboxService $dropboxService)
    {
        $this->dropboxService = $dropboxService;
        $this->accessToken = env('DROPBOX_ACCESS_TOKEN');
    }
    public function uploadPDFDropbox($fileName, $folderName, $url, Request $request)
    {
        echo 1;
        try {
            // Obtener todos los encabezados del objeto $request
            // Obtener solo la cadena de consulta de la URL
            echo 1;
            $latestToken = $this->dropboxService->getLatestToken();
            $token="";
            if($latestToken==null){
                $token="";
                echo 1;
            }else{
                $token=$latestToken->token;
            }
            echo 1;
            file_put_contents(
                base_path('.env'),
                str_replace(
                    'DROPBOX_ACCESS_TOKEN=' . env('DROPBOX_ACCESS_TOKEN'),
                    $token,
                    file_get_contents(base_path('.env'))
                )
            );
            $queryString = $request->getQueryString();
            //$headers = get_headers($url, 0); // El segundo parámetro opcional devuelve los encabezados como un array asociativo


            $url = str_replace('(1)', '%281%29', $url);
            $url .= '?';
            $completeUrl = $url . $queryString;

            //$url=$request->url();
            // Parse the URL to extract query parameters
            //$parsedUrl = parse_url($url);

            /*if (!isset($parsedUrl['query'])) {
                return response()->json(['error' => "Invalid URL: Missing query parameters"], 400);
            }
            
            parse_str($parsedUrl['query'], $queryParams);

            // Check if all required query parameters are present
            $requiredParams = ['X-Amz-Algorithm', 'X-Amz-Credential', 'X-Amz-Date', 'X-Amz-Expires', 'X-Amz-SignedHeaders', 'X-Amz-Signature'];
            foreach ($requiredParams as $param) {

                if (!isset($queryParams[$param])) {
                    
                    return response()->json(['error' => "Missing required query parameter: $param"], 400);
                }
            }*/

            //$accessToken = 'sl.B2gMd-6VhVbvjhB8jLicr7VD5CVJq1Goe8OOrXpx7dbLpMVF9uzDyKdqeK_2YtgcI4XE0zQAHIECf1ithVyc1tsVSIA910buOuTRT6s1QJwk-YXL77Oxmq9SmFWWwgonq5hJG2nxCL8RVTvQJhwd';
            // Set the headers from the query parameters
            /*$headers = [
                'X-Amz-Algorithm' => $queryParams['X-Amz-Algorithm'],
                'X-Amz-Credential' => $queryParams['X-Amz-Credential'],
                'X-Amz-Date' => $queryParams['X-Amz-Date'],
                'X-Amz-Expires' => $queryParams['X-Amz-Expires'],
                'X-Amz-SignedHeaders' => $queryParams['X-Amz-SignedHeaders'],
                'X-Amz-Signature' => $queryParams['X-Amz-Signature']
            ];*/

            // Log the URL and headers for debugging purposes
            //Log::info("Fetching PDF from URL: $url");
            //Log::info("Headers: " . json_encode($headers));

            // Create a Guzzle HTTP client
            $httpClient = new HttpClient();

            // Make the request and get the response
            $response = $httpClient->request('GET', $completeUrl);

            // Log the response status code
            Log::info("HTTP status code: " . $response->getStatusCode());

            // Check if the request was successful
            if ($response->getStatusCode() !== 200) {
                return response()->json(['error' => "Error fetching the PDF file from the URL. Status code: " . $response->getStatusCode()], 500);
            }

            // Get the content of the file
            $content = $response->getBody()->getContents();

            // Upload the file to Dropbox
            //$accessToken = env('DROPBOX_ACCESS_TOKEN'); // Ensure the access token is stored in your .env file

            $client = new Client($this->accessToken);
            $remoteDirectory = '/' . $folderName;
            $filePath = $remoteDirectory . '/' . $fileName; // Dropbox path where the file will be saved

            try {
                $client->upload($filePath, $content, 'overwrite');
            } catch (\Exception $e) {
                if ($e->getMessage() == 'Expired token') {

                    $this->refreshAccessToken();
                    $client = new Client($this->accessToken);
                    $client->upload($filePath, $content, 'overwrite');
                } else {
                    Log::error("Error uploading the file to Dropbox: " . $e->getMessage());
                    return response()->json(['error' => "Error uploading the file to Dropbox: " . $e->getMessage()], 500);
                }
            }

            try {
                $sharedLink = $client->createSharedLinkWithSettings($filePath);
            } catch (\Exception $e) {
                Log::error("Error getting the shared link for the file: " . $e->getMessage() . " - " . json_encode($e->getTrace()));
                return response()->json(['error' => "Error getting the shared link for the file: " . $e->getMessage() . " - " . json_encode($e->getTrace())], 500);
            }
            echo $this->accessToken;
            return response()->json(['url' => $sharedLink['url']], 200);
        } catch (\Exception $e) {
            // Log the error details
            Log::error('Error occurred: ' . $e->getMessage());

            // Return a custom error response
            return response()->json([
                'error' => 'An error occurred. Please try again later.',
                'details' => $e->getMessage() // Include the error details in the response
            ], 500);
        }

    }

    private function refreshAccessToken()
    {
        $clientId = env('DROPBOX_CLIENT_ID');
        $clientSecret = env('DROPBOX_CLIENT_SECRET');
        $refreshToken = env('DROPBOX_REFRESH_TOKEN');

        $httpClient = new HttpClient();

        $response = $httpClient->post('https://api.dropbox.com/oauth2/token', [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data['access_token'])) {
            $this->accessToken = $data['access_token'];
            file_put_contents(
                base_path('.env'),
                str_replace(
                    'DROPBOX_ACCESS_TOKEN=' . env('DROPBOX_ACCESS_TOKEN'),
                    'DROPBOX_ACCESS_TOKEN=' . $data['access_token'],
                    file_get_contents(base_path('.env'))
                )
            );
            $this->dropboxService->storeToken($data['access_token']);
        } else {
            throw new \Exception('Error refreshing Dropbox access token');
        }
    }


}
