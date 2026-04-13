<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EventHostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        if (! $event instanceof Event) {
            return false;
        }

        return $event->host_id === $this->user()?->id;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Forbidden.',
        ], 403));
    }
}
