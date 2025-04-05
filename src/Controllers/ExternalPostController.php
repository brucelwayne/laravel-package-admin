<?php

namespace Brucelwayne\Admin\Controllers;

use Illuminate\Http\Request;
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

        $post_model = MallriaPostModel::create([
            'team_id' => TenantFacade::getOrFail()->getKey(),
            'app_id' => AppFacade::getOrFail()->getKey(),
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