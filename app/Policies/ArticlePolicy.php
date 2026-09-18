<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock.tableau_bord.voir');
    }

    public function view(User $user, Article $article): bool
    {
        return $user->can('stock.tableau_bord.voir');
    }

    public function create(User $user): bool
    {
        return $user->can('stock.article.gerer');
    }

    public function update(User $user, Article $article): bool
    {
        return $user->can('stock.article.gerer');
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->can('stock.article.gerer');
    }
}
