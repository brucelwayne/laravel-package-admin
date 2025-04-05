<?php

namespace Brucelwayne\Admin\Controllers;

use Brucelwayne\SEO\Exports\FavoritePostsExportV3;
use Brucelwayne\SEO\Models\SeoFavoriteQuickCategoryModel;
use Brucelwayne\SEO\Models\SeoPostModel;
use Brucelwayne\SEO\Models\SeoPostQuickCategoryModel;
use Brucelwayne\SEO\Models\SeoUserModel;
use Brucelwayne\SEO\Models\UserFavoriteSeoPostModel;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Mallria\Category\Models\TransCategoryModel;
use Mallria\Core\Facades\InertiaAdminFacade;
use Mallria\Core\Http\Responses\ErrorJsonResponse;
use Mallria\Core\Http\Responses\SuccessJsonResponse;
use Mallria\Core\Models\User;

class ExternalSellerController extends BaseAdminController
{
    function single(Request $request, SeoPostModel $post)
    {
        $post_hash = $request->get('post');
        $post = SeoPostModel::byHashOrFail($post_hash);
        return InertiaAdminFacade::render('Admin/External/Post', [
            'post' => $post,
        ]);
    }

    function favoritedPostCategory(Request $request)
    {
        if ($request->isMethod('post')) {
            // 定义验证规则
            $validator = Validator::make($request->all(), [
                'scene' => [
                    'required',
                    Rule::in(['material', 'position', 'style']),
                ],
                'category' => ['required'],
            ]);

            // 验证失败处理
            if ($validator->fails()) {
                return new ErrorJsonResponse($validator->errors()->first());
            }

            // 获取验证后的数据
            $validated = $validator->validated();

            $category_hash = Arr::get($validated, 'category');

            if (is_string($category_hash)) {
                $category_id = TransCategoryModel::hashToId($category_hash);

                if (empty($category_id)) {
                    return new ErrorJsonResponse('无效的分类');
                }

                // 数据处理逻辑（如保存到数据库）
                $result = SeoFavoriteQuickCategoryModel::firstOrCreate([
                    'scene' => $validated['scene'],
                    'category_id' => $category_id,
                ], []);

                return new SuccessJsonResponse([
                    'result' => $result,
                ]);
            } else if (is_array($category_hash)) {
                $category_ids = collect($category_hash)
                    ->map(function ($hash) {
                        return TransCategoryModel::hashToId($hash);
                    })
                    ->filter() // 自动过滤掉 null 和 false
                    ->unique()
                    ->values() // 重新整理索引
                    ->toArray(); // 转回普通数组

                $result = [];
                foreach ($category_ids as $category_id) {
                    // 数据处理逻辑（如保存到数据库）
                    $result[] = SeoFavoriteQuickCategoryModel::firstOrCreate([
                        'scene' => $validated['scene'],
                        'category_id' => $category_id,
                    ], []);
                }
                return new SuccessJsonResponse([
                    'results' => $result,
                ]);
            }
            return new ErrorJsonResponse('无效的参数');
        } else if ($request->isMethod('delete')) {
            $hash = $request->get('quick');
            $model = SeoFavoriteQuickCategoryModel::byHashOrFail($hash);
            if ($model->delete()) {
                return new SuccessJsonResponse('删除成功');
            }
            return new ErrorJsonResponse('发生了一些错误，请联系管理员');
        }

        $scene = $request->get('scene');
        $pageSize = $request->get('pageSize', 50);

        $categories = SeoFavoriteQuickCategoryModel::with(['category', 'category.translations'])
            ->when($scene, function ($query) use ($scene) {
                $query->where('scene', $scene);
            })
            ->orderByDesc('_id')
            ->paginate($pageSize);

        if ($request->expectsJson()) {
            return new SuccessJsonResponse([
                'quick_categories' => $categories,
            ]);
        }

        return InertiaAdminFacade::render('Admin/External/FavoritedPostCategory', [
            'quick_categories' => $categories,
        ]);
    }

