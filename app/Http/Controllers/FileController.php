<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToFavouritesRequest;
use App\Http\Requests\FilesActionRequest;
use App\Http\Requests\ShareFilesRequest;
use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\TrashFilesRequest;
use App\Http\Resources\FileResource;
use App\Jobs\UploadFileToCloudJob;
use App\Mail\ShareFilesMail;
use App\Models\File;
use App\Models\FileShare;
use App\Models\SharredFile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileController extends Controller
{
    public function myFiles(Request $request, string $folder_name = null)
    {
        $search = $request->get('search');
        if ($folder_name) {
            $folder = File::query()
                ->where('created_by', Auth::id())
                ->where('path', $folder_name)
                ->firstOrFail();
        } else {
            $folder = $this->getRoot();
        }
        $favourites = (int)$request->get('favourites');
        $query = File::query()
            ->select('files.*')
            ->with('sharred')
            ->where('files.created_by', Auth::id())
            ->where('_lft', '!=', 1)
            ->orderBy('files.is_folder', 'desc')
            ->orderBy('files.created_at', 'desc')
            ->orderBy('files.id', 'desc');
        if ($search) {
            $query->where('name', 'like', "%$search%");
        } else {
            $query->where('files.parent_id', $folder->id);
        }
        if ($favourites == 1) {
            $query->join('sharred_files', 'sharred_files.file_id', 'files.id')
                ->where('sharred_files.user_id', Auth::id());
        }
        $files = $query->paginate(10);

        $files = FileResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }
        $ancestors = FileResource::collection([...$folder->ancestors, $folder]);
        $folder = new FileResource($folder);
        return Inertia::render(
            'MyFiles',
            compact('files', 'folder', 'ancestors')
        );
    }

    public function createFolder(StoreFolderRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;
        if (!$parent) {
            $parent = $this->getRoot();
        }
        $file = new File();
        $file->is_folder = 1;
        $file->name = $data['name'];

        $parent->appendNode($file);
    }


    public function store(StoreFileRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;
        $file_tree = $request->file_tree;
        $user = $request->user();

        if (!$parent) {
            $parent = $this->getRoot();
        }

        if (!empty($file_tree)) {
            $this->saveFileTree($file_tree, $parent, $user);
        } else {
            foreach ($data['files'] as $file) {
                /** @var \Illuminate\Http\UploadedFile $file */
                $path = $file->store('/files/' . $user->id);
                $instance = new File();
                $instance->is_folder = false;
                $instance->name = $file->getClientOriginalName();
                $instance->mime = $file->getMimeType();
                $instance->size = $file->getSize();
                $instance->storage_path = $path;
                $parent->appendNode($instance);
            }
        }
    }


    public function destroy(FilesActionRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;

        if ($data['all']) {
            $children = $parent->children;

            foreach ($children as $child) {
                $child->moveToTrash();
            }
        } else {
            foreach ($data['ids'] ?? [] as $id) {
                $file = File::find($id);
                if ($file) {
                    $file->moveToTrash();
                }
            }
        }
        return to_route('myFiles', ['folder' => $parent->path]);
    }

    public function download(FilesActionRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;

        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];
        if (!$all and empty($ids)) {
            return [
                'message' => 'Пожалуйста выделите файлы для загрузки',
            ];
        }
        if ($all) {
            $url = $this->createZip($parent->children);
            $filename = $parent->name . '.zip';
        } else {
            [$url, $filename] = $this->getDownloadUrl($ids, $parent->name);
        }
        return [
            'url' => $url,
            'filename' => $filename
        ];
    }


    public function downloadSharedWithMe(FilesActionRequest $request)
    {
        $data = $request->validated();
        $zipName = 'shared_with_me';
        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];
        if (!$all and empty($ids)) {
            return [
                'message' => 'Пожалуйста выделите файлы для загрузки',
            ];
        }
        if ($all) {
            $files = File::getSharedWithMe()->get();
            $url = $this->createZip($files);
            $filename = $zipName . '.zip';
        } else {
            [$url, $filename] = $this->getDownloadUrl($ids, $zipName);
        }
        return [
            'url' => $url,
            'filename' => $filename
        ];
    }

    public function downloadSharedByMe(FilesActionRequest $request)
    {
        $data = $request->validated();
        $zipName = 'shared_by_me';
        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];
        if (!$all and empty($ids)) {
            return [
                'message' => 'Пожалуйста выделите файлы для загрузки',
            ];
        }
        if ($all) {
            $files = File::getSharedByMe()->get();
            $url = $this->createZip($files);
            $filename = $zipName . '.zip';
        } else {
            [$url, $filename] = $this->getDownloadUrl($ids, $zipName);
        }
        return [
            'url' => $url,
            'filename' => $filename
        ];
    }

    public function trash(Request $request)
    {
        $search = $request->get('search');
        $query = File::onlyTrashed()
            ->where('created_by', Auth::id())
            ->orderBy('is_folder', 'desc')
            ->orderBy('deleted_at', 'desc')
            ->orderBy('id', 'desc');
        if ($search){
            $query->where('name', 'like', "%$search%");
        }
        $files = $query->paginate(10);
        $files = FileResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }
        return Inertia::render('Trash', compact('files'));
    }

    private function getRoot()
    {
        return File::query()->whereIsRoot()
            ->where('created_by', Auth::id())
            ->firstOrFail();
    }


    public function saveFileTree(array $fileTree, File $parent, User $user)
    {
        /* @var $folder File */
        foreach ($fileTree as $name => $file) {
            if (is_array($file)) {
                $folder = new File();
                $folder->is_folder = 1;
                $folder->name = $name;
                $parent->appendNode($folder);
                $this->saveFileTree($file, $folder, $user);
            } else {
                $this->saveFile($file, $user, $parent);
            }
        }
    }

    /**
     * @param UploadedFile $file
     * @param User $user
     * @param File $parent
     * @return void
     */
    public function saveFile(UploadedFile $file, User $user, File $parent): void
    {
        /* @var $file UploadedFile */
        $path = $file->store('/files/' . $user->id);
        $file_instance = new File();
        $file_instance->name = $file->getClientOriginalName();
        $file_instance->is_folder = false;
        $file_instance->size = $file->getSize();
        $file_instance->mime = $file->getMimeType();
        $file_instance->storage_path = $path;
        $parent->appendNode($file_instance);
        UploadFileToCloudJob::dispatch($file_instance);
    }

    public function createZip($files): string
    {
        $zipPath = 'zip/' . Str::random() . '.zip';
        $publicPath = 'public/' . $zipPath;
        if (!is_dir(dirname($publicPath))) {
            Storage::makeDirectory(dirname($publicPath));
        }
        $zipFile = Storage::path($publicPath);
        $zip = new \ZipArchive();

        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $this->addFilesToZip($zip, $files);
        }
        $zip->close();
        return asset(Storage::url($zipPath));
    }

    private function addFilesToZip($zip, $files, $ancestors = '')
    {
        foreach ($files as $file) {
            if ($file->is_folder) {
                $this->addFilesToZip($zip, $file->children, $ancestors . $file->name . '/');

            } else {
                $zip->addFile(Storage::path($file->storage_path), $ancestors . $file->name);
            }
        }
    }

    public function restore(TrashFilesRequest $request)
    {
        $data = $request->validated();
        if ($data['all']) {
            $children = File::onlyTrashed();
            foreach ($children as $child) {
                $child->restore();
            }
        } else {
            $ids = $data['ids'] ?? [];
//            dd($ids);
            $children = File::onlyTrashed()->whereIn('id', $ids)->get();
            foreach ($children as $child) {
                $child->restore();
            }
        }
        return to_route('trash');
    }

    public function deleteForever(TrashFilesRequest $request)
    {
        $data = $request->validated();
        if ($data['all']) {
            $children = File::onlyTrashed();
            foreach ($children as $child) {
                $child->deleteForever();
            }
        } else {
            $ids = $data['ids'] ?? [];
            $children = File::onlyTrashed()->whereIn('id', $ids)->get();
            foreach ($children as $child) {
                $child->deleteForever();
            }
        }
        return to_route('trash');
    }

    public function addToFavourites (AddToFavouritesRequest $request) {
        $data = $request->validated();

        $id = $data['id'];
        $file = File::find($id);
        $user_id = Auth::id();

        $starredFile = SharredFile::query()
            ->where('file_id', $file->id)
            ->where('user_id', $user_id)
            ->first();

        if ($starredFile) {
            $starredFile->delete();
        } else {
            SharredFile::create([
                'file_id' => $file->id,
                'user_id' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
        return redirect()->back();
    }
    public function share(ShareFilesRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;

        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];
        $email = $data['email'] ?? [];

        if (!$all && empty($ids)) {
            return [
                'message' => 'Для того чтобы поделится файлом его необходимо выделить'
            ];
        }
        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            return redirect()->back();
        }
        if ($all) {
            $files = $parent->children;
        } else {
            $files = File::find($ids);
        }
        $data = [];
        $ids = Arr::pluck($files, 'id');
        $existingFileIds = FileShare::query()
            ->whereIn('file_id', $ids)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('file_id');
        foreach($files as $file) {
            if ($existingFileIds->has($file->id)) continue;
            $data[] = [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
        }
        FileShare::insert($data);
        Mail::to($user->email)->send(new ShareFilesMail(
            $user, Auth::user(), $files
        ));
        return redirect()->back();
    }

    public function sharedWithMe(Request $request)
    {
        $search = $request->get('search');
        $query = File::getSharedWithMe();
        if ($search) {
            $query->where('name', 'like', "%$search%");
        }
        $files = $query->paginate(10);
        $files = FileResource::collection($files);
        if ($request->wantsJson()) {
            return $files;
        }
        return Inertia::render('SharedWithMe', compact('files'));
    }

    public function sharedByMe(Request $request)
    {
        $search = $request->get('search');
        $query = File::getSharedByMe();
        if ($search) {
            $query->where('name', 'like', "%$search%");
        }
        $files = $query->paginate(10);
        $files = FileResource::collection($files);
        if ($request->wantsJson()) {
            return $files;
        }
        return Inertia::render('SharedByMe', compact('files'));
    }

    private function getDownloadUrl(array $ids, $zipName)
    {
        if (count($ids) == 1) {
            $file = File::find($ids[0]);
            if ($file->is_folder) {
                if ($file->children->count() === 0) {
                    return [
                        'message' => 'Данная папка пуста'
                    ];
                }
                $url = $this->createZip($file->children);
                $filename = $file->name . '.zip';
            } else {
                $dest = 'public/' . pathinfo($file->storage_path, PATHINFO_BASENAME);
                Storage::copy($file->storage_path, $dest);

                $url = asset(Storage::url($dest));
                $filename = $file->name;
            }
        } else {
            $files = File::query()->whereIn('id', $ids)->get();
            $url = $this->createZIp($files);
            $filename = $zipName . '.zip';
        }
        return [$url, $filename];
    }


}
