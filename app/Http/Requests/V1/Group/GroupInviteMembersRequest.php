<?php

namespace App\Http\Requests\V1\Group;

use Illuminate\Foundation\Http\FormRequest;

class GroupInviteMembersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // 'users' => 'required|array|exists:users,id',
            'users' => 'required|array',
            'note' => 'string',
        ];
    }
}