    function sellers(Request $request)
    {
        // 从请求中获取分页数，默认每页10条数据
        $pageSize = $request->get('pageSize', 10);

        // 使用 Seller 模型分页获取数据
        $sellers = SeoUserModel::where('available', true)
            ->orderBy('id', 'desc')
            ->paginate($pageSize);
        $sellers->load(['user']);

        return InertiaAdminFacade::render('Admin/External/Sellers', [
            'sellers' => $sellers,
        ]);
    }

    function newPostsBySeller(Request $request)
    {
        $seller_hash = $request->get('seller');
        $seller_model = SeoUserModel::byHashOrFail($seller_hash);
        /**
         * @var User $user
         */
        $user = auth()->user();

        // 从请求中获取分页数，默认每页10条数据
        $pageSize = $request->get('pageSize', 10);
        $keywords = $request->get('query');
        $cursor = $request->get('cursor'); // 获取游标

        // 创建查询对象
        if (!empty($keywords)) {
            // Use paginate for search queries
            $postsQuery = SeoPostModel::search($keywords)
                ->where('seo_user_id', $seller_model->getKey())
                ->orderBy('created_at', 'desc');
            $posts = $postsQuery->paginate($pageSize);

            // 获取用户是否收藏的帖子ID
            $userFavoritedIds = UserFavoriteSeoPostModel::where('user_id', $user->getKey())
                ->whereIn('seo_post_id', $posts->pluck('_id')->toArray())
                ->pluck('seo_post_id')
                ->toArray();

            // 标记用户是否收藏
            $posts->transform(function ($post) use ($userFavoritedIds) {
                $post->is_favorited = in_array($post->getKey(), $userFavoritedIds);
                return $post;
            });

            $posts->load(['seo_user.user']);

            return InertiaAdminFacade::render('Admin/External/SearchPosts', [
                'posts' => $posts,
            ]);
        } else {
            // Use cursorPaginate for browsing
            $posts = SeoPostModel::with(['seo_user.user'])->where('seo_user_id', $seller_model->getKey())
                ->orderBy('_id', 'desc')->cursorPaginate($pageSize);

            // 获取总数
            $totalCount = Cache::remember('seo_posts_count', 60, function () {
                return SeoPostModel::raw(function ($collection) {
                    return $collection->estimatedDocumentCount();
                });
            });

            // 获取用户是否收藏的帖子ID
            $userFavoritedIds = UserFavoriteSeoPostModel::where('user_id', $user->getKey())
                ->whereIn('seo_post_id', $posts->pluck('_id')->toArray())
                ->pluck('seo_post_id')
                ->toArray();

            // 标记用户是否收藏
            $posts->transform(function ($post) use ($userFavoritedIds) {
                $post->is_favorited = in_array($post->getKey(), $userFavoritedIds);
                return $post;
            });

            return InertiaAdminFacade::render('Admin/External/NewPosts', [
                'posts' => $posts,
                'totalCount' => $totalCount,
            ]);
        }
    }

    function newPosts(Request $request)
    {
        /**
         * @var User $user
         */
        $user = auth()->user();

        // 从请求中获取分页数，默认每页10条数据
        $pageSize = $request->get('pageSize', 10);
        $keywords = $request->get('query');
        $cursor = $request->get('cursor'); // 获取游标

        // 创建查询对象
        if (!empty($keywords)) {
            // Use paginate for search queries
            $postsQuery = SeoPostModel::search($keywords)
                ->orderBy('created_at', 'desc');
            $posts = $postsQuery->paginate($pageSize);

            $posts->load(['seo_user.user']);

            return InertiaAdminFacade::render('Admin/External/SearchPosts', [
                'posts' => $posts,
            ]);
        } else {
            // Use cursorPaginate for browsing
            $posts = SeoPostModel::with(['seo_user.user'])->orderBy('_id', 'desc')->cursorPaginate($pageSize);

            // 获取总数
            $totalCount = Cache::remember('seo_posts_count', 60, function () {
                return SeoPostModel::raw(function ($collection) {
                    return $collection->estimatedDocumentCount();
                });
            });

            // 获取用户是否收藏的帖子ID
            $userFavoritedIds = UserFavoriteSeoPostModel::where('user_id', $user->getKey())
                ->whereIn('seo_post_id', $posts->pluck('_id')->toArray())
                ->pluck('seo_post_id')
                ->toArray();

            // 标记用户是否收藏
            $posts->transform(function ($post) use ($userFavoritedIds) {
                $post->is_favorited = in_array($post->getKey(), $userFavoritedIds);
                return $post;
            });

            return InertiaAdminFacade::render('Admin/External/NewPosts', [
                'posts' => $posts,
                'totalCount' => $totalCount,
            ]);
        }
    }


