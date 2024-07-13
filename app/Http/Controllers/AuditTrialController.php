<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminUser;
use App\Models\AuditTrial;
use App\Models\Visitors;
use App\Models\CustomerTrail;
use App\Models\ProductAssessment;
use Illuminate\Support\Facades\Log;


class AuditTrialController extends Controller
{

function Auditor($UserId, $Action) {
    $ipAddress = $_SERVER['REMOTE_ADDR']; // Get user's IP address

    try {
        // Initialize cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://ipinfo.io/{$ipAddress}/json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);

        // Check if any error occurred
        if (curl_errno($ch)) {
            throw new \Exception('cURL error: ' . curl_error($ch));
        }

        // Close cURL session
        curl_close($ch);

        // Decode JSON response
        $ipDetails = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decoding error: ' . json_last_error_msg());
        }

        Log::info('The IP DETAILS:', (array)$ipDetails);

        $country = $ipDetails->country ?? 'Unknown';
        $city = $ipDetails->city ?? 'Unknown';
        $location = $ipDetails->loc ?? ''; // Latitude and Longitude
        $latitude = $location ? explode(',', $location)[0] : '';
        $longitude = $location ? explode(',', $location)[1] : '';
    } catch (\Exception $e) {
        Log::error('Error in Auditor function: ' . $e->getMessage());
        $country = $city = $latitude = $longitude = 'Unknown';
    }

    // Get user agent information
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    // Parse the user agent string to determine device and OS
    $device = $this->detectDevice($userAgent);
    $os = $this->detectOperatingSystem($userAgent);

    // URL path
    $urlPath = $_SERVER['REQUEST_URI'];

    $stu = AdminUser::where('UserId', $UserId)->first();
    if ($stu == null) {
        return response()->json(["message" => "Admin does not exist"], 400);
    }
    

    $googleMapsLink = $latitude && $longitude ? "https://maps.google.com/?q={$latitude},{$longitude}" : '';

    // Create a new AuditTrail instance and save the log to the database
    $auditTrail = new AuditTrial();
    $auditTrail->ipAddress = $ipAddress ?? " ";
    $auditTrail->country = $country ?? " ";
    $auditTrail->city = $city ?? " ";
    $auditTrail->device = $device ?? " ";
    $auditTrail->os = $os ?? " ";
    $auditTrail->urlPath = $urlPath ?? " ";
    $auditTrail->action = $Action ?? " ";
    $auditTrail->googlemap = $googleMapsLink ?? " ";
    $auditTrail->userId = $stu->UserId ?? " ";
    $auditTrail->userName = $stu->Username?? " ";
    $auditTrail->userPic = $stu->Picture ?? " ";

    $auditTrail->save();
}

function CustomerAuditor($UserId, $Action) {
    $ipAddress = $_SERVER['REMOTE_ADDR']; // Get user's IP address

    try {
        // Initialize cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://ipinfo.io/{$ipAddress}/json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);

        // Check if any error occurred
        if (curl_errno($ch)) {
            throw new \Exception('cURL error: ' . curl_error($ch));
        }

        // Close cURL session
        curl_close($ch);

        // Decode JSON response
        $ipDetails = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decoding error: ' . json_last_error_msg());
        }

        Log::info('The IP DETAILS:', (array)$ipDetails);

        $country = $ipDetails->country ?? 'Unknown';
        $city = $ipDetails->city ?? 'Unknown';
        $location = $ipDetails->loc ?? ''; // Latitude and Longitude
        $latitude = $location ? explode(',', $location)[0] : '';
        $longitude = $location ? explode(',', $location)[1] : '';
    } catch (\Exception $e) {
        Log::error('Error in Auditor function: ' . $e->getMessage());
        $country = $city = $latitude = $longitude = 'Unknown';
    }

    // Get user agent information
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    // Parse the user agent string to determine device and OS
    $device = $this->detectDevice($userAgent);
    $os = $this->detectOperatingSystem($userAgent);

    // URL path
    $urlPath = $_SERVER['REQUEST_URI'];

    $stu = AdminUser::where('UserId', $UserId)->first();

    $googleMapsLink = $latitude && $longitude ? "https://maps.google.com/?q={$latitude},{$longitude}" : '';

    // Create a new AuditTrail instance and save the log to the database
    $auditTrail = new CustomerTrail();
    $auditTrail->ipAddress = $ipAddress ?? " ";
    $auditTrail->country = $country ?? " ";
    $auditTrail->city = $city ?? " ";
    $auditTrail->device = $device ?? " ";
    $auditTrail->os = $os ?? " ";
    $auditTrail->urlPath = $urlPath ?? " ";
    $auditTrail->action = $Action ?? " ";
    $auditTrail->googlemap = $googleMapsLink ?? " ";
    $auditTrail->userId = $stu->UserId ?? " ";
    $auditTrail->userName = $stu->Name;

    $auditTrail->save();
}

