<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Native\Laravel\Facades\SecureStorage;

class ApiClient
{
    public static function auth()
    {
        return Http::withToken(
            SecureStorage::get('auth_token')
        )->baseUrl(config('services.school_api.url'));
    }
}
