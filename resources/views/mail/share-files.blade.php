<p>Приветствуем {{ $user->name }}</p>

<p>Пользователь <b>{{ $author->name }}</b> поделился с вами следующими файлами:</p>
<hr>
@foreach($files as $file)
        <p>{{ $file->is_folder ? 'Папка' : 'Файл' }} - {{ $file->name }}</p>
@endforeach
