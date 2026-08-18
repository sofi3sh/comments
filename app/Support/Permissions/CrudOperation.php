<?php

namespace App\Support\Permissions;

final class CrudOperation
{
    public const LIST         = 'list';
    public const CREATE       = 'create';
    public const UPDATE       = 'update';
    public const DELETE       = 'delete';
    public const SHOW         = 'show';
    public const INVALIDATE   = 'invalidate';
    public const BLOCK        = 'block';
    public const UPDATE_ROLES   = 'update-roles';
    public const DELETE_LIMITED = 'delete-limited';
    public const PUBLISH        = 'publish';
    public const UNPUBLISH      = 'unpublish';

    public const BASE = [
        self::LIST,
        self::CREATE,
        self::UPDATE,
        self::DELETE,
        self::SHOW,
    ];

    public const ARTICLE_OWN_CAPABLE = [
        self::LIST,
        self::UPDATE,
        self::DELETE,
        self::SHOW,
        self::INVALIDATE,
    ];
}
