<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Support\Facades\Auth;

class StoreFileRequest extends ParentIdBaseRequest
{
    protected function prepareForValidation()
    {
        $paths = array_filter($this->relativePaths ?? [], fn($file) => $file != null);
        $this->merge([
            'file_paths' => $paths,
            'folder_name' => $this->detectFolderName($paths)
        ]);
    }

    protected function passedValidation()
    {
        $data = $this->validated();
        $this->replace([
            'file_tree' => $this->buildFileTree($this->file_paths, $data['files'])
        ]);
    }

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
                'files.*' => [
                    'required',
                    'file',
                    function ($attribute, $value, $fail) {
                        /** @var $value \Illuminate\Http\UploadedFile */
                        if (!$this->folder_name) {
                            $file = File::query()->where('name', $value->getClientOriginalName())
                                ->where('created_by', Auth::id())
                                ->where('parent_id', $this->parent_id)
                                ->whereNull('deleted_at')
                                ->exists();
                            if ($file) {
                                $fail('Файл с именем: "' . $value->getClientOriginalName() . '" уже находится в папке');
                            }
                        }
                    },
                ],
                'folder_name' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        if ($value) {
                            $folder = File::query()->where('name', $value)
                                ->where('created_by', Auth::id())
                                ->where('parent_id', $this->parent_id)
                                ->whereNull('deleted_at')
                                ->exists();
                            if ($folder) {
                                $fail('Папка с именем: "' . $value . '" уже существует в данной папке');
                            }
                        }
                    }
                ]

            ]
        );
    }
    public function detectFolderName($paths)
    {
        if (!$paths) {
            return null;
        }
        $parts = explode('/', $paths[0]);
        return $parts[0];
    }

    public function buildFileTree($filePaths, $files)
    {
        $filePaths = array_slice($filePaths, 0, count($files));
        $filePaths = array_filter($filePaths, fn($f) => $f != null);

        $tree = [];

        foreach ($filePaths as $idx => $filePath) {
            // Разделяем имена папок на массив из элементов
            $parts = explode('/', $filePath);
            // Передаем указатель массива $tree
            $currentNode = &$tree;
            foreach ($parts as $part_idx => $part) {
                if (!isset($currentNode[$part])) {
                    $currentNode[$part] = [];
                }

                if ($part_idx == (count($parts) - 1)) {
                    $currentNode[$part] = $files[$idx];
                } else {
                    $currentNode = &$currentNode[$part];
                }
            }

        }
        return $tree;
    }
}