    function favoriteNewPost(Request $request)
    {
        /**
         * @var User $user
         */
        $user = auth()->user();
        $post_hash = $request->get('post');
        $seo_post = SeoPostModel::byHashOrFail($post_hash);
        $seo_post_favorite = UserFavoriteSeoPostModel::where('user_id', $user->getKey())
            ->where('seo_post_id', $seo_post->getKey())
            ->first();
        if (empty($seo_post_favorite)) {
            $seo_post_favorite = UserFavoriteSeoPostModel::create([
                'user_id' => $user->getKey(),
                'seo_post_id' => $seo_post->getKey(),
            ]);
            return new SuccessJsonResponse([
                'post_favorite' => $seo_post_favorite,
                'is_favorite' => true,
            ]);
        } else {
            $seo_post_favorite->delete();
            return new SuccessJsonResponse([
                'is_favorite' => false,
            ]);
        }
    }

    function create(Request $request)
    {
        return InertiaAdminFacade::render('Admin/External/CreateSeller');
    }

    function favoritedPosts(Request $request)
    {
        $pageSize = 10;
        $category_hash = $request->get('category');
        $category_model = null;
        if (!empty($category_hash)) {
            $category_model = TransCategoryModel::byHashOrFail($category_hash);
        }
        if (empty($category_model)) {
            $seo_post_favorite = UserFavoriteSeoPostModel::with([
                'seoPost',
                'seoPost.seo_user',
                'seoPost.seo_user.user',
            ])
                ->orderBy('_id', 'desc')
                ->cursorPaginate($pageSize);
        } else {
            $seo_post_favorite = SeoPostQuickCategoryModel::with([
                'seoPost',
                'seoPost.seo_user',
                'seoPost.seo_user.user',
            ])
                ->where('category_id', $category_model->getKey())
                ->orderBy('_id', 'desc')
                ->cursorPaginate($pageSize);
        }

        if (!$seo_post_favorite->isEmpty()) {
            $seo_post_ids = $seo_post_favorite->pluck('seoPost._id')->toArray();
            $seo_post_quick_categories = SeoPostQuickCategoryModel::with([
                'quick_category',
                'quick_category.category',
                'quick_category.category.translations'
            ])->whereIn('seo_post_id', $seo_post_ids)->get();

            // 将 quick_category 数据按照 seo_post_id 分组
            $quickCategoriesByPostId = $seo_post_quick_categories->groupBy('seo_post_id');

            // 为 seo_post_favorite 的每个 seoPost 添加 quick_category
            $seo_post_favorite->transform(function ($record) use ($quickCategoriesByPostId) {
                $seoPost = $record->seoPost;
                if ($seoPost) {
                    $seoPost->quick_categories = $quickCategoriesByPostId->get($seoPost->_id, collect())->pluck('quick_category');
                }
                return $record;
            });
        }

        // 获取总数
        $totalCount = Cache::remember('user_favorite_count', 60, function () {
            return UserFavoriteSeoPostModel::raw(function ($collection) {
                return $collection->estimatedDocumentCount();
            });
        });

        return InertiaAdminFacade::render('Admin/External/FavoritePosts', [
            'posts' => $seo_post_favorite,
            'category' => $category_model,
            'totalCount' => $totalCount,
        ]);
    }

