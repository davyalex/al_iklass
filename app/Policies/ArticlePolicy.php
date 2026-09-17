<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock.dashboard.view');
    }

    public function view(User $user, Article $article): bool
    {
        return $user->can('stock.dashboard.view');
    }

    public function create(User $user): bool
    {
        return $user->can('stock.article.manage');
    }

    public function update(User $user, Article $article): bool
    {
        return $user->can('stock.article.manage');
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->can('stock.article.manage');
    }
}
