<?php

namespace App\Http\Controllers\Admin\Support;

use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use App\Models\HelpArticleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ManageHelpArticlesController extends Controller
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
            );

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
                'help_articles.is_published',
                'help_articles.created_at',
                'help_article_categories.category_name',
                'help_article_categories.slug as category_slug',
            ])

            ->orderBy('help_articles.created_at', 'desc')

            ->paginate(15);

        $categories = HelpArticleCategory::select([
            'id',
            'category_name',
            'slug',
        ])

            ->where('is_active', 1)

            ->orderBy('sort_order')

            ->get();

        return response()->json([

            'status' => 'success',

            'articles' => $articles,

            'categories' => $categories,

        ], 200);
    }

    public function show(int $id)
    {
        $article = HelpArticle::query()

            ->leftJoin(
                'help_article_categories',
                'help_articles.help_article_category_id',
                '=',
                'help_article_categories.id'
            )

            ->where('help_articles.id', $id)

            ->select([
                'help_articles.id',
                'help_articles.title',
                'help_articles.slug',
                'help_articles.summary',
                'help_articles.content',
                'help_articles.is_published',
                'help_articles.created_at',
                'help_articles.updated_at',
                'help_article_categories.id as category_id',
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

    public function store(Request $request)
    {
        $request->validate([

            'help_article_category_id' => 'required|integer|exists:help_article_categories,id',

            'title' => 'required|string|max:255',

            'summary' => 'nullable|string',

            'content' => 'required|string',

            'is_published' => 'required|boolean',

        ]);

        $title = trim($request->input('title'));

        $article = HelpArticle::create([

            'help_article_category_id' => $request->input('help_article_category_id'),

            'title' => $title,

            'slug' => Str::slug($title),

            'summary' => $request->input('summary'),

            'content' => $request->input('content'),

            'is_published' => $request->boolean('is_published'),

        ]);

        return response()->json([

            'status' => 'success',

            'message' => 'Help article created successfully.',

            'article' => $article,

        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $request->validate([

            'help_article_category_id' => 'required|integer|exists:help_article_categories,id',

            'title' => 'required|string|max:255',

            'summary' => 'nullable|string',

            'content' => 'required|string',

            'is_published' => 'required|boolean',

            'slug' => [

                'nullable',

                'string',

                'max:255',

                Rule::unique('help_articles')->ignore($id),

            ],

        ]);

        $article = HelpArticle::find($id);

        if (! $article) {

            return response()->json([

                'status' => 'error',

                'message' => 'Help article not found.',

            ], 404);
        }

        $title = trim($request->input('title'));

        $article->update([

            'help_article_category_id' => $request->input('help_article_category_id'),

            'title' => $title,

            'slug' => $request->filled('slug')
                ? Str::slug($request->input('slug'))
                : Str::slug($title),

            'summary' => $request->input('summary'),

            'content' => $request->input('content'),

            'is_published' => $request->boolean('is_published'),

        ]);

        return response()->json([

            'status' => 'success',

            'message' => 'Help article updated successfully.',

            'article' => $article,

        ], 200);
    }

    public function destroy(int $id)
    {
        $article = HelpArticle::find($id);

        if (! $article) {

            return response()->json([

                'status' => 'error',

                'message' => 'Help article not found.',

            ], 404);
        }

        $article->delete();

        return response()->json([

            'status' => 'success',

            'message' => 'Help article deleted successfully.',

        ], 200);
    }
}