    function exportFavoriteToExcel(Request $request)
    {
        ini_set('memory_limit', '800M');

        $directory = 'exports/favorite_posts';
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }
        $files = Storage::disk('public')->files($directory);
        foreach ($files as $file) {
            Storage::disk('public')->delete($file);
        }

        $quick_category_hash = $request->get('quick_category');
        $quick_category_model = null;
        if (!empty($quick_category_hash)) {
            $quick_category_model = SeoFavoriteQuickCategoryModel::byHashOrFail($quick_category_hash);
        }

        if (empty($quick_category_model)) {
            $fileName = 'favorite_posts_' . time() . '.xlsx';
        } else {
            $fileName = 'favorite_posts_' . $quick_category_model->category->name . time() . '.xlsx';
        }
        $categoryFilePath = $directory . '/' . $fileName;

        Excel::store(new FavoritePostsExportV3($quick_category_model), $categoryFilePath, 'public');

        $url = Storage::disk('public')->url($directory . '/' . $fileName);
        return new SuccessJsonResponse([
            'url' => $url
        ]);
    }

    protected function generateCategorizedExcel()
    {
        $seo_quick_category_models = SeoFavoriteQuickCategoryModel::with(['category'])->get();
        $seo_post_quick_category_models = SeoPostQuickCategoryModel::with(['seoPost', 'category'])->get();
        foreach ($seo_quick_category_models as $seo_quick_category_model) {
            /**
             * @var SeoFavoriteQuickCategoryModel $seo_quick_category_model
             */
            $category = $seo_quick_category_model->category;
            //获取这个分类相关的帖子
            $seo_post_quick_category_models = SeoPostQuickCategoryModel::with(['seoPost'])
                ->where('quick_category_id', $seo_quick_category_model->getKey())
                ->get();

        }
    }

    protected function generateUnCategoriesExcel()
    {
        $posts = SeoPostModel::raw(function ($collection) {
            return $collection->aggregate([
                // 1. 使用 $lookup 关联 categories 表
                [
                    '$lookup' => [
                        'from' => 'seo_post_quick_category', // 关联的集合名
                        'localField' => '_id',  // posts 表的字段
                        'foreignField' => 'seo_post_id', // categories 表的字段
                        'as' => 'category_info', // 关联结果保存的字段名
                    ]
                ],
                // 2. 筛选没有分类记录的 posts
                [
                    '$match' => [
                        'category_info' => ['$size' => 0] // 只保留关联结果为空的记录
                    ]
                ]
            ]);
        });
    }

    function syncCategory(Request $request)
    {
        $quick_category_hash = $request->get('quick_category');
        $post_hash = $request->get('post');

        $quick_category_model = SeoFavoriteQuickCategoryModel::byHashOrFail($quick_category_hash);
        $seo_post_model = SeoPostModel::byHashOrFail($post_hash);

        $post_quick_category_model = SeoPostQuickCategoryModel::where([
            'seo_post_id' => $seo_post_model->getKey(),
            'quick_category_id' => $quick_category_model->getKey(),
        ])->first();

        if (!empty($post_quick_category_model)) {
            //删除关系
            $post_quick_category_model->delete();
        } else {
            //增加关系
            $post_quick_category_model = SeoPostQuickCategoryModel::create([
                'action' => 'favorite',
                'seo_post_id' => $seo_post_model->getKey(),
                'quick_category_id' => $quick_category_model->getKey(),
                'category_id' => $quick_category_model->category_id,
            ]);
        }

        $post_quick_categories = SeoPostQuickCategoryModel::with([
            'quick_category',
            'quick_category.category',
            'quick_category.category.translations'
        ])
            ->where('seo_post_id', $seo_post_model->getKey())
            ->get();

        return new SuccessJsonResponse([
            'post_quick_category' => $post_quick_category_model,
            'post_quick_categories' => $post_quick_categories,
        ]);
    }
}