<?php

namespace Brucelwayne\Admin\Controllers;

use Brucelwayne\AI\Agents\PostForwardContentAgent;
use Brucelwayne\AI\LLMs\ChatGPT;
use Brucelwayne\AI\Models\AiLogModel;
use Brucelwayne\SEO\Events\NewSeoPostForwardedEvent;
use Brucelwayne\SEO\Models\SeoPostModel;
use Brucelwayne\SEO\Models\SeoUserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mallria\App\Facades\AppFacade;
use Mallria\Core\Enums\PostStatus;
use Mallria\Core\Enums\PostType;
use Mallria\Core\Http\Responses\ErrorJsonResponse;
use Mallria\Core\Http\Responses\SuccessJsonResponse;
use Mallria\Media\Models\MediableModel;
use Mallria\Media\Models\MediaModel;
use Mallria\Shop\Models\Translations\PostTranslationModel;
use Mallria\Shop\Models\TransPostModel;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class SEONewPostController extends BaseAdminController
{
    function newPost(Request $request)
    {
        $locale = $request->get('locale', App::getLocale());
        $seo_post_model = SeoPostModel::byHashOrFail($request->post('ex_post'));
        $seo_post_model->load(['seo_user']);
        $post_type = $request->post('type', PostType::Post->value);
        $post_type = PostType::from($post_type);

        $content = empty($seo_post_model->content) ? $seo_post_model->title : $seo_post_model->content;
        if (!empty($content)) {
            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        }

        $result = '';

        if (!empty($content)) {

            // 获取支持的语言区域
            $language = null;
            $supported_locales = LaravelLocalization::getSupportedLocales();
            // 遍历支持的语言，匹配 locale，找到对应的语言名称
            foreach ($supported_locales as $_locale => $supported_locale) {
                if ($_locale === $locale) {
                    $language = $supported_locale['name'];
                    break;
                }
            }

            // 如果没有找到对应的语言，标记为作业失败
            if (empty($language)) {
                // 标记作业为失败，并附加失败原因
                return new ErrorJsonResponse('未找到指定的语言');
            }

            $big_model_name = config('openai.model_name');
            $chatModel = new ChatGPT(model: $big_model_name);//qwen-plus qwen-max-latest
            $postContentAgent = new PostForwardContentAgent($chatModel);
            $response = $postContentAgent->filterAndTranslateMultiple($language, $content);

            if (config('app.debug')) {
                Log::info('AI response：' . json_encode($request));
            }

            $result = get_json_result_from_ai_response($response);

            //记录ai请求记录
            AiLogModel::create([
                'big_model_name' => $big_model_name,
                'job_name' => 'post-forward-content',
                'model_type' => $seo_post_model->getMorphClass(),
                'model_id' => $seo_post_model->getKey(),
                'response' => $response,
                'payload' => [
                    'language' => $language,
                    'result' => $result,
                ],
            ]);

            if (empty($result)) {
                return new ErrorJsonResponse('优化内容发生错误01！',
                    [
                        'response' => $response,
                        'result' => $result,
                    ]
                );
            }

            if (empty($result['status'])) {
                return new ErrorJsonResponse('优化内容发生错误02！',
                    [
                        'response' => $response,
                        'result' => $result,
                    ]
                );
            }
            if (empty($result['text'])) {
                return new ErrorJsonResponse('优化内容发生错误03！',
                    [
                        'response' => $response,
                        'result' => $result,
                    ]
                );
            }
            if ($result['status'] === 'error') {
                return new ErrorJsonResponse('优化内容发生错误04！',
                    [
                        'response' => $response,
                        'result' => $result,
                    ]
                );
            }
        }


        DB::beginTransaction();

        try {

            $new_content = empty($result['text']) ? '' : $result['text'];
            if (!empty($new_content)) {
                $new_content = mb_convert_encoding($new_content, 'UTF-8', 'auto');
            }
            $randomTimestamp = Carbon::now()->subDays(rand(0, 7));

            App::setLocale($locale);
            /**
             * @var TransPostModel $post_model
             */
            $post_model = TransPostModel::create([
                'app_id' => AppFacade::getOrFail()->getKey(),//在哪个app里发布
                'user_id' => $seo_post_model->seo_user->user_id,//哪个用户发布的
                'status' => PostStatus::Published->value,
                'type' => $post_type->value,
                'content' => $new_content,
                'created_at' => $randomTimestamp,
                'updated_at' => $randomTimestamp,
            ]);

            $post_model->setDefaultLocale($locale);

//            // 提取tags
            $tags = [];
            if (!empty($new_content)) {
                $tags = get_tags_from_ai_response($new_content);
            }

            /**
             * @var PostTranslationModel $post_translation_model
             */
            $post_translation_model = $post_model->translateOrNew($locale);
            $post_translation_model->content = $new_content;
            $post_translation_model->save();
//            if (!empty($tags)) {
//                $post_translation_model->syncTags($tags);
//            }

            $image_ids = $request->post('images');
            $video_ids = $request->post('videos');
            $featured_image_id = $request->post('featured_image_id');

            if (empty($featured_image_id) && !empty($image_ids)) {
                $featured_image_id = $image_ids[0];
            }

            $mediable_model = MediableModel::saveMediable($post_model, [
                'image_id' => $featured_image_id,
                'show_in_gallery' => false,
                'video_id' => empty($video_ids[0]) ? null : $video_ids[0],
                'image_ids' => $image_ids,
            ]);

            if (!empty($mediable_model)) {
                $post_model->load(['mediable']);
            }

            $seo_post_model->update([
                'converted_post_id' => $post_model->getKey(),
                'converted_at' => now()
            ]);

            event(new NewSeoPostForwardedEvent(seo_post: $seo_post_model, post: $post_model, locale: $locale));

            DB::commit();

            return new SuccessJsonResponse([
                'post' => $post_model,
                'ex_post_model' => $seo_post_model,
                'content' => $content,
                'result' => $result,
                'tags' => $tags,
                'seo_user' => $seo_post_model->seo_user,
//            'supported_locales' => $supported_locales,
//            'translates' => $translates,
            ]);

        } catch (\Exception|\Throwable $e) {
            DB::rollBack();
            return new ErrorJsonResponse($e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    function getJob(Request $request)
    {
        /**
         * @var SeoPostModel $seo_post_model
         */
        if (config('app.debug')) {
            $seo_post_model = SeoPostModel::whereNull('converted_post_id')
                ->first();
            $random_seo_user_id = $seo_post_model->seo_user_id;
        } else {
            $latest_seo_user_model = SeoUserModel::orderBy('id', 'desc')->first();

            // 获取最新用户的 ID，使用 optional 处理 null 情况
            $latest_user_id = optional($latest_seo_user_model)->id;

            // 生成 1 到 最新用户 ID 之间的随机数
            $random_seo_user_id = $latest_user_id ? rand(1, $latest_user_id) : null;

            // 根据随机用户 ID 获取 SEO 文章
            $seo_post_model = SeoPostModel::whereNull('converted_post_id')
                ->when($random_seo_user_id, function ($query) use ($random_seo_user_id) {
                    return $query->where('seo_user_id', $random_seo_user_id);
                })
                ->first();
        }

        return new SuccessJsonResponse([
            'random_seo_user_id' => $random_seo_user_id,
            'job' => $seo_post_model,
        ]);
    }

    function saveDownloadedMedias(Request $request)
    {
        $seo_post_hash = $request->post('post');
        $seo_post_model = SeoPostModel::byHashOrFail($seo_post_hash);

        $media_hash = $request->post('media');
        $media_model = MediaModel::byHashOrFail($media_hash);

        $url = $request->post('url');

        if (empty($url)) {
            return new ErrorJsonResponse('无效的请求');
        }

        DB::beginTransaction();

        $media_array = $seo_post_model->download_medias;
        if (is_null($media_array)) {
            $media_array = [];
        }

        $media_array[] = [
            'media_id' => $media_model->getKey(),
            'url' => $url,
        ];

        if ($seo_post_model->update([
            'download_medias' => $media_array,
        ])) {
            return new SuccessJsonResponse([
                'post' => $seo_post_model,
            ]);
        }
        return new ErrorJsonResponse('更新时发生错误，请稍后重试！');
    }
}