<?php

namespace App\Infrastructure\Messaging;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SmsSender
{
    private string $provider;
    private array $config;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'log');
        $this->config = config('services.sms', []);
    }

    public function send(string $phone, string $message): bool
    {
        try {
            switch ($this->provider) {
                case 'twilio':
                    return $this->sendViaTwilio($phone, $message);
                
                case 'nexmo':
                    return $this->sendViaNexmo($phone, $message);
                
                case 'africas_talking':
                    return $this->sendViaAfricasTalking($phone, $message);
                
                case 'aqilas':
                    return $this->sendViaAqilas($phone, $message);
                
                case 'log':
                default:
                    return $this->sendViaLog($phone, $message);
            }
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'phone' => $phone,
                'message' => $message,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    private function sendViaTwilio(string $phone, string $message): bool
    {
        // Implementation for Twilio
        // This would use the Twilio SDK
        
        $accountSid = $this->config['twilio']['account_sid'] ?? '';
        $authToken = $this->config['twilio']['auth_token'] ?? '';
        $fromNumber = $this->config['twilio']['from_number'] ?? '';

        if (empty($accountSid) || empty($authToken) || empty($fromNumber)) {
            throw new \InvalidArgumentException('Twilio configuration is incomplete');
        }

        // Here you would integrate with Twilio SDK
        // For now, we'll log it
        Log::info('SMS sent via Twilio', [
            'phone' => $phone,
            'message' => $message,
            'from' => $fromNumber
        ]);

        return true;
    }

    private function sendViaNexmo(string $phone, string $message): bool
    {
        // Implementation for Nexmo/Vonage
        
        $apiKey = $this->config['nexmo']['api_key'] ?? '';
        $apiSecret = $this->config['nexmo']['api_secret'] ?? '';
        $fromNumber = $this->config['nexmo']['from_number'] ?? '';

        if (empty($apiKey) || empty($apiSecret) || empty($fromNumber)) {
            throw new \InvalidArgumentException('Nexmo configuration is incomplete');
        }

        // Here you would integrate with Nexmo SDK
        Log::info('SMS sent via Nexmo', [
            'phone' => $phone,
            'message' => $message,
            'from' => $fromNumber
        ]);

        return true;
    }

    private function sendViaAfricasTalking(string $phone, string $message): bool
    {
        // Implementation for Africa's Talking (popular in Africa)
        
        $username = $this->config['africas_talking']['username'] ?? '';
        $apiKey = $this->config['africas_talking']['api_key'] ?? '';
        $from = $this->config['africas_talking']['from'] ?? '';

        if (empty($username) || empty($apiKey)) {
            throw new \InvalidArgumentException('Africa\'s Talking configuration is incomplete');
        }

        // Here you would integrate with Africa's Talking SDK
        Log::info('SMS sent via Africa\'s Talking', [
            'phone' => $phone,
            'message' => $message,
            'from' => $from
        ]);

        return true;
    }

    private function sendViaAqilas(string $phone, string $message): bool
    {
        // Implementation for AQILAS SMS (https://www.aqilas.com/)
        
        $apiKey = $this->config['aqilas']['api_key'] ?? '';
        $senderId = $this->config['aqilas']['sender_id'] ?? '';
        $baseUrl = $this->config['aqilas']['base_url'] ?? 'https://api.aqilas.com';

        if (empty($apiKey)) {
            throw new \InvalidArgumentException('AQILAS configuration is incomplete');
        }

        try {
            // Format phone number for AQILAS (remove + and country code handling)
            $formattedPhone = $this->formatPhoneForAqilas($phone);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($baseUrl . '/v1/sms/send', [
                'to' => $formattedPhone,
                'message' => $message,
                'sender_id' => $senderId
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('SMS sent via AQILAS', [
                    'phone' => $formattedPhone,
                    'message' => $message,
                    'response' => $responseData
                ]);

                return true;
            } else {
                Log::error('AQILAS SMS sending failed', [
                    'phone' => $formattedPhone,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return false;
            }

        } catch (\Exception $e) {
            Log::error('Exception while sending AQILAS SMS', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    private function formatPhoneForAqilas(string $phone): string
    {
        // AQILAS typically expects phone numbers in international format without +
        // For Burkina Faso: 22670123456 (instead of +226 70 12 34 56)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // If it starts with 226 (Burkina Faso country code), keep as is
        if (str_starts_with($cleanPhone, '226')) {
            return $cleanPhone;
        }
        
        // If it starts with 0, replace with 226
        if (str_starts_with($cleanPhone, '0')) {
            return '226' . substr($cleanPhone, 1);
        }
        
        // If it's a local number (8 digits), add 226
        if (strlen($cleanPhone) === 8) {
            return '226' . $cleanPhone;
        }
        
        return $cleanPhone;
    }

    private function sendViaLog(string $phone, string $message): bool
    {
        // For development/testing - just log the SMS
        Log::info('SMS would be sent', [
            'phone' => $phone,
            'message' => $message,
            'provider' => 'log'
        ]);

        return true;
    }

    public function validatePhoneNumber(string $phone): bool
    {
        // Basic phone number validation
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        
        return !empty($cleanPhone) && strlen($cleanPhone) >= 8 && strlen($cleanPhone) <= 15;
    }

    public function formatPhoneNumber(string $phone, string $countryCode = '+226'): string
    {
        // Remove all non-numeric characters except +
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        
        // If phone doesn't start with +, add country code
        if (!str_starts_with($cleanPhone, '+')) {
            // Remove leading 0 if present
            $cleanPhone = ltrim($cleanPhone, '0');
            $cleanPhone = $countryCode . $cleanPhone;
        }
        
        return $cleanPhone;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function isConfigured(): bool
    {
        switch ($this->provider) {
            case 'twilio':
                return !empty($this->config['twilio']['account_sid']) && 
                       !empty($this->config['twilio']['auth_token']);
            
            case 'nexmo':
                return !empty($this->config['nexmo']['api_key']) && 
                       !empty($this->config['nexmo']['api_secret']);
            
            case 'africas_talking':
                return !empty($this->config['africas_talking']['username']) && 
                       !empty($this->config['africas_talking']['api_key']);
            
            case 'aqilas':
                return !empty($this->config['aqilas']['api_key']);
            
            case 'log':
            default:
                return true;
        }
    }
}
