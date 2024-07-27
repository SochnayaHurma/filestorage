<?php

namespace App\Models;

use App\Traits\HasCreatorAndUpdater;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NodeTrait;

class File extends Model
{
    use HasFactory, NodeTrait, SoftDeletes, HasCreatorAndUpdater;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(File::class, 'parent_id');
    }

    public function sharred()
    {
        return $this->hasOne(SharredFile::class, 'file_id', 'id')
            ->where('user_id', Auth::id());
    }

    public function owner(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $attributes['created_by'] == Auth::id()
                    ? 'me'
                    : $this->user->name;
            }
        );
    }

    public function isOwnerBy($userId): bool
    {
        return $this->created_by == $userId;
    }

    public function isRoot(): bool
    {
        return $this->parent_id == null;
    }

    public function get_file_size()
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        // Если значение размера больше 0, мы спрашиваем в какую степень можно возвести
        // 1024 чтобы получить близкое значение к нашему размеру
        // 0 по 1024 не нашлось 1024 байт == Б
        // 1024 в степени 1 то есть мы имеем дело с кило байтами
        // 2 в 1024 степени значит мы имеем дело с МБ и т д
        $power = $this->size > 0 ? floor(log($this->size, 1024)) : 0;
        return number_format(
                $this->size / pow(1024, $power),
                2, '.', ',') . ' ' . $units[$power];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->parent) {
                return;
            }
            $model->path = (
                !$model->parent->isRoot()
                    ? $model->parent->path . '/'
                    : ''
                ) . Str::slug($model->name);
        });
//        static::deleted(function(File $model) {
//            if (!$model->is_folder) {
//                Storage::delete($model->storage_path);
//            }
//        });
    }

    public function moveToTrash()
    {
        $this->deleted_at = Carbon::now();
        return $this->save();
    }

    public function deleteForever()
    {
        $this->deleteFilesFromStorage([$this]);
        $this->forceDelete();
    }

    public function deleteFilesFromStorage($files)
    {
        foreach ($files as $file) {
            if ($file->is_folder) {
                $this->deleteFilesFromStorage($file->children);
            } else {
                Storage::delete($file->storage_path);
            }
        }
    }

    public static function getSharedWithMe()
    {
        return self::query()
            ->select('files.*')
            ->join('file_shares', 'file_shares.file_id', 'files.id')
            ->where('file_shares.user_id', Auth::id())
            ->orderBy('file_shares.created_at', 'desc')
            ->orderBy('files.id', 'desc');
    }

    public static function getSharedByMe()
    {
        return self::query()
            ->select('files.*')
            ->join('file_shares', 'file_shares.file_id', 'files.id')
            ->where('files.created_by', Auth::id())
            ->orderBy('file_shares.created_at', 'desc')
            ->orderBy('files.id', 'desc');
    }
}
