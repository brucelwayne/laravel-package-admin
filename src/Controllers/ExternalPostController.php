<?php

namespace Brucelwayne\Admin\Controllers;

use Brucelwayne\SEO\Models\SeoPostModel;
use Brucelwayne\SEO\Models\UserFavoriteSeoPostModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Mallria\App\Facades\AppFacade;
use Mallria\Core\Enums\PostType;
use Mallria\Core\Facades\InertiaAdminFacade;
use Mallria\Core\Facades\TenantFacade;
use Mallria\Core\Http\Responses\SuccessJsonResponse;
use Mallria\Core\Models\User;
use Mallria\Media\Models\MediableModel;
use Mallria\Shop\Models\ExternalPostModel;
use Mallria\Shop\Models\ForwardedPost;
use Mallria\Shop\Models\MallriaPostModel;

class ExternalPostController extends BaseAdminController
{

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

            return InertiaAdminFacade::render('Business/Admin/External/SearchPosts', [
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

            return InertiaAdminFacade::render('Business/Admin/External/NewPosts', [
                'posts' => $posts,
                'totalCount' => $totalCount,
            ]);
        }
    }


    function posts(Request $request)
    {
        // 获取搜索关键词
        $query = $request->input('q', null);

        // 获取当前页码
        $currentPage = $request->input('page', 1); // 默认为第 1 页

        // 获取每页显示的数量
        $perPage = $request->input('pageSize', 10); // 默认为每页 10 条

        if (!empty($query)) {
            // 使用 Laravel Scout 进行搜索
            $external_posts = ExternalPostModel::search($query)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        } else {
            // 默认获取分页数据
            $external_posts = ExternalPostModel::orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $currentPage);
        }


        return InertiaAdminFacade::render('Admin/External/Posts', [
            'posts' => $external_posts,
        ]);
    }

    function forward(Request $request)
    {
        /**
         * @var User $user
         */
        $user = auth()->user();

        $ex_post_hash = $request->post('ex_post');
        $ex_post_model = ExternalPostModel::byHashOrFail($ex_post_hash);

        $post_type = $request->post('type', PostType::Post->value);
        $post_type = PostType::from($post_type);

        $team_id = null;
        if (!empty(TenantFacade::get())) {
            $team_id = TenantFacade::get()->getKey();
        }
        $app_id = null;
        if (!empty(AppFacade::get())) {
            $app_id = AppFacade::get()->getKey();
        }

        $post_model = MallriaPostModel::create([
            'team_id' => $team_id,
            'app_id' => $app_id,
            'user_id' => $user->getKey(),
            'type' => $post_type->value,
            'content' => $request->post('content'),
        ]);

        $image_ids = $request->post('images');
        $video_ids = $request->post('videos');
        $featured_image_id = $request->post('featured_image_id');

        $mediable_model = MediableModel::saveMediable($post_model, [
            'image_id' => $featured_image_id,
            'show_in_gallery' => false,
            'video_id' => empty($video_ids[0]) ? null : $video_ids[0],
            'image_ids' => $image_ids,
        ]);

        if (!empty($mediable_model)) {
            $post_model->load(['mediable']);
        }

        $forwarded_post = ForwardedPost::create([
            'post_id' => $post_model->getKey(),
            'ex_post_id' => $ex_post_model->getKey(),
        ]);
        $forwarded_post->load('ex_post');

        return new SuccessJsonResponse([
            'post' => $post_model,
            'forwarded_post' => $forwarded_post,
        ]);
    }

}