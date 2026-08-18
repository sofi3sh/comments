<?php

return [
    'admin' => [
        'title_in_singular' => 'Gallery',
        'title_in_plural'   => 'Gallery',
    ],
    'fields' => [
        'id'          => 'ID',
        'filename'    => 'Filename',
        'path'        => 'Path',
        'mime_type'   => 'MIME Type',
        'size'        => 'Size',
        'alt'         => 'Alt Text',
        'title'       => 'Title',
        'description' => 'Description',
        'caption'     => 'Caption',
        'user'        => 'User',
        'preview'     => 'Preview',
        'created_at'  => 'Created At',
        'updated_at'  => 'Updated At',
        'file'        => 'File',
        'file_replace' => 'Replace File',
        'current_file' => 'Current File',
        'tags'         => 'Tags',
    ],
    'hints' => [
        'filename' => 'Filename is automatically generated',
        'file' => 'Upload a file to add to the gallery',
        'file_replace' => 'Upload a new file to replace the current one',
    ],
    'messages' => [
        'created' => 'File successfully uploaded',
        'updated' => 'File successfully updated',
    ],
];

