<?php

namespace Database\Seeders;

use App\Models\Articles\ArticleType;
use App\Models\User\Permission;
use App\Support\Permissions\CrudOperation;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guardName = 'web';

        $entities = [
            'user',
            'role',
            'permission',
            'article',
            'category',
            'category-translation',
            'article-translation',
            'seo-meta-translation',
            'site',
            'tag',
            'tag-translation',
            'marker',
            'article-type',
            'attachment',
            'article-field-configuration',
            'articles-block-settings',
            'article-translation-permission',
            'locale',
            'static-cache',
            'audit',
        ];

        $operations = CrudOperation::BASE;

        foreach ($entities as $entity) {
            $entityOperations = match ($entity) {
                'user' => [
                    CrudOperation::LIST,
                    CrudOperation::CREATE,
                    CrudOperation::UPDATE,
                    CrudOperation::DELETE,
                    CrudOperation::SHOW,
                    CrudOperation::BLOCK,
                    CrudOperation::UPDATE_ROLES,
                ],
                'article' => [
                    CrudOperation::LIST,
                    'list-own',
                    CrudOperation::CREATE,
                    CrudOperation::UPDATE,
                    'update-own',
                    CrudOperation::DELETE,
                    'delete-own',
                    CrudOperation::DELETE_LIMITED,
                    CrudOperation::SHOW,
                    'show-own',
                    CrudOperation::PUBLISH,
                    'publish-own',
                    CrudOperation::UNPUBLISH,
                    'unpublish-own',
                    CrudOperation::INVALIDATE,
                    'invalidate-own',
                ],
                'seo-meta-translation' => [
                    CrudOperation::CREATE,
                    CrudOperation::UPDATE,
                ],
                'static-cache' => [CrudOperation::DELETE],
                'audit' => [CrudOperation::SHOW],
                default => $operations,
            };

            foreach ($entityOperations as $operation) {
                Permission::firstOrCreate([
                    'name' => "{$entity}.{$operation}",
                    'guard_name' => $guardName,
                ]);
            }
        }

        foreach (array_unique(ArticleType::TYPES) as $articleTypeCode) {
            Permission::firstOrCreate([
                'name' => "article-type-access.{$articleTypeCode}",
                'guard_name' => $guardName,
            ]);
        }

        Permission::where('guard_name', $guardName)
            ->whereIn('name', [
                'article.list-any',
                'article.update-any',
                'article.delete-any',
                'article.show-any',
            ])
            ->delete();

        Permission::where('guard_name', $guardName)
            ->whereIn('name', array_map(
                fn (string $operation): string => "seo-meta.{$operation}",
                CrudOperation::BASE
            ))
            ->delete();

        Permission::where('guard_name', $guardName)
            ->whereIn('name', array_map(
                fn (string $operation): string => "seo-meta-translation.{$operation}",
                [CrudOperation::LIST, CrudOperation::DELETE, CrudOperation::SHOW]
            ))
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = Permission::where('guard_name', $guardName)->get();
        $adminRole = Role::where('name', 'Admin')->where('guard_name', $guardName)->first();

        if ($adminRole && $allPermissions->isNotEmpty()) {
            $adminRole->syncPermissions($allPermissions);
        }
    }
}
