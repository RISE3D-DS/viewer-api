<?php

namespace Rise3d\ViewerApi;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ViewerApi
{
    protected $baseUrl;

    protected $headers;

    protected $projectId;

    protected $token;

    protected $attachment;

    public function __construct()
    {
        $this->baseUrl = config('services.viewer.api_url');
        $this->headers = ['Accept' => 'application/json'];

        // Add extra header on non production environments.
        if (! app()->environment('production')) {
            $this->headers['X-Portaal-Base-Uri'] = config('app.url');
        }
    }

    /**
     * Set API token.
     *
     * This function will only be used when an action should be performed via an external app,
     * which could also be our own app. For example, generate an project information report.
     * It overwrites the API token of the current logged in user.
     *
     * @param  string  $token
     */
    public function token($token)
    {
        $this->token = $token;

        // Set token as request header.
        $this->headers['X-Portaal-Token'] = $this->token;

        return $this;
    }

    /**
     * Set project.
     *
     *  @return ViewerApiService
     */
    public function project($project)
    {
        // Set project
        $this->projectId = $project->hxdr_id;

        // Update request header.
        $this->headers['X-Portaal-Project-Id'] = $this->projectId;

        return $this;
    }

    /**
     * Add an attachment to the request.
     *
     * @return ViewerApiService
     */
    public function attachment($attachment)
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * Build the API request.
     *
     * @return PendingRequest;
     */
    protected function buildRequest()
    {
        // Set current user API token if none is set.
        if (is_null($this->token)) {
            $this->token(decrypt(auth()->user()->api_token));
        }

        return Http::withHeaders($this->headers);
    }

    /**
     * Get resource details from Luciad API.
     *
     * @param  string  $path  The path of the endpoint.
     * @return object
     *
     * @throws RequestException
     */
    public function get(string $path)
    {
        $response = $this->buildRequest()
            ->get($this->baseUrl.'/'.$path);

        if ($response->failed()) {
            $this->failed($this->baseUrl.'/'.$path, 'GET', $response->status());

            $response->throw();
        }

        return $response->json();
    }

    /**
     * Post request to the Luciad back-end.
     *
     * @param  string  $path  The path of the endpoint.
     * @param  array  $data  Additional data to be sent with request.
     * @return void
     *
     * @throws RequestException
     */
    public function post(string $path, ?array $data)
    {
        $response = $this->buildRequest()
            ->asJson()
            ->post($this->baseUrl.'/'.$path, $data);

        if ($response->failed()) {
            $this->failed($this->baseUrl.'/'.$path, 'POST', $response->status());

            $response->throw();
        }

        return $response->json();
    }

    /**
     * Update request to the Luciad back-end.
     *
     * @param  string  $path The path of the endpoint.
     * @param  array|null  $data Additional data to be sent with request.
     * @return void
     *
     * @throws RequestException
     */
    public function put(string $path, ?array $data)
    {
        $response = $this->buildRequest()
            ->asJson()
            ->put($this->baseUrl.'/'.$path, $data);

        if ($response->failed()) {
            $this->failed($this->baseUrl.'/'.$path, 'PUT', $response->status());

            $response->throw();
        }

        return $response->json();
    }

    /**
     * Delete an object from the Luciad back-end.
     *
     * @param  string  $path  The path of the endpoint.
     * @return object
     *
     * @throws RequestException
     */
    public function delete(string $path)
    {
        $response = $this->buildRequest()
            ->asJson()
            ->delete($this->baseUrl.'/'.$path);

        if ($response->failed()) {
            $this->failed($this->baseUrl.'/'.$path, 'DELETE', $response->status());

            $response->throw();
        }

        return $response->json();
    }

    /**
     * Upload a layer for a specified project.
     *
     * @param  string  $name  Layer name
     * @param  string  $epsgCode  EPSG code
     * @return object
     *
     * @throws RequestException
     */
    public function uploadLayer(string $name, string $epsgCode)
    {
        $response = $this->buildRequest()
            ->asMultipart()
            ->attach('file', $this->attachment, uniqid().'.shp')
            ->post($this->baseUrl.'/projects/'.$this->projectId.'/layers/upload-file', [
                'projectId' => $this->projectId,
                'properties' => '{ "epsgCode": "'.$epsgCode.'", "layerLabel": "'.$name.'", "layerType": "WFS" }',
            ]);

        if ($response->failed()) {
            $this->failed($this->baseUrl.'/projects/'.$this->projectId.'/layers/upload-file', 'POST', $response->status());

            $response->throw();
        }

        return $response->json();
    }

    /**
     * When an API request failed.
     *
     * @return void
     */
    public function failed(string $url, string $method, int $statusCode)
    {
        // Send notification in Slack channel.
        Log::channel('slack')
            ->error('API request to viewer back-end failed', [
                'environment' => app()->environment(),
                'url' => $url,
                'method' => $method,
                'status code' => $statusCode,
            ]);
    }
}
