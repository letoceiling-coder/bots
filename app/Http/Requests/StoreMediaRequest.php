<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Авторизация проверяется через middleware auth:api
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxSizeVideo = config('media.upload.max_size_video', 102400); // По умолчанию 100 МБ для видео
        $maxSizeOther = config('media.upload.max_size', 10240); // По умолчанию 10 МБ для остальных
        $allowAllTypes = config('media.upload.allow_all_types', false);
        
        $rules = [
            'file' => ['required', 'file'],
            'folder_id' => 'nullable|exists:folders,id'
        ];

        // Если не разрешены все типы, добавляем проверку MIME типов
        if (!$allowAllTypes) {
            $allowedMimes = config('media.upload.allowed_mime_types', []);
            if (!empty($allowedMimes)) {
                $rules['file'][] = 'mimes:' . implode(',', array_map(function($mime) {
                    // Преобразуем MIME типы в расширения для валидации
                    $mimeToExt = [
                        'image/jpeg' => 'jpeg,jpg',
                        'image/png' => 'png',
                        'image/gif' => 'gif',
                        'image/webp' => 'webp',
                        'video/mp4' => 'mp4',
                        'application/pdf' => 'pdf',
                    ];
                    return $mimeToExt[$mime] ?? str_replace(['/', '+'], ['_', '_'], $mime);
                }, $allowedMimes));
            }
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $file = $this->file('file');
            
            if ($file) {
                $maxSizeVideo = config('media.upload.max_size_video', 102400); // 100 МБ для видео
                $maxSizeOther = config('media.upload.max_size', 10240); // 10 МБ для остальных
                
                // Проверяем, является ли файл видео
                $isVideo = str_starts_with($file->getMimeType(), 'video/');
                $maxSize = $isVideo ? $maxSizeVideo : $maxSizeOther;
                $maxSizeMB = $isVideo ? 100 : 10;
                
                if ($file->getSize() > ($maxSize * 1024)) {
                    $validator->errors()->add(
                        'file',
                        "Размер файла не должен превышать {$maxSizeMB} МБ"
                    );
                }
            }
        });
    }
    
    /**
     * Настройка сообщений об ошибках
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Файл обязателен для загрузки',
            'file.file' => 'Загружаемый объект должен быть файлом',
            'file.mimes' => 'Тип файла не разрешен для загрузки',
            'folder_id.exists' => 'Указанная папка не существует'
        ];
    }
}
