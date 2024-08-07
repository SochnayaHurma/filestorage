<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FilesActionRequest extends ParentIdBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(
            parent::rules(),
            [
                'all' => 'nullable|bool',
                'ids.*' => [
                    Rule::exists('files', 'id'),
                    function ($attribute, $id, $fail) {
                        $file = File::query()
                            ->leftJoin('file_shares', 'file_shares.file_id', 'files.id')
                            ->where('id', $id)
                            ->where(function($query) {
                                $query->where('files.created_by', Auth::id())
                                    ->orWhere('file_shares.user_id', Auth::id());
                            });

                        if (!$file) {
                            $fail('Некорректный ID: ' . $id);
                        }
                    }
                ],

            ]
        );
    }
}
