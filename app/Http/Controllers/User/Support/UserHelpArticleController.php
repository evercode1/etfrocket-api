<?php

namespace App\Http\Controllers\User\Support;

use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use App\Models\HelpArticleCategory;
use Illuminate\Http\Request;

class UserHelpArticleController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:120',
        ]);

        $query = HelpArticle::query()
            ->leftJoin(
                'help_article_categories',
                'help_articles.help_article_category_id',
                '=',
                'help_article_categories.id'
            )
            ->where('help_articles.is_published', 1);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($builder) use ($search) {
                $builder
                    ->whereFullText(
                        [
                            'help_articles.title',
                            'help_articles.content',
                        ],
                        $search
                    )
                    ->orWhere(
                        'help_articles.title',
                        'like',
                        '%'.$search.'%'
                    )
                    ->orWhere(
                        'help_articles.summary',
                        'like',
                        '%'.$search.'%'
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Category Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('category')) {
            $query->where(
                'help_article_categories.slug',
                $request->input('category')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Paginate
        |--------------------------------------------------------------------------
        */

        $articles = $query
            ->select([
                'help_articles.id',
                'help_articles.title',
                'help_articles.slug',
                'help_articles.summary',
                'help_articles.created_at',
                'help_article_categories.category_name',
                'help_article_categories.slug as category_slug',
            ])
            ->orderBy('help_articles.created_at', 'desc')
            ->paginate(12);

        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        $categories = HelpArticleCategory::select([
            'help_article_categories.id',
            'help_article_categories.category_name',
            'help_article_categories.slug',
        ])
            ->join(
                'help_articles',
                'help_article_categories.id',
                '=',
                'help_articles.help_article_category_id'
            )
            ->where('help_article_categories.is_active', 1)
            ->where('help_articles.is_published', 1)
            ->distinct()
            ->orderBy('help_article_categories.sort_order')
            ->get();

        return response()->json([
            'status' => 'success',
            'articles' => $articles,
            'categories' => $categories,
        ], 200);
    }

    public function show(string $slug)
    {
        $article = HelpArticle::query()
            ->leftJoin(
                'help_article_categories',
                'help_articles.help_article_category_id',
                '=',
                'help_article_categories.id'
            )
            ->where('help_articles.slug', $slug)
            ->where('help_articles.is_published', 1)
            ->select([
                'help_articles.id',
                'help_articles.title',
                'help_articles.slug',
                'help_articles.summary',
                'help_articles.content',
                'help_articles.created_at',
                'help_articles.updated_at',
                'help_article_categories.category_name',
                'help_article_categories.slug as category_slug',
            ])
            ->first();

        if (! $article) {
            return response()->json([
                'status' => 'error',
                'message' => 'Help article not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'article' => $article,
        ], 200);
    }
}