function ProductAssessment($productId, $Action) {
    $ipAddress = $_SERVER['REMOTE_ADDR']; // Get user's IP address

    try {
        // Initialize cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://ipinfo.io/{$ipAddress}/json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);

        // Check if any error occurred
        if (curl_errno($ch)) {
            throw new \Exception('cURL error: ' . curl_error($ch));
        }

        // Close cURL session
        curl_close($ch);

        // Decode JSON response
        $ipDetails = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decoding error: ' . json_last_error_msg());
        }

        Log::info('The IP DETAILS:', (array)$ipDetails);

        $country = $ipDetails->country ?? 'Unknown';
        $city = $ipDetails->city ?? 'Unknown';
        $location = $ipDetails->loc ?? ''; // Latitude and Longitude
        $latitude = $location ? explode(',', $location)[0] : '';
        $longitude = $location ? explode(',', $location)[1] : '';
    } catch (\Exception $e) {
        Log::error('Error in Auditor function: ' . $e->getMessage());
        $country = $city = $latitude = $longitude = 'Unknown';
    }

    // Get user agent information
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    // Parse the user agent string to determine device and OS
    $device = $this->detectDevice($userAgent);
    $os = $this->detectOperatingSystem($userAgent);

    // URL path
    $urlPath = $_SERVER['REQUEST_URI'];

    $stu = ProductAssessment::where('productId', $productId)->first();

    $googleMapsLink = $latitude && $longitude ? "https://maps.google.com/?q={$latitude},{$longitude}" : '';

    // Create a new AuditTrail instance and save the log to the database
    $auditTrail = new ProductAssessment();
    $auditTrail->ipAddress = $ipAddress ?? " ";
    $auditTrail->country = $country ?? " ";
    $auditTrail->city = $city ?? " ";
    $auditTrail->device = $device ?? " ";
    $auditTrail->os = $os ?? " ";
    $auditTrail->urlPath = $urlPath ?? " ";
    $auditTrail->action = $Action ?? " ";
    $auditTrail->googlemap = $googleMapsLink ?? " ";
    $auditTrail->productId = $stu->productId ?? " ";
    $auditTrail->productName = $stu->productName ?? " ";
    $auditTrail->productPic = $stu->productPic ?? " ";

    $auditTrail->save();
}









 public function Visitors() {
    $ipAddress = $_SERVER['REMOTE_ADDR']; // Get user's IP address

    try {
        // Initialize cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://ipinfo.io/{$ipAddress}/json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);

        // Check if any error occurred
        if (curl_errno($ch)) {
            throw new \Exception('cURL error: ' . curl_error($ch));
        }

        // Close cURL session
        curl_close($ch);

        // Decode JSON response
        $ipDetails = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decoding error: ' . json_last_error_msg());
        }

        Log::info('The IP DETAILS:', (array)$ipDetails);

        $country = $ipDetails->country ?? 'Unknown';
        $city = $ipDetails->city ?? 'Unknown';
        $location = $ipDetails->loc ?? ''; // Latitude and Longitude
        $latitude = $location ? explode(',', $location)[0] : '';
        $longitude = $location ? explode(',', $location)[1] : '';
    } catch (\Exception $e) {
        Log::error('Error in Visitors function: ' . $e->getMessage());
        $country = $city = $latitude = $longitude = 'Unknown';
    }

    // Get user agent information
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    // Parse the user agent string to determine device and OS
    $device = $this->detectDevice($userAgent);
    $os = $this->detectOperatingSystem($userAgent);

    // URL path
    $urlPath = $_SERVER['REQUEST_URI'];

    $googleMapsLink = $latitude && $longitude ? "https://maps.google.com/?q={$latitude},{$longitude}" : '';

    // Create a new AuditTrail instance and save the log to the database
    $auditTrail = new Visitors();
    $auditTrail->IpAddress = $ipAddress ?? " ";
    $auditTrail->Country = $country ?? " ";
    $auditTrail->City = $city ?? " ";
    $auditTrail->Device = $device ?? " ";
    $auditTrail->Os = $os ?? " ";
    $auditTrail->googlemap = $googleMapsLink ?? " ";

    $auditTrail->save();

    return response()->json(['success' => 'true'], 200);
}



    function TokenGenerator(): string {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$^&*()_+{}|<>-=[],.';
        $length = 30;
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }


    function detectDevice($userAgent) {
        $isMobile = false;
        $mobileKeywords = ['Android', 'webOS', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 'Windows Phone'];

        foreach ($mobileKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                $isMobile = true;
                break;
            }
        }

        return $isMobile ? 'Mobile' : 'Desktop';
    }

    // Function to detect operating system from User-Agent string
    function detectOperatingSystem($userAgent) {
        $os = 'Unknown';

        $osKeywords = ['Windows', 'Linux', 'Macintosh', 'iOS', 'Android'];

        foreach ($osKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                $os = $keyword;
                break;
            }
        }

        return $os;
    }


    function IdGenerator(): string {
        $randomID = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        return $randomID;
    }



}
