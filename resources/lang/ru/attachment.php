<?php

return [
    'admin' => [
        'title_in_singular' => 'Галерея',
        'title_in_plural'   => 'Галерея',
    ],
    'fields' => [
        'id'          => 'ID',
        'filename'    => 'Название файла',
        'path'        => 'Путь',
        'mime_type'   => 'Тип файла',
        'size'        => 'Размер',
        'alt'         => 'Alt текст',
        'title'       => 'Title',
        'description' => 'Описание',
        'caption'     => 'Подпись',
        'user'        => 'Пользователь',
        'preview'     => 'Предпросмотр',
        'created_at'  => 'Создано',
        'updated_at'  => 'Обновлено',
        'file'        => 'Файл',
        'file_replace' => 'Заменить файл',
        'current_file' => 'Текущий файл',
        'tags'         => 'Теги',
    ],
    'hints' => [
        'filename' => 'Название файла формируется автоматически',
        'file' => 'Загрузите файл для добавления в галерею',
        'file_replace' => 'Загрузите новый файл, чтобы заменить текущий',
    ],
    'messages' => [
        'created' => 'Файл успешно загружен',
        'updated' => 'Файл успешно обновлен',
    ],
];

