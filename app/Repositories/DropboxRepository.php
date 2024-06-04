<?php
namespace App\Repositories;

use Spatie\Dropbox\Client;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Log;
use App\Models\Dropbox;
use Illuminate\Database\Eloquent\ModelNotFoundException;
class DropboxRepository
{
    public function uploadPDFDropbox($fileName, $folderName,$url)
    {
        
        try {
            
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
            
            $accessToken = config('services.dropbox.access_token');
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
            Log::info("Fetching PDF from URL: $url");
            //Log::info("Headers: " . json_encode($headers));

            // Create a Guzzle HTTP client
            $httpClient = new HttpClient();

            // Make the request and get the response
            $response = $httpClient->request('GET', $url);
            echo 1;
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

            $client = new Client($accessToken);
            $remoteDirectory = '/' . $folderName;
            $filePath = $remoteDirectory . '/' . $fileName; // Dropbox path where the file will be saved

            try {
                $client->upload($filePath, $content, 'overwrite');
            } catch (\Exception $e) {
                Log::error("Error uploading the file to Dropbox: " . $e->getMessage());
                return response()->json(['error' => "Error uploading the file to Dropbox: " . $e->getMessage()], 500);
            }

            // Get the shared link for the file in Dropbox
            try {
                $sharedLink = $client->createSharedLinkWithSettings($filePath);
            } catch (\Exception $e) {
                Log::error("Error getting the shared link for the file: " . $e->getMessage() . " - " . json_encode($e->getTrace()));
                return response()->json(['error' => "Error getting the shared link for the file: " . $e->getMessage() . " - " . json_encode($e->getTrace())], 500);
            }

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

    public function store(array $data)
    {
        return Dropbox::create($data);
    }
    public function getLatest()
    {

        try {
            // Find the last record and return it
            echo 1;
            $lastToken = Dropbox::latest('id')->first();
            
            return $lastToken;
        } catch (Exception $e) {
            // Log the error
            \Log::error('Error occurred: ' . $e->getMessage());

            // You can also return a custom error response
            return response()->json([
                'error' => 'An error occurred. Please try again later.',
                'details' => $e->getMessage() // Aquí se incluye el detalle del error
            ], 500);
        }
    }
}
?>