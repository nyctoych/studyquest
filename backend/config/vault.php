<?php
return [
    // Maximum file upload size: 25MB
    'max_file_size' => 25 * 1024 * 1024,
    'max_file_size_label' => '25 MB',

    // Allowed reviewer document formats
    'allowed_extensions' => ['pdf', 'docx', 'doc', 'pptx', 'ppt', 'txt'],

    // Storage path for uploaded manuscripts (outside webroot execution)
    'storage_path' => realpath(__DIR__ . '/../storage/vault') ?: (__DIR__ . '/../storage/vault'),

    // Allowed MIME types mapped by extension
    'mime_types' => [
        'pdf'  => ['application/pdf', 'application/x-pdf'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed'
        ],
        'doc'  => [
            'application/msword',
            'application/vnd.ms-word',
            'application/x-msword',
            'application/octet-stream'
        ],
        'pptx' => [
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip',
            'application/x-zip-compressed'
        ],
        'ppt'  => [
            'application/vnd.ms-powerpoint',
            'application/powerpoint',
            'application/mspowerpoint',
            'application/x-mspowerpoint',
            'application/octet-stream'
        ],
        'txt'  => [
            'text/plain',
            'text/x-plain',
            'text/markdown',
            'application/octet-stream'
        ]
    ]
];
