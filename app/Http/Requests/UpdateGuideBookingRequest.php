<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGuideBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // يمكن إضافة شرط صلاحيات (مثلاً: المرشد أو الآدمن فقط)
        return true;
    }

    public function rules(): array
    {
        return [
            'status'     => ['required','in:pending,accepted,rejected,canceled'],
            'guide_note' => ['nullable','string','max:500'],
        ];
    }
}
